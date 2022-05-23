<?php

/**
 * Plugin Name: Import for Prices by User Role
*/

class WooImportProductsPlugin extends DisplayPlugin
{
    private $_ecommercePlugin;

    /**
     * @override
     */
    public function onInit()
    {
        parent::onInit();

        $this->_ecommercePlugin = FestiCoreStandalone::getActiveEcommercePluginInstance();

        $plugin = $this->_ecommercePlugin;

        if ($plugin) {
            $plugin->addActionListener(
                'wp_ajax_importProductData',
                array(&$this, 'onAjaxImportChunkOfProductsAction')
            );
        }
    } // end onInit

    public function displayImportPage($backend)
    {
        $importManager = new CsvWooProductsImporter($backend);

        $importManager->displayPage();
    } // end displayImportPage

    public function onAjaxImportChunkOfProductsAction()
    {
        $backend = $this->_ecommercePlugin->onBackendInit();

        $importManager = new CsvWooProductsImporter($backend);

        $importManager->onAjaxImportChunkOfProductsAction();
    } // end onAjaxImportChunkOfProductsAction
}