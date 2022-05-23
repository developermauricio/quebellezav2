<?php

namespace core\util;

/**
 * Class PluginWrapper
 * @package core\util
 */
class PluginWrapper
{
    static private $_instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance == null) {
            // XXX: Dirty hack to fixed autocomplete problem in PHPStorm
            $className = "\core\util\PluginWrapper";
            self::$_instance = new $className();
        }

        return self::$_instance;
    } // end getInstance

    public function __get($name)
    {
        $pluginName = ucfirst($name);

        return \Core::getInstance()->getPluginInstance($pluginName);
    }
}