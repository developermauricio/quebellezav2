<?php

class WpmlFacade
{
    private static $_instance = null;
    private $_isInstalled = null;
    
    public static function &getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end &getInstance
    
    public function __construct()
    {
         if (isset(self::$_instance)) {
            $message = 'Instance already defined ';
            $message .= 'use WpmlFacade::getInstance';
            throw new Exception($message);
         }
    } // end __construct
    
    public function isInstalled()
    {
         $pluginPath = 'woocommerce-multilingual/wpml-woocommerce.php';
         
         if ($this->_isInstalled === null) {
            $this->_isInstalled = $this->_isPluginActive($pluginPath);    
         }
         
         return $this->_isInstalled;
    } // end isInstalled

    private function _isPluginActive($pluginMainFilePath)
    {
        $result = false;

        $facade = EngineFacade::getInstance();

        if ($facade->isMultiSiteOptionOn()) {
            $activePlugins = $facade->getMainSiteOption(
                'active_sitewide_plugins'
            );
            $result =  array_key_exists($pluginMainFilePath, $activePlugins);
        }

        if ($result) {
            return true;
        }

        $activePlugins = $facade->getOption('active_plugins');

        return in_array($pluginMainFilePath, $activePlugins);
    } // end _isPluginActive
    
    public function getWooCommerceProductIDByPostID($idProduct)
    {
        $originalProductID = EngineFacade::getInstance()->dispatchFilter(
            'wpml_master_post_from_duplicate',
            $idProduct
        );
        
        return $originalProductID;
    } // end getWooCommerceProductIDByPostID
}