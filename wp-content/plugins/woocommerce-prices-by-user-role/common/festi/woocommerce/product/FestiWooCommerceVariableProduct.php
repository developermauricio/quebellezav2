<?php

class FestiWooCommerceVariableProduct extends AbstractFestiWooCommerceProduct
{
    public function removeAddToCartButton()
    {
        $this->adapter->addActionListener(
            'woocommerce_after_single_variation',
            'removeVariableAddToCartLinkAction'
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
            
            $price = $this->adapter->getUserPrice($product, $display);

            $priceList[] = $price;
        }
        
        return $priceList;
    } //end getAllPriceOfChildren

    public function isAvailableToDisplaySaleRange($product)
    {
        $children = $this->getChildren($product);
        
        if (!$children) {
            return true;
        }

        return $this->_isAllChildsPriceRegularOrSale($children);
    } // end isAvailableToDisplaySaleRange
    
    private function _getChildsWithRolePrice($children)
    {
        $listOfProducts = $this->adapter->getListOfProductsWithRolePrice();

        $childs = array();
        
        foreach ($children as $childrenId) {
            if (!in_array($childrenId, $listOfProducts)) {
                continue;
            }
            
            $childs[] = $childrenId;
        }
        
        return $childs;
    } // end _getChildsWithRolePrice
    
    private function _isAllChildsPriceRegularOrSale($children)
    {
        $childsWithRolePrice = $this->_getChildsWithRolePrice($children);
        
        return count($childsWithRolePrice) == false;
    } // end _isAllChildsPriceRegularOrSale
    
    public function getFormattedPriceForSaleRange($product, $userPrice)
    {
        return $userPrice;
    } // end getFormattedPriceForSaleRange
    
    public function getUserPrice($product, $display = false)
    {
        $prices = $this->getAllPriceOfChildren($product, $display);
        if (!$prices) {
            return false;
        }
        
        $prices = array_unique($prices);
        
        $this->doRemoveEmptyPrices($prices);

        if (!$this->_hasEqualPricesInChildProducts($prices)) {
            return false;
        }
        
        $price = current($prices);
        
        if (!$display) {
            return $price;
        }
        
        $quantity = $this->productMinimalQuantity;
        $facade = &$this->ecommerceFacade;

        if ($facade->isDisplayPricesIncludeTax()) {
            return $facade->getPriceIncludingTax($product, $quantity, $price);
        }

        return $facade->getPriceExcludingTax($product, $quantity, $price);
    } // end getUserPrice
    
    public function getUserPrices($product, $display = false)
    {
        $prices = $this->getAllPriceOfChildren($product, $display);
        if (!$prices) {
            return false;
        }
        $prices = array_unique($prices);
        
        $this->doRemoveEmptyPrices($prices);

        return $prices;
    } // end getUserPrices
    
    private function _hasEqualPricesInChildProducts($prices)
    {
        return count($prices) == $this->minimalPricesCount;
    } // end _hasEqualPricesInChildProducts
    
    protected function doRemoveEmptyPrices(&$prices)
    {
        while (($key = array_search(false, $prices)) !== false) {
            unset($prices[$key]);
        } 
    } // end doRemoveEmptyPrices
    
    public function getRegularPrice($product, $display)
    {
        $price = $this->_getPrice($product, $display);
        
        if (!$display) {
            return $price;
        }
        
        $quantity = $this->productMinimalQuantity;
        $facade = &$this->ecommerceFacade;

        if ($facade->isDisplayPricesIncludeTax()) {
            return $facade->getPriceIncludingTax($product, $quantity, $price);
        }
        
        return $facade->getPriceExcludingTax($product, $quantity, $price);
    } // end getRegularPrice
    
    private function _getPrice($product, $display)
    {
        if ($this->_isExistsMethodVariationPrices($product)) {
                
            $prices = $product->get_variation_prices($display);
            
            if ($this->_isExistsRegularPriceKeyInPrices($prices)) {
                return current($prices['regular_price']);
            }
        }
        
        return $product->get_variation_price('min', $display);
    } //end _getPrice
    
    private function _isExistsMethodVariationPrices($product)
    {
        return method_exists($product, 'get_variation_prices');
    } // end _isExistsMethodVariationPrices
    
    private function _isExistsRegularPriceKeyInPrices($prices)
    {
        return array_key_exists('regular_price', $prices);
    } // end _isExistsRegularPriceKeyInPrices
}