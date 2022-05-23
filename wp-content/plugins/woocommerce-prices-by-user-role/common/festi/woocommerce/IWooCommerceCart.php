<?php

interface IWooCommerceCart
{
    public function &getCartInstance();
    public function getTotal();
    public function getSubtotal();
    public function getProducts();
}