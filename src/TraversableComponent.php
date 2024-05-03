<?php

namespace Battis\OpenAPI\Client;

use Iterator;
use Traversable;

abstract class TraversableComponent extends BaseComponent implements
    Traversable,
    Iterator
{
    private int $key = 0;

    abstract protected function getIterableProperty(): array;

    public function current(): mixed
    {
        return $this->getIterableProperty()[$this->key];
    }

    public function key(): mixed
    {
        return $this->key;
    }

    public function next(): void
    {
        $this->key++;
    }

    public function rewind(): void
    {
        $this->key = 0;
    }

    public function valid(): bool
    {
        return isset($this->getIterableProperty()[$this->key]);
    }
}
