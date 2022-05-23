<?php

class WooCommerceCacheHelper
{
    public static function doRefreshPriceCache()
    {
        if (!self::_isExistsWoocommerceRefreshCacheMethod()) {
            return false;
        }

        WC_Cache_Helper::get_transient_version('product', true);
    } // end doRefreshPriceCache
    
    private static function _isExistsWooCommerceRefreshCacheMethod()
    {
        $className = 'WC_Cache_Helper';
        $methodName = 'get_transient_version';
        
        if (!class_exists($className)) {
            return false;
        }
        
        $method = array($className, $methodName);
        return is_callable($method);
    } // end _isExistsWooCommerceRefreshCacheMethod
}