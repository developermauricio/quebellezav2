<?php

/**
 * Top level abstraction for implement cross-platform compatibility with 
 * different CMS and eCommerce platforms. 
 * 
 * When we are using term Engine we will mean CMS or eCommerce platform.
 * 
 * @version 1.0
 */
abstract class EngineFacade extends FestiObject
{
    const FILTER_BACKEND_MENU_OPTIONS = 'onBackendMenuItems';

    const DEFAULT_FILE_SYSTEM_METHOD = 'direct';
    const FTP_FILE_SYSTEM_METHOD = 'ftpext';

    private static $_instance = null;

    /**
     * Returns an instance of EngineFacade for the current platform like 
     * WordPress, Magento etc.
     * 
     * @throws EngineFacadeException
     * @return EngineFacade
     */
    public static function &getInstance()
    {
        if (self::$_instance == null) {
            
            if (!empty($GLOBALS['wp_version'])) {
                self::$_instance = new WordpressFacade();
            } else {
                throw new EngineFacadeException('Undefined Engine');
            }
        }
    
        return self::$_instance;
    } // end getInstance
    
    /**
     * For creating an instance of EngineFacade you should use static 
     * method getInstance.
     * 
     * @throws EngineFacadeException
     */
    public function __construct()
    {
        if (isset(self::$_instance)) {
            $message = 'Instance already defined use EngineFacade::getInstance';
            throw new EngineFacadeException($message);
        }
    } // end __construct
    
    /**
     * Required method for an identifying the Engine.
     */
    abstract protected function getIdent();
    
    /**
     * Invoke action in the engine. For better understanding, 
     * you should look to Publish–subscribe pattern.
     * 
     * @param string $actionName
     * @return mixed
     */
    abstract public function dispatchAction($actionName);
    
    /**
     * Invoke filter in the engine. A filter is the same as an action 
     * only filter must return modified a value.
     * 
     * @param string $filterName
     * @param mixed &$value
     * @return mixed Modified $value
     */
    abstract public function dispatchFilter($filterName, &$value);
    
    abstract public function addActionListener(
        $actionName, $callbackMethod, $priority = 10, $acceptedArgs = 1
    );
    
    abstract public function addFilterListener(
        $filterName, $callbackMethod, $priority = 10, $acceptedArgs = 1
    );
    
    abstract public function getAttachmentsByPostID($postParent, $fileType);
    abstract public function getAbsolutePath($url);
    abstract public function getPluginData($pluginPath);
}

class EngineFacadeException extends Exception
{
}