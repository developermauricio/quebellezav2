<?php

/*
 * Need to compatibility with plugin Product Bundles
 * @link http://www.woothemes.com/products/product-bundles/
 */

class FestiWooCommerceBundleProduct extends FestiWooCommerceSimpleProduct
{
    public function isAvailableToDisplaySaleRange($product)
    {
        return !$this->_hasRolePriceForCurrentUser($product);
    } // end isAvailableToDisplaySaleRange
    
    private function _hasRolePriceForCurrentUser($product)
    {
        $listOfProducts = $this->adapter->getListOfProductsWithRolePrice();
        $idProduct = $this->getProductID($product);
        return in_array($idProduct, $listOfProducts);
    } // end _hasRolePriceForCurrentUser
    
    public function getFormattedPriceForSaleRange($product, $userPrice)
    {
        return wc_price($userPrice);
    } // end getFormattedPriceForSaleRange
}