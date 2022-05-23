<?php

class WooCommerceButterflyAdapter extends EcommerceFacade
{
    public function getHookNameForWritePanels()
    {
        return "woocommerce_product_data_panels";
    } // end getHookNameForWritePanels
    
    public function getHookNameForGetPrice()
    {
        return "woocommerce_product_get_price";
    } // end getHookNameForGetPrice
    
    public function getHookNameForPriceFilter()
    {
        return "woocommerce_product_query";
    } // end getHookNameForPriceFilter
    
    public function getMethodNameForPriceFilter()
    {
        return 'onProductQueryResults';
    } // end getMethodNameForPriceFilter
       
    public function getProductType($product)
    {
        if (get_class($product) == 'stdClass') {
            return $product->product_type;
        }
        return $product->get_type();
    } // end getProductType
    
    public function getVariationProductID($product)
    {
        return $product->get_id();
    } // end getVariationProductID
    
    public function isChildProduct($product)
    {
        return (bool) $product->get_parent_id();
    } // end isChildProduct
    
    public function getProductParentID($product)
    {
        return $product->get_parent_id();
    } // end getProductParentID
    
    public function getProductID($product)
    {
        return $product->get_id();
    } // end getProductID
    
    public function getPriceExcludingTax($product, $options)
    {
        return wc_get_price_excluding_tax($product, $options);
    } // end getPriceExcludingTax
    
    public function getPriceIncludingTax($product, $options)
    {
        return wc_get_price_including_tax($product, $options);
    } // end getPriceIncludingTax
    
    public function getPricesFromVariationProduct($product)
    {
        $prices = array();
        $productIDs = $product->get_children();

        foreach ($productIDs as $idProduct) {
            $product = wc_get_product($idProduct);
            $prices[$idProduct] = $product->get_price();
        }
        
        return $prices;
    } // end getPricesFromVariationProduct

    public function getSubscriptionSignUpFee($product)
    {
        return $product->get_meta('_subscription_sign_up_fee');
    } // end getSubscriptionSignUpFee

    public function getSubscriptionPrice($product)
    {
        return $product->get_meta('_subscription_price');
    } // end getSubscriptionPrice

    public function setSalePrice($product, $salePrice)
    {
        return $product->set_sale_price($salePrice);
    } // end setSalePrice
    
    public function getProductsForRangeWidgetFilter()
    {
        $queryObject = get_queried_object();
        
        $productCategory = array();
        
        if ($this->_hasCategoryByQueryObject($queryObject)) {
            $productCategory = $queryObject->slug;
        }

        $args = array(
            'limit'       => -1,
            'post_status' => array('publish'),
            'type'        => array('simple', 'variation', 'variable'),
            'category'    => $productCategory,
            'visibility'  => 'visible',
        );
       
        $products = wc_get_products($args);
        
        return $products;
    } // end getProductsForRangeWidgetFilter
    
    private function _hasCategoryByQueryObject($queryObject)
    {
        return !empty($queryObject->term_id);
    } // end _hasCategoryByQueryObject
}