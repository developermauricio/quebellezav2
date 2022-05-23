<?php

class CompatibilityManagerWooUserRolePrices extends WooUserRolePricesFestiPlugin
{
    private $_message = array();
    
    protected function onInit()
    {
        if ($this->_isActivatedNotCompatiblePlugins()) {
            $this->addActionListener(
                'admin_notices',
                'onDisplayInfoAboutNotCompatiblePluginAction'
            );
        }
    } // end onInit
    
    private function _isActivatedNotCompatiblePlugins()
    {
        $plugins = $this->_getNotCompatiblePluginsList();

        $result = false;
        
        foreach ($plugins as $path => $name) {
            if ($this->isPluginActive($path)) {
                $message = 'WooCommerce Prices By User Role: ';
                $message .= 'Not compatible with "'.$name.'" ';
                $message .= 'Please disable "'.$name.'" ';
                $message .= 'for WooCommerce Prices By User Role correct work.';
                $this->_message[] = $message;
                $result = true;
            }
        }

        return $result;
    } // end _isActivatedNotCompatiblePlugins
    
    public function onDisplayInfoAboutNotCompatiblePluginAction()
    {
        if (!$this->_message) {
            return false;
        }
        
        foreach ($this->_message as $message) {
            $this->displayError($message);
        }
    } // end onDisplayInfoAboutNotCompatiblePluginAction
    
    private function _getNotCompatiblePluginsList()
    {
        $pluginsList = array();
        
        $path = 'woocommerce-composite-products/';
        $mainFile = 'woocommerce-composite-products.php';
        $name = 'Composite Products';
        
        $pluginsList[$path.$mainFile] = $name;
        
        $path = 'jck-woo-show-single-variations/';
        $mainFile = 'jck-woo-show-single-variations.php';
        $name = 'WooCommerce Show Single Variations';
        
        $pluginsList[$path.$mainFile] = $name;

        $path = 'r3df-dashboard-language-switcher/';
        $mainFile = 'r3df-dashboard-language-switcher.php';
        $name = 'R3DF - Dashboard Language Switcher';

        $pluginsList[$path.$mainFile] = $name;
        
        return $pluginsList;
    } // end _getNotCompatiblePluginsList
}