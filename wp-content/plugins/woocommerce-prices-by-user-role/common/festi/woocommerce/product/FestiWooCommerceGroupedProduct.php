<?php

class FestiWooCommerceGroupedProduct extends AbstractFestiWooCommerceProduct
{
    public function removeAddToCartButton()
    {
        $this->engineFacade->onRemoveAllActions(
            'woocommerce_grouped_add_to_cart'
        );
        
        $this->adapter->addActionListener(
            'woocommerce_grouped_add_to_cart',
            'removeGroupedAddToCartLinkAction'
        );
    } // end removeAddToCartButton
    
    public function getProductID($product)
    {
        return $this->ecommerceFacade->getProductID($product);
    } // end getProductID
    
    public function isAvailableToDisplaySavings($product)
    {
        return $this->adapter->isProductPage();
    } // end isAvailableToDisplaySavings
    
    public function getMaxProductPrice($product, $display)
    {
        $priceList = $this->getAllPriceOfChildren($product, $display);
        
        return ($priceList) ? max($priceList) : false;
    } // end getMaxProductPrice
    
    public function getMinProductPrice($product, $display)
    {
        $priceList = $this->getAllPriceOfChildren($product, $display);
        
        return ($priceList) ? min($priceList) : false;
    } // end getMinProductPrice
    
    protected function getAllPriceOfChildren($product, $display)
    {
        $children = $this->getChildren($product);

        if (!$children) {
            return false;
        }
        
        $priceList = array();

        foreach ($children as $childrenId) {
            $product = $this->adapter->createProductInstance($childrenId);
            
            $price = $this->getUserPrice($product, $display);
            
            if (!$price) {
                continue;
            }

            $priceList[] = $price;
        }
        
        return $priceList;
    } //end getAllPriceOfChildren
    
    public function isAvailableToDisplaySaleRange($product)
    {
        return true;
    } // end isAvailableToDisplaySaleRange
}