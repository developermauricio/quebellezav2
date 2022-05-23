<?php

/**
 * Plugin Name: Quantity discount for Prices by User Role
*/

class QuantityDiscountPlugin extends DisplayPlugin
{
    private $_ecommercePlugin;
    private $_ecommerceFacade;

    public static $cartItemsCount;

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

    public function getQuantityDiscountByUserRole($price)
    {
        $plugin = $this->_ecommercePlugin;

        $settings = $plugin->getSettings();
        $userRole = $plugin->getUserRole();

        $settings = $plugin->getPreparedQuantityDiscountOptions($settings);

        $key = SettingsWooUserRolePrices::QUANTITY_DISCOUNT_OPTION_KEY;

        if (!isset($settings[$key])) {
            return $price;
        }

        $quantityDiscount = $settings[$key];

        if (!$plugin->hasUserRoleInOptions(
            $userRole,
            $quantityDiscount
        )) {
            return $price;
        }

        $minValue = $quantityDiscount[$userRole]['minValue'];
        $maxValue = $quantityDiscount[$userRole]['maxValue'];
        $amount = $quantityDiscount[$userRole]['value'];

        if ($this->_isCartItemsCountBetweenQuantityDiscountRange(
            $minValue,
            $maxValue,
            $amount
        )) {
            $price = $this->_getPriceWithQuantityDiscount(
                $price,
                $amount,
                $plugin
            );
        }

        return $price;
    } // end getQuantityDiscountByUserRole

    private function _getPriceWithQuantityDiscount($price, $amount, $plugin)
    {
        $key = SettingsWooUserRolePrices::QUANTITY_DISCOUNT_OPTION_KEY;

        if ($plugin->isPercentDiscountType($key)) {
            $amount = $plugin->getAmountOfDiscountOrMarkUpInPercentage(
                $price,
                $amount
            );
        }

        $minimalPrice = PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE;
        $price = ($amount > $price) ? $minimalPrice : $price - $amount;

        $numberOfDecimals = $this->_ecommerceFacade->getNumberOfDecimals();

        if (!$numberOfDecimals) {
            $price = round($price);
        }

        return $price;
    } // end _getPriceWithQuantityDiscount

    private function _isCartItemsCountBetweenQuantityDiscountRange(
        $minValue,
        $maxValue,
        $amount
    )
    {
        return $amount &&
               self::$cartItemsCount &&
               self::$cartItemsCount >= $minValue &&
               self::$cartItemsCount <= $maxValue;
    } // end _isCartItemsCountBetweenQuantityDiscountRange
}