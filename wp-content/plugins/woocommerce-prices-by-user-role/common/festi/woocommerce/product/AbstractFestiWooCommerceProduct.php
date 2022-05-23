<?php

class AbstractFestiWooCommerceProduct
{
    protected $adapter;
    protected $productMinimalQuantity = 1;
    protected $minimalPricesCount = 1;
    protected $ecommerceFacade;
    protected $engineFacade;
    
    public function __construct($adapter)
    {
        $this->adapter = $adapter;
        $this->ecommerceFacade = EcommerceFactory::getInstance();
        $this->engineFacade = EngineFacade::getInstance();
    } // end __construct
    
    public function onInit()
    {
    } //end onInit
    
    public function getMaxProductPrice($product, $display)
    {
        throw new Exception('Undefined method'.__FUNCTION__);
    } // end getMaxProductPrice
    
    public function getMinProductPrice($product, $display)
    {
        throw new Exception('Undefined method'.__FUNCTION__);
    } // end getMinProductPrice
    
    public function getPriceRange($product)
    {
        return false;
    } // end getPriceRange

    public function getChildren($product)
    {
        $facade = $this->ecommerceFacade;

        return $facade->getVariationChildrenIDs($product);
    } // end getChildren
    
    public function getUserPrice($product, $display = false)
    {
        if (!$display) {
            return $product->get_price();
        }

        $facade = $this->ecommerceFacade;

        if ($facade->isDisplayPricesIncludeTax()) {
            return $facade->getPriceIncludingTax($product);
        }

        return $facade->getPriceExcludingTax($product);
    } // end getUserPrice
    
    protected function isPriceIncludeTax()
    {
        $taxDisplayMode = $this->getTaxDisplayMode();
        
        return $taxDisplayMode == 'incl';
    } // end isPriceIncludeTax
    
    protected function getTaxDisplayMode()
    {
        $engineFacade = EngineFacade::getInstance();
        
        return $engineFacade->getOption('woocommerce_tax_display_shop');
    } // end getTaxDisplayMode
    
    public function getRegularPrice($product, $display)
    {
        $price = $product->get_regular_price();
        
        if (!$display) {
            return $price;
        }
        
        $quantity = $this->productMinimalQuantity;
        
        $facade = &$this->ecommerceFacade;
        
        $options = array(
            'qty'   => $quantity,
            'price' => $price
        );
        
        if ($facade->isDisplayPricesIncludeTax()) {
            return $facade->getPriceIncludingTax($product, $options);
        }
        
        return $facade->getPriceExcludingTax($product, $options);
    } // end getRegularPrice
    
    public function isAvailableToDisplaySaleRange($product)
    {
        throw new Exception('Undefined method '.__FUNCTION__);
    } // end isAvailableToDisplaySaleRange
    
    public function getFormattedPriceForSaleRange($product, $userPrice)
    {
        throw new Exception('Undefined method '.__FUNCTION__);
    } // end getFormattedPriceForSaleRange
    
    public function getPriceSuffix($product, $price)
    {
        return $product->get_price_suffix($price);
    } // end getPriceSuffix
}