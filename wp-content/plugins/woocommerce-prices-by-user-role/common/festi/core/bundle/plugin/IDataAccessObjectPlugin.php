<?php

namespace core\plugin;

interface IDataAccessObjectPlugin
{
    public function getObject(
        string $name = null, string $pluginName = null
    ): ?\IDataAccessObject;
}
