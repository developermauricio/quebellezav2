<?php

class UserRolePriceFacade implements IUserRolePriceFacade
{
    private static $_instance = null;
    
    const INDEX_PRODUCT_WITH_MINIMAL_PRICE = 0;
    const PRICE_FILTER_KEY = 'price_filter';
    const MIN_PRICE_KEY = 'min_price';
    const MAX_PRICE_KEY = 'max_price';

    public static function &getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end &getInstance
    
    public function __construct()
    {
         if (isset(self::$_instance)) {
            $message = 'Instance already defined ';
            $message .= 'use UserRolePriceFacade::getInstance';
            throw new Exception($message);
         }
    } // end __construct
    
    private function _getRolePriceForWooCommercePriceSuffix(
        $product,
        $userRole,
        $engine,
        $engineFacade
    )
    {
        $idProduct = $this->_getVariationWithMinimalPrice(
            $product,
            $engineFacade
        );

        $minUserPrices = $this->_getUserRolePricesForProduct(
            $idProduct,
            $engine
        );

        if (!$minUserPrices) {
            $minUserPrices = array();
        }

        if ($this->_isSalePriceForUserRoleSet($minUserPrices, $userRole)) {
            return $this->_getSalePriceForUserRole($minUserPrices, $userRole);
        }
        
        return $this->_getRegularPriceForUserRole(
            $minUserPrices,
            $userRole
        );
    } // end _getRolePriceForWooCommercePriceSuffix

    private function _getRegularPriceForUserRole($priceList, $userRole)
    {
        foreach ($priceList as $role => $price) {
            if ($role == $userRole) {
                return $price;
            }
        }

        return false;
    } // end _getRegularPriceForUserRole

    private function _getSalePriceForUserRole($priceList, $userRole)
    {
        foreach ($priceList['salePrice'] as $role => $price) {
            if ($role == $userRole) {
                return $price;
            }
        }

        return false;
    } // end _getSalePriceForUserRole

    private function _getUserRolePricesForProduct($idProduct, $engine)
    {
        return $engine->getMetaOptions(
            $idProduct,
            PRICE_BY_ROLE_PRICE_META_KEY
        );
    } // end _getUserRolePricesForProduct

    private function _getVariationWithMinimalPrice($product, $engineFacade)
    {
        $result = $engineFacade->getPricesFromVariationProduct($product);

        if (!empty($result)) {
            $result = array_keys($result, min($result));

            return $result[static::INDEX_PRODUCT_WITH_MINIMAL_PRICE];
        }

        return false;
    } // end _getVariationWithMinimalPrice

    private function _isSalePriceForUserRoleSet($priceList, $userRole)
    {
        return array_key_exists('salePrice', $priceList) &&
               !empty($priceList['salePrice'][$userRole]);
    } // end _isSalePriceForUserRoleSet

    private function _isUserRolePriceFilterRange($metaQuery, $frontend)
    {
        return array_key_exists(static::PRICE_FILTER_KEY, $metaQuery) &&
               is_array($metaQuery[static::PRICE_FILTER_KEY]['value']) &&
               $frontend->getUserRole();
    } // end _isUserRolePriceFilterRange

    private function _isRolePriceBetweenMinMax($rolePrice, $min, $max)
    {
        return $rolePrice && $rolePrice <= $max && $rolePrice >= $min;
    } // end _isRolePriceBetweenMinMax

    private function _getQueryClassName($query)
    {
        if (!is_object($query)) {
            return false;
        }

        return get_class($query);
    } // end _getQueryClassName

    private function _getProductsPriceFilterRangeByRole(
        $frontend,
        $metaQuery
    )
    {
        if (!$this->_isUserRolePriceFilterRange($metaQuery, $frontend)) {
            return $metaQuery;
        }

        $facade = EcommerceFactory::getInstance();

        list($min, $max) = $metaQuery[static::PRICE_FILTER_KEY]['value'];

        $rolePrices = $frontend->getRolePricesForWidgetFilter();

        foreach ($rolePrices as $idProduct => $price) {
            if (!$this->_isRolePriceBetweenMinMax($price, $min, $max)) {
                continue;
            }
            $product = $frontend->createProductInstance($idProduct);
            $regularPrice = $facade->getRegularPrice($product);
            if ($regularPrice == 0 && $min > 0) {
                continue;
            }
            $regularPrices[] = $regularPrice;
        }

        if (!empty($regularPrices)) {
            $regularPriceRange[] = min($regularPrices);
            $regularPriceRange[] = max($regularPrices);
            $metaQuery[static::PRICE_FILTER_KEY]['value'] = $regularPriceRange;
        } else {
            unset($metaQuery[static::PRICE_FILTER_KEY]['compare']);
        }

        return $metaQuery;
    } // end _getProductsPriceFilterRangeByRole

    private function _isEcommerceQuery($engineQuery, $ecommerceQuery)
    {
        $engineQueryClass = $this->_getQueryClassName($engineQuery);
        $ecommerceQueryClass = $this->_getQueryClassName($ecommerceQuery);

        $ecommerceFacade = EcommerceFactory::getInstance();
        $ecommerceClass = get_class($ecommerceFacade);

        $engineFacade = EngineFacade::getInstance();
        $engineClass = get_class($engineFacade);

        return $ecommerceQueryClass == $ecommerceClass::QUERY_CLASS_NAME &&
               $engineQueryClass == $engineClass::QUERY_CLASS_NAME;
    } // end _isEcommerceQuery

    public function updatePriceFilterQueryForProductsSearch(
        $engineQuery,
        $ecommerceQuery,
        $frontend
    )
    {
        if ($this->_isEcommerceQuery($engineQuery, $ecommerceQuery)) {

            $ecommerceFacade = EcommerceFactory::getInstance();

            $metaQuery = $ecommerceFacade->getMetaQuery($ecommerceQuery);

            if (!$metaQuery) {
                $metaQuery = $this->_setPreparePriceFilter(
                    $frontend,
                    $metaQuery
                );
            }

            $priceRange = $this->_getProductsPriceFilterRangeByRole(
                $frontend,
                $metaQuery
            );

            $facade = EngineFacade::getInstance();
            $engineClassName = get_class($facade);

            $engineQuery->set($engineClassName::META_QUERY_KEY, $priceRange);
        }
    } // end updatePriceFilterQueryForProductsSearch

    public function getPriceByRolePriceFilter($price, $product, $engine)
    {
        $product = $engine->getProductNewInstance($product);

        if (!$engine->isRegisteredUser()) {
            return $price;
        }

        if (!$engine->hasUserRoleInActivePluginRoles()) {
            return $engine->getPriceWithFixedFloat($price);
        }

        $newPrice = $this->_getRolePriceOrSale($product, $engine);

        if ($newPrice) {
            return $engine->getPriceWithFixedFloat($newPrice);
        }

        $facade = EcommerceFactory::getInstance();

        if (
            $engine->isVariableTypeProduct($product) &&
            $facade->isEnabledTaxCalculation() &&
            $facade->hasPriceDisplaySuffixPriceIncludingOrExcludingTax()
        ) {
            return $this->_getRolePriceForWooCommercePriceSuffix(
                $product,
                $engine->getUserRole(),
                $engine,
                $facade
            );
        }

        return $price;
    } // end getPriceByRolePriceFilter

    private function _getRolePriceOrSale($product, $engine)
    {
        $salePrice = $engine->getSalePrice($product);

        if ($salePrice && $salePrice > 0) {
            return $salePrice;
        }

        return $engine->getPrice($product);
    } // end _getRolePriceOrSale

    private function _hasPriceFilterRangeInRequest($frontend)
    {
        return $frontend->getUserRole() &&
               array_key_exists(static::MIN_PRICE_KEY, $_GET) &&
               $_GET[static::MIN_PRICE_KEY] &&
               array_key_exists(static::MAX_PRICE_KEY, $_GET) &&
               $_GET[static::MAX_PRICE_KEY];
    } // end _hasPriceFilterRangeInRequest

    private function _setPreparePriceFilter($frontend, $metaQuery)
    {
        if (!$this->_hasPriceFilterRangeInRequest($frontend)) {
            return $metaQuery;
        }

        $minPrice = floatval($_GET[static::MIN_PRICE_KEY]);
        $maxPrice = floatval($_GET[static::MAX_PRICE_KEY]);

        $metaQuery[static::PRICE_FILTER_KEY] = array(
            'key' => WooCommerceProductValuesObject::PRICE_KEY,
            'value' => array($minPrice, $maxPrice),
            'compare' => 'BETWEEN',
            'type' => 'DECIMAL(10,2)',
            static::PRICE_FILTER_KEY => true
        );

        return $metaQuery;
    } // end _setPreparePriceFilter
}