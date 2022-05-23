<?php

class ModulesSwitchListener
{
    private $_object;
    private $_pluginsPath;
    private $_ecommercePlugin;

    CONST CORE_PRICES_PLUGIN = 'PriceByUserRoleCore';
    CONST TAXES_PLUGIN = 'UserRoleTaxes';
    CONST IMPORT_PRODUCTS_PLUGIN = 'WooImportProducts';
    CONST PRODUCTS_EXPORT_PLUGIN = 'UserRoleProductsExporter';
    CONST QUANTITY_DISCOUNT_PLUGIN = 'QuantityDiscount';

    public function __construct(Core $core, $object, $ecommercePlugin)
    {
        $this->_object = $object;

        $core->addEventListener(
            EngineFacade::FILTER_BACKEND_MENU_OPTIONS,
            array($this, 'onBackendMenuItems')
        );

        $this->_pluginsPath = $core->getOption('plugins_path');
        $this->_ecommercePlugin = $ecommercePlugin;
    } // end __construct

    public function onBackendMenuItems(FestiEvent &$event)
    {
        $menuItems = &$event->getTarget();

        if ($this->_isModuleActive(static::TAXES_PLUGIN)) {
            $menuItems['taxesTab'] = 'Tax Options';
        }

        if ($this->_isModuleActive(static::QUANTITY_DISCOUNT_PLUGIN)) {
            $menuItems['quantityDiscountTab'] = 'Quantity Discount';
        }

        if ($this->_isModuleActive(static::IMPORT_PRODUCTS_PLUGIN)) {
            $menuItems['importProductTab'] = 'Import Products';
        }

        $menuItems['registrationTab'] = 'Product License';
        $menuItems['extensionsTab'] = 'Extensions';
    } // end onBackendMenuItems

    private function _isModuleActive($name)
    {
        return $this->_ecommercePlugin->isModuleExist($name) &&
               $this->_object->isModuleActive($name);
    } // end _isModuleActive
}