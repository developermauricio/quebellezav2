<?php

// @codingStandardsIgnoreStart

function get_price_by_role_instance()
{
    if (empty($GLOBALS['wooUserRolePricesFestiPlugin'])) {
        throw new Exception("Not found Price By User Role Plugin.");
    }
    
    return $GLOBALS['wooUserRolePricesFestiPlugin'];
}

function update_prices_by_roles($product_id, $prices)
{
    $instance = get_price_by_role_instance();
    return $instance->updateProductPrices($product_id, $prices);
}

function get_product_prices($product_id)
{
    $instance = get_price_by_role_instance();
    return $instance->getProductPrices($product_id);
}

function get_price_by_user_id($product_id, $user_id = false)
{
    $instance = get_price_by_role_instance();
    
    return $instance->getRolePrice($product_id, $user_id);
}

// @codingStandardsIgnoreEnd