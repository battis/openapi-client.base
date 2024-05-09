<?php

namespace Battis\OpenAPI\Client;

use Battis\OpenAPI\Client\Client;
use Battis\OpenAPI\Client\Exceptions\ClientException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use JsonSerializable;

/**
 * @api
 */
abstract class BaseEndpoint extends Mappable
{
    protected string $url = '';

    /**
     * @var array<string, class-string<\Battis\OpenAPI\Client\BaseEndpoint> $endpoints
     */
    protected array $endpoints = [];

    protected Client $api;
    protected ?HttpClient $http = null;

    protected static string $EXPECTED_RESPONSE_MIMETYPE = 'application/json';

    public function __construct(Client $api)
    {
        $this->api = $api;
    }

    public function handleRedirect()
    {
        $this->api->handleRedirect();
    }

    /**
     * @param string $method
     * @param array<string, string> $pathParameters
     * @param array<string, string> $queryParameters
     * @param string|JsonSerializable|null $body
     *
     * @return mixed  description
     */
    protected function send(
        string $method,
        array $pathParameters = [],
        array $queryParameters = [],
        mixed $body = null
    ): mixed {
        /*
         * TODO deal with refreshing tokens (need callback to store new refresh token)
         *   https://developer.blackbaud.com/skyapi/docs/in-depth-topics/api-request-throttling
         */
        usleep(100000);

        $headers = [];
        if ($body instanceof JsonSerializable) {
            $body = json_encode($body);
            $headers['Content-Type'] = 'application/json';
        }

        $url = $this->url;
        foreach ($pathParameters as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        $request = new Request(
            $method,
            $url . '?' . http_build_query($queryParameters),
            array_merge($headers, $this->api->getHeaders()),
            $body
        );

        if ($this->http === null) {
            $this->http = new HttpClient();
        }

        return $this->decodeResponse(
            $this->http
                ->send($request)
                ->getBody()
                ->getContents()
        );
    }

    protected function decodeResponse(string $response): mixed
    {
        return json_decode($response, true);
    }

    /**
     * @param string $name
     *
     * @return ?BaseEndpoint
     */
    public function __get(string $name): ?BaseEndpoint
    {
        if (array_key_exists($name, $this->endpoints)) {
            $instance = "_$name";
            assert(
                property_exists($this, $instance),
                new ClientException(
                    "Expected router property `$instance` not present"
                )
            );
            if ($this->$instance === null) {
                $class = $this->endpoints[$name];
                $this->$instance = new $class($this->api);
            }
            return $this->$instance;
        }
        trigger_error(
            'Undefined property: ' . static::class . "::$name",
            E_USER_WARNING
        );
        return null;
    }
}
