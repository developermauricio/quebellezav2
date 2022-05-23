<?php

abstract class EcommerceFacade implements IEcommerceFacade
{
    public function getHookNameForWritePanels()
    {
        throw new UnsupportableFacadeMethod();
    } // end getHookNameForWritePanels
    
    public function getHookNameForGetPrice()
    {
        throw new UnsupportableFacadeMethod();
    } // end getHookNameForGetPrice
       
    public function getProductType($product)
    {
        throw new UnsupportableFacadeMethod();
    } // end getProductType
    
    public function getVariationProductID($product)
    {
        throw new UnsupportableFacadeMethod();
    } // end getVariationProductID
    
    public function isChildProduct($product)
    {
        throw new UnsupportableFacadeMethod();
    } // end isChildProduct
    
    public function getProductParentID($product)
    {
        throw new UnsupportableFacadeMethod();
    } // end getProductParentID
    
    public function getProductID($product)
    {
        throw new UnsupportableFacadeMethod();
    } // end getProductID
    
    public function getPriceExcludingTax($product, $options)
    {
        throw new UnsupportableFacadeMethod();
    } // end getPriceExcludingTax
    
    public function getPriceIncludingTax($product, $options)
    {
        throw new UnsupportableFacadeMethod();
    } // end getPriceIncludingTax
    
    public function getPricesFromVariationProduct($product)
    {
        throw new UnsupportableFacadeMethod();
    } // end getPricesFromVariationProduct
}