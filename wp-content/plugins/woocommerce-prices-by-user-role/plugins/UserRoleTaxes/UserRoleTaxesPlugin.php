<?php

/**
 * Plugin Name: Taxes for Prices by User Role
*/

class UserRoleTaxesPlugin extends DisplayPlugin
{
    private $_ecommercePlugin;
    private $_ecommerceFacade;

    /**
     * @override
     */
    public function onInit()
    {
        parent::onInit();

        $this->_ecommercePlugin =
            FestiCoreStandalone::getActiveEcommercePluginInstance();

        $this->_ecommerceFacade = EcommerceFactory::getInstance();
    } // end __construct

    public function onInitTaxListeners()
    {
        $facade = EngineFacade::getInstance();

        $facade->addActionListener(
            'woocommerce_base_tax_rates',
            array($this, 'onUserRoleBaseTaxRates')
        );

        $facade->addActionListener(
            'woocommerce_matched_rates',
            array($this, 'onUserRoleMatchedRates'),
            10,
            2
        );
    } // end onInitTaxListeners

    public function onUserRoleBaseTaxRates($rate)
    {
        $taxClass = $this->_getUserRoleTaxClass();

        if (!$taxClass) {
            return $rate;
        }

        $facade = EcommerceFactory::getInstance();

        $args = array(
            'country' => $facade->getBaseCountry(),
            'state' => $facade->getBaseState(),
            'postcode' => $facade->getBasePostCode(),
            'city' => $facade->getBaseCity(),
            'tax_class' => $taxClass
        );

        return $facade->findTaxRates($args);
    } // end onUserRoleBaseTaxRates

    private function _getUserRoleTaxClass()
    {
        if (!$this->_ecommercePlugin->isRegisteredUser()) {
            return false;
        }

        if (!$this->_ecommercePlugin->isEnabledUserRoleTaxOptions()) {
            return false;
        }

        $facade = EcommerceFactory::getInstance();

        $settings = $this->_ecommercePlugin->getTaxByUserRoleOptions();

        if (!$settings) {
            return false;
        }

        $key = $settings['taxClass'];

        $taxClasses = $facade->getTaxClasses();

        $taxClass = false;

        $engineFacade = EngineFacade::getInstance();

        if ($key) {
            $taxClass = $taxClasses[$key];
            $taxClass = $engineFacade->doSanitizeTitle($taxClass);
        }

        return $taxClass;
    } // end _getUserRoleTaxClass

    public function onUserRoleMatchedRates($taxClass = '', $customer = null)
    {
        $taxClass = $this->_getUserRoleTaxClass();

        $facade = EcommerceFactory::getInstance();

        $location = $facade->getTaxLocation($taxClass, $customer);

        $matchedTaxRates = array();

        if ($this->_isTaxLocationFieldsExist($location)) {
            list($country, $state, $postcode, $city) = $location;

            $args = array(
                'country' => $country,
                'state' => $state,
                'postcode' => $postcode,
                'city' => $city,
                'tax_class' => $taxClass,
            );

            $matchedTaxRates = $facade->findTaxRates($args);
        }

        return $matchedTaxRates;
    } // end onUserRoleMatchedRates

    private function _isTaxLocationFieldsExist($location)
    {
        return sizeof($location) == 4;
    } //end _isTaxLocationFieldsExist

    public function doRestoreDefaultDisplayTaxValues()
    {
        $engineFacade = EngineFacade::getInstance();

        $ecommercePlugin = FestiCoreStandalone::getActiveEcommercePluginInstance();

        if (!$ecommercePlugin->isUserRoleDisplayTaxOptionExist()) {
            return false;
        }

        $options = $engineFacade->getOption(
            PRICE_BY_ROLE_TAX_DISPLAY_OPTIONS
        );

        $ecommerceFacade = EcommerceFactory::getInstance();

        list($shopHookName, $cartHookName)
            = $ecommerceFacade->getDisplayTaxHookNames();

        $engineFacade->updateOption(
            $shopHookName,
            $options[$shopHookName]
        );

        $engineFacade->updateOption(
            $cartHookName,
            $options[$cartHookName]
        );
    } // end doRestoreDefaultDisplayTaxValues

    public function onInitEcommerceSettingsTaxListener()
    {
        $facade = EngineFacade::getInstance();

        $facade->addActionListener(
            'woocommerce_settings_tax',
            array($this, 'doRestoreDefaultDisplayTaxValues')
        );
    } // end onInitEcommerceSettingsTaxListener
}