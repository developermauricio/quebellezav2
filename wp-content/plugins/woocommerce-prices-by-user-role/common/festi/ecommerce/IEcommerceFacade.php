<?php

interface IEcommerceFacade
{
    public function getHookNameForWritePanels();
    public function getHookNameForGetPrice();
    public function getProductType($product);
    public function getVariationProductID($product);
    public function isChildProduct($product);
    public function getProductParentID($product);
    public function getProductID($product);
    public function getPriceExcludingTax($product, $options);
    public function getPriceIncludingTax($product, $options);
    public function getPricesFromVariationProduct($product);
}
