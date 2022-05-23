<?php

require_once __DIR__.'/IEngineCompatibility.php';

class FestiCoreStandalone
{
    const EVENT_ON_INIT = "core_standalone_init";

    private static $_instance = false;

    /**
     * Return true if instance has been init.
     *
     * @return bool
     */
    public static function isInit(): bool
    {
        return !empty(static::$_instance);
    } // end isInit

    public static function init()
    {
        if (static::$_instance) {
            throw new FestiCoreStandaloneException(
                'FestiCoreStandalone already defined.'
            );
        }
        
        self::_doInclude();
        
        static::$_instance = self::_getEngineCompatibilityInstance();

        $event = new FestiEvent(static::EVENT_ON_INIT);
        Core::getInstance()->dispatchEvent($event);
    } // end init

    public static function install($options = array())
    {
        if (!static::$_instance) {
            throw new FestiCoreStandaloneException(
                'FestiCoreStandalone hasn\'t inited. FestiCoreStandalone::init()'
            );
        }
        
        static::$_instance->install($options);
    } // end install
    
    public static function bind($options = array())
    {
        if (!static::$_instance) {
            throw new FestiCoreStandaloneException(
                'FestiCoreStandalone isn\'t init. Use FestiCoreStandalone::init'
            );
        }
        
        static::$_instance->bind($options);
        
    } // end bind
    
    private static function _doInclude()
    {
        if (!defined('FESTI_CORE_PATH')) {
            $msg = "Undefined constant the FESTI_CORE_PATH. ".
                   "You should put path to the Festi Core.";
            throw new FestiCoreStandaloneException($msg);
        }
        
        if (!defined('ENGINE_BASE_PATH')) {
            $msg = "Undefined constant the ENGINE_BASE_PATH. ".
                    "You should put path to current engine (CMS etc.).";
            throw new FestiCoreStandaloneException($msg);
        }
        
        ini_set(
            'include_path', 
            get_include_path().PATH_SEPARATOR.FESTI_CORE_PATH
        );
        
        require_once FESTI_CORE_PATH.'/bundle/Core.php';

        if (!class_exists("DataAccessObject")) {
            require_once FESTI_CORE_PATH.'/bundle/database/DataAccessObject.php';
        }
    } // end _include

    private static function _getEngineCompatibilityInstance()
    {
        if (!empty($GLOBALS['wp_version'])) {
            $ident = "wordpress";
        } else {
            throw new FestiCoreStandaloneException("Undefined Engine");
        }
        
        $path = __DIR__.DIRECTORY_SEPARATOR.$ident.DIRECTORY_SEPARATOR.
                'EngineCompatibility.php';
        
        require_once $path;
        
        return new EngineCompatibility();
    } // end _getEngineCompatibilityInstance

    private static function _isWordPressFestiPlugin()
    {
        return !empty($GLOBALS['className']) &&
               strpos($GLOBALS['className'], 'FestiPlugin') !== false;
    } // end _isWordPressFestiPlugin

    public static function getActiveEcommercePluginInstance()
    {
        $ecommercePlugin = null;

        if (static::_isWordPressFestiPlugin()) {

            $className = $GLOBALS['className'];

            $ecommercePlugin = $GLOBALS[$className];
        }

        return $ecommercePlugin;
    } // end getActiveEcommercePluginInstance
}

class FestiCoreStandaloneException extends Exception
{

}