<?php

/**
 * Plugin Name: Products Export for Prices by User Role
 */

class UserRoleProductsExporterPlugin extends DisplayPlugin
{
    const PRICE_SUFFIX_KEY = '_price';
    const SALE_PRICE_SUFFIX_KEY = '_sale_price';
    private $_ecommercePlugin;
    private $_ecommerceFacade;
    private $_userRoles;
    private $_exportColumnNames;

    /**
     * @override
     */
    public function onInit()
    {
        parent::onInit();

        $this->_ecommercePlugin =
            FestiCoreStandalone::getActiveEcommercePluginInstance();

        $this->_ecommerceFacade = EcommerceFactory::getInstance();

        $this->_doPrepareUserRoles();
    } // end __construct

    private function _doPrepareUserRoles()
    {
        $userRoles = $this->_ecommercePlugin->getUserRoles();

        if (!$userRoles) {
            $userRoles = array();
        }

        foreach ($userRoles as $key => $role) {
            $userRoles[$key] = $role['name'];
        }

        $this->_userRoles = $userRoles;
    } // end onInitDefaultExportNames

    public function onInitDefaultExportNames()
    {
        $facade = $this->_ecommerceFacade;

        $this->_exportColumnNames = $facade->getDefaultColumnNamesForExport();

        $facade = EngineFacade::getInstance();

        $facade->addActionListener(
            'woocommerce_product_export_product_default_columns',
            array($this, 'onExportDefaultColumns')
        );

        $facade->addActionListener(
            'woocommerce_product_export_skip_meta_keys',
            array($this, 'onExportSkipMetaKeys'),
            10,
            2
        );

        $this->onInitExportColumnFilters();
    } // end onExportDefaultColumns

    public function onInitExportColumnFilters()
    {
        $userRoles = $this->_userRoles;

        $exportColumnHookName = 'woocommerce_product_export_product_column_';

        $facade = EngineFacade::getInstance();

        $method = array($this, 'onFilterExportColumnValue');

        foreach ($userRoles as $roleKey => $roleName) {

            $facade->addFilterListener(
                $exportColumnHookName . $roleKey . static::PRICE_SUFFIX_KEY,
                $method,
                10,
                3
            );

            $facade->addFilterListener(
                $exportColumnHookName . $roleKey . static::SALE_PRICE_SUFFIX_KEY,
                $method,
                10,
                3
            );
        }
    } // end onFilterExportColumnValue

    public function onExportDefaultColumns()
    {
        $columnNames = $this->_exportColumnNames;

        $userRoles = $this->_userRoles;

        $languageDomain = $this->_ecommercePlugin->languageDomain;

        foreach ($userRoles as $key => $roleName) {
            $priceSuffix = __('Price', $languageDomain);
            $salePriceSuffix = __('Sale Price', $languageDomain);

            $keyPrice = $key . static::PRICE_SUFFIX_KEY;
            $columnNames[$keyPrice] = "{$roleName} {$priceSuffix}";
            $keySalePrice = $key . static::SALE_PRICE_SUFFIX_KEY;
            $columnNames[$keySalePrice] = "{$roleName} {$salePriceSuffix}";
        }

        return $columnNames;
    } // end _doPrepareUserRoles

    public function onFilterExportColumnValue($value, $product, $columnName)
    {
        $facade = $this->_ecommerceFacade;

        $idProduct = $facade->getProductID($product);

        $userRole = $this->_getUserRoleFromExportColumn($columnName);

        $priceList = $this->_ecommercePlugin->getProductPrices($idProduct);

        if (!$this->_ecommercePlugin->hasRolePriceInProductOptions($priceList, $userRole)) {
            return false;
        };

        if ($this->_isSalePriceColumn($columnName)) {
            $priceList = $priceList['salePrice'];
        }

        return $priceList[$userRole];
    } // end _getUserRoleFromExportColumn

    private function _getUserRoleFromExportColumn($name)
    {
        $search = array(
            static::SALE_PRICE_SUFFIX_KEY,
            static::PRICE_SUFFIX_KEY
        );

        return str_replace($search, '', $name);
    } // end _isSalePriceColumn

    private function _isSalePriceColumn($name)
    {
        return strpos($name, static::SALE_PRICE_SUFFIX_KEY) !== false;
    } // end onInitExportColumnFilters

    public function onExportSkipMetaKeys($metaKeys, $product)
    {
        $metaKeys[] = PRICE_BY_ROLE_PRICE_META_KEY;

        return $metaKeys;
    } // end onExportSkipMetaKeys
}