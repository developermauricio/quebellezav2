<?php

class WooCommerceDaringDassieAdapter extends EcommerceFacade
{
    public function getHookNameForWritePanels()
    {
        return "woocommerce_product_write_panels";
    } // end getHookNameForWritePanels
    
    public function getHookNameForGetPrice()
    {
        return "woocommerce_get_price";
    } // end getHookNameForGetPrice
    
    public function getHookNameForPriceFilter()
    {
        return "woocommerce_price_filter_results";
    } // end getHookNameForPriceFilter
    
    public function getMethodNameForPriceFilter()
    {
        return 'onPriceFilterWidgetResults';
    } // end getMethodNameForPriceFilter
    
    public function getProductType($product)
    {
        return $product->product_type;
    } // end getProductType
    
    public function getVariationProductID($product)
    {
        return $product->variation_id;
    } // end getVariationProductID
    
    public function isChildProduct($product)
    {
        return isset($product->post->post_parent) 
               && $product->post->post_parent != false;
    } // end isChildProduct
    
    public function getProductParentID($product)
    {
        return $product->post->post_parent;
    } // end getProductParentID
    
    public function getProductID($product)
    {
        return $product->id;
    } // end getProductID
    
    public function getPriceExcludingTax($product, $options)
    {
        $qty = !empty($options['qty']) ? $options['qty'] : 1;
        $price = !empty($options['price']) ? $options['price'] : '';
        
        return $product->get_price_excluding_tax($qty, $price);
    }  // end getPriceExcludingTax
    
    public function getPriceIncludingTax($product, $options)
    {
        $qty = !empty($options['qty']) ? $options['qty'] : 1;
        $price = !empty($options['price']) ? $options['price'] : '';
        
        return $product->get_price_including_tax($qty, $price);
    } // getPriceIncludingTax
    
    public function getPricesFromVariationProduct($product)
    {
        $prices = array();
        $productIDs = $product->get_children();

        foreach ($productIDs as $idProduct) {
            $product = $product->get_child($idProduct);
            $prices[$idProduct] = $product->get_price();
        }
        
        return $prices;
    } // end getPricesFromVariationProduct

    public function getSubscriptionSignUpFee($product)
    {
        return $product->subscription_sign_up_fee;
    } // end getSubscriptionSignUpFee

    public function getSubscriptionPrice($product)
    {
        return $product->subscription_price;
    } // end getSubscriptionPrice

    public function setSalePrice($product, $salePrice)
    {
        return $product->sale_price = $salePrice;
    } // end setSalePrice
    
    public function getProductsForRangeWidgetFilter()
    {
        $facade = WooCommerceFacade::getInstance();
        $productsIDs = $facade->getProductsIDsForRangeWidgetFilter();
        
        $products = array();
        foreach ($productsIDs as $idProduct) {
            $wooFactory = new WC_Product_Factory();
            $products[] = $wooFactory->get_product($idProduct);
        }
        
        return $products;
    } // end getProductsForRangeWidgetFilter
}