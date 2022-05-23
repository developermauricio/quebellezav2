<?php

class FestiWooCommerceSimpleProduct extends AbstractFestiWooCommerceProduct
{
    public function removeAddToCartButton()
    {
        $this->engineFacade->onRemoveAllActions(
            'woocommerce_simple_add_to_cart'
        );
        
        // Need to display stock status
        $this->adapter->addActionListener(
            'woocommerce_simple_add_to_cart',
            'onDisplayOnlyProductStockStatusAction'
        );
    } // end removeAddToCartButton
    
    public function getProductID($product)
    {
        return $this->ecommerceFacade->getProductID($product);
    } // end getProductID
    
    public function isAvailableToDisplaySavings($product)
    {
        return true;
    } // end isAvailableToDisplaySavings
    
    public function isAvailableToDisplaySaleRange($product)
    {
        $price = $this->adapter->getUserPrice($product);
        if ($price) {
            return false;
        }
        
        return true;
    } // end isAvailableToDisplaySaleRange
    
    public function getFormattedPriceForSaleRange($product, $userPrice)
    {
        return $userPrice;
    } // end getFormattedPriceForSaleRange
}