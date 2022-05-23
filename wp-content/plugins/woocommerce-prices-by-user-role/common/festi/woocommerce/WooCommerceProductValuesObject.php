<?php

class WooCommerceProductValuesObject
{
    private $_post;
    private $_product;

    const SALE_PRICE_KEY = "_sale_price";
    const REGULAR_PRICE_KEY = "_regular_price";
    const PRICE_KEY = "_price";
    
    public function __construct($post)
    {
        $this->_post = $post;
        
        $factory = new WC_Product_Factory();
        $this->_product = $factory->get_product($this->getID());
    } // end __construct
    
    public function getID()
    {
        return $this->_post->ID;
    } // end getID

    public function getType()
    {
        $facade = WooCommerceFacade::getInstance();

        $product = $this->_product;

        return $facade->getProductType($product);
    } // end getType
}