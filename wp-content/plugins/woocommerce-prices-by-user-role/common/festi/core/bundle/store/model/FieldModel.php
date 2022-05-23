<?php

abstract class FieldModel
{
    protected $storage;
    
    public function __construct($storage)
    {
        $this->storage = $storage;
    }
    
    abstract public function getAttributes();
    abstract public function hasOptions(): bool;
    abstract public function getOptions(): array;
}
