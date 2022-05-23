<?php

class WooUserRolePricesApiFacade
{
    const API_FILTER_GET_PRODUCT = 'woocommerce_api_product_response';
    
    private $_plugin;
    
    public function __construct(&$engine)
    {
        $this->_plugin = $engine;
    } // end __construct
    
    public function init()
    {
        $this->_plugin->addFilterListener(
            static::API_FILTER_GET_PRODUCT,
            array($this, 'onWooCommerceApiGetProduct')
        );
    } // end init
    
    public function onWooCommerceApiGetProduct($product)
    {
        if ($product['type'] == 'variable') {
            foreach ($product['variations'] as &$variation) {
                $prices = $this->_plugin->getProductPrices($variation['id']);
                $variation['prices_by_user_roles'] = $prices;
            }
        } else {
            $prices = $this->_plugin->getProductPrices($product['id']);
            $product['prices_by_user_roles'] = $prices;
        }
        
        return $product;
    } // end onWooCommerceApiGetProduct
}