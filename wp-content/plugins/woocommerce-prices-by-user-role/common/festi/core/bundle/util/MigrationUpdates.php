<?php

namespace core\util;

abstract class MigrationUpdates
{
    abstract protected function onStart() : void;

    public function apply() : void
    {
        $this->onStart();
    } // end apply
}