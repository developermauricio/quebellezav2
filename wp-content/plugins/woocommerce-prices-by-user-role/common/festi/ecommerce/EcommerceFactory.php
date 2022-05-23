<?php

class EcommerceFactory
{
    public static function &getInstance()
    {
        return WooCommerceFacade::getInstance();
    } // end &getInstance
}