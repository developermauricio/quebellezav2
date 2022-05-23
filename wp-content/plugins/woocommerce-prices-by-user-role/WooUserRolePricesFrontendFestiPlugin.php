<?php
require_once __DIR__.'/common/autoload.php';

class WooUserRolePricesFrontendFestiPlugin extends WooUserRolePricesFestiPlugin
{
    const TYPE_PRODUCT_SIMPLE = 'simple';
    const TYPE_PRICE_USER = 'user';
    const TYPE_PRICE_REGULAR = 'regular';

    public $products;
    public $mainTotals;
    public $subscriptionTax;
    public $subscriptionCount;
    public $subscribeProduct;
    public $subscriptionFee;
    public $subscriptionKey;

    protected $eachProductId = 0;
    protected $removeLoopList = array();
    protected $textInsteadPrices;
    protected $mainProductOnPage = 0;
    protected $subscriptionPrice;

    private static $_rangePrices = array();
    private static $_rolePrices = array();

    protected function onInit()
    {
        WooUserRoleModule::init($this);

        if ($this->isMaxExecutionTimeLowerThanConstant()) {
            ini_set(
                'max_execution_time',
                WooUserRolePricesFestiPlugin::MAX_EXECUTION_TIME
            );
        }

        $this->addActionListener('wp_loaded', 'onInitPriceFiltersAction');
        $this->addActionListener('wp', 'onHiddenAndRemoveAction');
        $this->addActionListener('wp_print_styles', 'onInitCssAction');
        $this->addActionListener('wp_enqueue_scripts', 'onInitJsAction');

        $this->addFilterListener(
            'woocommerce_get_variation_prices_hash',
            'onAppendDataToVariationPriceHashGeneratorFilter',
            10,
            3
        );

        $this->_onInitApi();
        
        $this->ecommerceFacade = EcommerceFactory::getInstance();
    } // end onInit

    private function _onInitApi()
    {
        $apiFacade = new WooUserRolePricesApiFacade($this);
        $apiFacade->init();
    } // end _onInitApi
    
    public function onAppendDataToVariationPriceHashGeneratorFilter(
        $productData, $product, $display
    )
    {
        $roles = $this->getAllUserRoles();
        
        $value = PRICE_BY_ROLE_HASH_GENERATOR_VALUE_FOR_UNREGISTRED_USER;
        $data = (!$roles) ? array($value) : $roles;

        $productData[PRICE_BY_ROLE_HASH_GENERATOR_KEY] = $data;
        
        return $productData;
    } // end onAppendDataToVariationPriceHashGeneratorFilter
    
    public function onGetTextInsteadOfEmptyPrice()
    {
        return WooUserRoleModule::get('EmptyPrice')
            ->onGetTextInsteadOfEmptyPrice();
    } // end onGetTextInsteadOfEmptyPrice
    
    public function onInitPriceFiltersAction()
    {
        static::$userRole = $this->getUserRole();

        if (static::$userRole) {
            $this->_setCartItemsCount();
        }

        $this->products = $this->getProductsInstances();

        $facade = EcommerceFactory::getInstance();

        if (!static::$userRole && $facade->isEnabledTaxCalculation()) {
            $this->doRestoreDefaultDisplayTaxValues();
        }

        if ($this->isEnabledUserRoleTaxOptions()) {
            $this->_setPrepareDisplayUserRoleTaxes();
        }

        $this->addActionListener('wp', 'onInitMainActionByProductID');
        
        if ($this->hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            $this->onFilterPriceByDiscountOrMarkup();   
        } else {
            $this->onFilterPriceByRolePrice();
        }

        $this->onDisplayCustomerSavings();

        $this->onFilterPriceRanges();
    } // end onInitPriceFiltersAction
    
    public function onHiddenAndRemoveAction()
    {
        WooUserRoleModule::get('HidePrice')->onHideAddToCartButton();
        WooUserRoleModule::get('HidePrice')->onHidePrice();
        WooUserRoleModule::get('EmptyPrice')->onHideEmptyPrice();

        $this->addFilterListener(
            'get_terms',
            'onHideSubcategoryTerms',
            10,
            3
        );
    } // end onHiddenAndRemoveAction
    
    public function onInitMainActionByProductID()
    {
        $this->getMainProductID();
    } // end onInitMainActionByProductID
    
    protected function onFilterPriceRanges()
    {
        $hookManager = new WooUserRolePricesFrontendHookManager($this);
        
        $hookManager->onInit();
    } // end onFilterPriceRanges
    
    public function onShowVariationPriceForCustomerSavings($isShow)
    {
        return WooUserRoleModule::get('PriceSavings')
            ->onShowVariationPriceForCustomerSavings($isShow);
    } // end onShowVariationPriceForCustomerSavings
    
    public function onRemovePriceForUnregisteredUsers($price, $product)
    {
        return WooUserRoleModule::get('HidePrice')
            ->onRemovePriceForUnregisteredUsers($price, $product);
    } // end onRemovePriceForUnregisteredUsers
    
    public function onSalePriceCheck($isSale, $product)
    {
        $id = $this->ecommerceFacade->getProductID($product);
        
        if (array_key_exists($id, static::$isSalePrices)) {
            return static::$isSalePrices[$id];
        }

        $isSalePrice = $this->_isSalePriceCheck($isSale, $product);

        static::$isSalePrices[$id] = $isSalePrice;
        
        return $isSalePrice;
    } // end onSalePriceCheck
    
    private function _isSalePriceCheck($isSale, $product)
    {
        if ($this->_hasSalePriceByUnregisteredUser($product)) {
            return true;
        }
        
        if ($this->_hasSalePriceForUserRole($product)) {
            return $this->_isRoleSalePriceLowerThenRolePrice($product);
        }
        
        if ($this->_hasRolePriceBySimpleProduct($product)) {
            return false;
        }
        
        if ($this->hasRoleSalePriceByVariableProduct($product)) {
            return true;
        }
        
        return $isSale;
    } // end _isSalePriceCheck
    
    private function _hasSalePriceByDiscountOrMarkUpProduct($product)
    {
        return $this->isEnableBothRegularSalePriceSetting() && 
               $this->hasSalePrice($product);
    } // end _hasSalePriceByDiscountOrMarkUpProduct
    
    private function _hasSalePriceByUnregisteredUser($product)
    {
        if (!$this->_isSimpleTypeProduct($product)) {
            return false;
        }
        
        if ($this->isRegisteredUser()) {
            return false;
        }
        
        return (bool) $product->get_sale_price();
    } // end _hasSalePriceByUnregisteredUser
    
    public function onHideSelectorSaleForProduct($content, $post, $product)
    {
        if ($this->hasRoleSalePriceByVariableProduct($product)) {
            return $content;
        }
        
        if ($this->isEnableBothRegularSalePriceSetting()) {
            return $content;
        }
        if ($this->_hasRolePriceByVariableProduct($product)) {
            return false;
        }
        
        if ($this->_hasRolePriceBySimpleProduct($product)) {
            return false;
        }
        
        if ($this->isDiscountOrMarkupEnabledByRole(static::$userRole)) {
            if ($this->_hasSalePriceByDiscountOrMarkUpProduct($product)) {
                return $content;
            }
            return false;
        }
        
        return $content;
    } // end onHideSelectorSaleForProduct
    
    private function _hasRolePriceBySimpleProduct($product)
    {
        if (!$this->_isSimpleTypeProduct($product)) {
            return false;
        }
        $idProduct = $this->ecommerceFacade->getProductID($product);

        $prices = $this->getProductPrices($idProduct);

        if ($this->_hasSalePriceByUserRole($prices)) {
            return false;
        }
        
        if ($this->_hasPriceByUserRole($prices)) {
            return true;
        }
       
        return false;
    } // end _hasRolePriceBySimpleProduct
    
    private function _hasSalePriceByUserRole($prices)
    {
        $userRole = static::$userRole;

        return $userRole &&
               array_key_exists('salePrice', $prices) &&
               array_key_exists($userRole, $prices['salePrice']) &&
               $prices['salePrice'][$userRole];
    } // _hasSalePriceByUserRole

    private function _isSimpleTypeProduct($product)
    {
        $facade = &$this->ecommerceFacade;

        return $facade->getProductType($product) == static::TYPE_PRODUCT_SIMPLE;
    } // _isSimpleTypeProduct
    
    private function _hasRolePriceByVariableProduct($product)
    {
        if (!$this->isVariableTypeProduct($product)) {
            return false;
        }

        $facade = $this->ecommerceFacade;

        $productsIDs = $facade->getVariationChildrenIDs($product);

        $flag = false;
        
        if ($productsIDs) {
            foreach ($productsIDs as $id) {
                $prices = $this->getProductPrices($id);
                
                if ($this->_hasPriceByUserRole($prices)) {
                    $flag = true;
                    break;
                }
            }
        }
        
        return $flag;
    } // end _hasRolePriceByVariableProduct
    
    private function _hasPriceByUserRole($prices)
    {
        $userRole = static::$userRole;

        return $userRole &&
               array_key_exists($userRole, $prices) &&
               $prices[$userRole];
    } // end _hasPriceByUserRole

    public function onProductQueryResults($engineQuery , $ecommereceQuery)
    {
        $facade = UserRolePriceFacade::getInstance();

        $facade->updatePriceFilterQueryForProductsSearch(
            $engineQuery,
            $ecommereceQuery,
            $this
        );
    } // end onProductQueryResults

    public function onPriceFilterWidgetResults($products, $min, $max)
    {
        if (!static::$userRole) {
            return $products;
        }
        
        $rolePrices = $this->getRolePricesForWidgetFilter();
        
        $productIDs = array();
        foreach ($rolePrices as $idProduct => $price) {
            if ($this->_isRolePriceBetweenMinMax($price, $min, $max)) {
                $productIDs[] = $idProduct;
            }
        }
        
        $products = $this->ecommerceFacade->getProductsByIDsForWidgetFilter(
            $productIDs
        );
       
        $products = $this->_getPrepareProductsByFilter($products);
            
        return $products;
    } // end onPriceFilterWidgetResults
    
    private function _isRolePriceBetweenMinMax($rolePrice, $min, $max)
    {
        return $rolePrice && $rolePrice <= $max && $rolePrice >= $min;
    } // end _isRolePriceBetweenMinMax
    
    private function _getPrepareProductsByFilter($products)
    {
        foreach ($products as $key => $product) {
            $products[$key] = (object) $product;
        }
        return $products;
    } // end _getPrepareProductsByFilter
    
    public function onPriceFilterWidgetMaxAmount($max)
    {
        if (static::$userRole) {
            $resultPrices = $this->getRolePricesForWidgetFilter();
            
            if ($resultPrices) {
                return max($resultPrices);
            }            
        }     
        return $max;
    } // end onPriceFilterWidgetMaxAmount
    
    public function onPriceFilterWidgetMinAmount($min)
    {
        if (static::$userRole) {
            $resultPrices = $this->getRolePricesForWidgetFilter();
            if ($resultPrices) {
                return min($resultPrices);
            }
        }    
        return $min;
    } // end onPriceFilterWidgetMinAmount
    
    public function getRolePricesForWidgetFilter()
    {
        if (!empty(static::$_rolePrices)) {
            return static::$_rolePrices;
        }

        $facade = &$this->ecommerceFacade;

        $products = $facade->getProductsForRangeWidgetFilter();

        $rolePrices = array();
        
        foreach ($products as $product) {
            $idProduct = $facade->getProductID($product);
            $rolePrices[$idProduct] = $facade->getProductPrice($product);
        }
        $rolePrices = $this->_getPrepareRolePrices($rolePrices);
        
        $this->_setRolePricesForWidgetFilter($rolePrices);
        
        return $rolePrices;
    } // end getRolePricesForWidgetFilter
    
    private function _setRolePricesForWidgetFilter($rolePrices)
    {
        static::$_rolePrices = $rolePrices;
    } // end _setRolePricesForWidgetFilter
    
    private function _getPrepareRolePrices($rolePrices)
    {
        foreach ($rolePrices as $key => $item) {
            if (!$item) {
                unset($rolePrices[$key]);    
            }
        }
        
        return $rolePrices;
    } // end _getPrepareRolePrices
    
    public function onHideProductByUserRole($query)
    {
        return WooUserRoleModule::get('HideProduct')
            ->onHideProductByUserRole($query);
    } // end onHideProductByUserRole

    public function onHideSubcategoryTerms($terms, $taxonomies, $args)
    {
        return WooUserRoleModule::get('HideProduct')->onHideSubcategoryTerms(
            $terms,
            $taxonomies,
            $args
        );
    } // end onHideSubcategoryTerms

    public function onProductPriceOnlyRegisteredUsers($price)
    {
        return WooUserRoleModule::get('HidePrice')
            ->onProductPriceOnlyRegisteredUsers($price);
    } // end onProductPriceOnlyRegisteredUsers
    
    public function getTextInsteadPrices()
    {
        return $this->textInsteadPrices;   
    } // end getTextInsteadPrices
    
    public function setTextInsteadPrices($content)
    {
        return $this->textInsteadPrices = $content;   
    } // end setTextInsteadPrices
    
    public function onSalePriceToNewPriceTemplateFilter(
        $price, $sale, $newPrice, $product
    )
    {
        if (!$this->isRegisteredUser()) {
            return $price;
        }
        $idProduct = $this->ecommerceFacade->getProductID($product);
        $prices = $this->getProductPrices($idProduct);
        
        if (!$this->_hasPriceByUserRole($prices)) {
            return $price;
        }
        
        $product = $this->getProductNewInstance($product);
        
        if (!$this->products->isAvailableToDisplaySaleRange($product)) {
        
            $price = $this->products->getFormattedPriceForSaleRange(
                $product,
                $newPrice
            );
            
            $price = $this->getFormattedPrice($price);
        }
        
        return $price;
    } // end onSalePriceToNewPriceTemplateFilter
    
    private function _fetchRolePriceRangeByVariableProduct($prices)
    {
        if (!$prices) {
            return false;
        }
        
        $minPrice = $this->getFormattedPrice(min($prices));
        $maxPrice = $this->getFormattedPrice(max($prices));
        
        return $this->fetchProductPriceRange($minPrice, $maxPrice);
    } // _fetchRolePriceRangeByVariableProduct
    
    public function onProductPriceRangeFilter($price, $product)
    {
        $id = $this->ecommerceFacade->getProductID($product);

        if (!empty(static::$_rangePrices[$id])) {
            return static::$_rangePrices[$id];
        }
        
        $price = $this->_getProductPriceRangeFilter($price, $product);
        
        static::$_rangePrices[$id] = $price;
        
        return $price;
        
    } // end onProductPriceRangeFilter
    
    private function _fetchRolePriceByVariableProduct($product)
    {
        $regularPrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            PRICE_BY_ROLE_TYPE_PRODUCT_REGULAR_PRICE
        ); 
        
        $salePrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            PRICE_BY_ROLE_TYPE_PRODUCT_SALE_PRICE
        );

        if (empty($salePrices)) {
            return $this->_fetchRegularPriceRangeForVariableProduct(
                $product,
                $regularPrices
            );
        }
        
        $isDifferentAmount = $this->_hasDifferentAmountOfPriceInProduct(
            $regularPrices,
            $salePrices
        );
        if ($isDifferentAmount) {
            $prices = $this->ecommerceFacade->getPricesFromVariationProduct(
                $product
            );
            return $this->_fetchRolePriceRangeByVariableProduct($prices);
        }
        
        return $this->_fetchSalePriceRangeForVariableProduct(
            $product,
            $regularPrices,
            $salePrices
        );
    } // end _fetchRolePriceByVariableProduct
    
    private function _hasDifferentAmountOfPriceInProduct(
        $regularPrices, $salePrices
    )
    {
         return count($regularPrices) != count($salePrices);
    } // end _hasDifferentAmountOfPriceInProduct
    
    private function _fetchRegularPriceRangeForVariableProduct(
        $product,
        $prices
    )
    {
        $price = $this->_fetchRolePriceRangeByVariableProduct(
            $prices
        );
        $priceSuffix = $this->ecommerceFacade->getPriceSuffix($product);

        $vars = array(
            'regularPrice' => $price.$priceSuffix,
        );

        return $this->fetch('price_role_with_sale_variable.phtml', $vars);
    } // end _fetchRegularPriceRangeForVariableProduct
    
    private function _fetchSalePriceRangeForVariableProduct(
        $product,
        $regularPrices,
        $salePrices
    )
    {
        $regularPrice = $this->_fetchRolePriceRangeByVariableProduct(
            $regularPrices
        );

        $salePrice = $this->_fetchRolePriceRangeByVariableProduct(
            $salePrices
        );
        
        $suffix = $this->ecommerceFacade->getPriceSuffix($product);

        $vars = array(
            'regularPrice' => $regularPrice,
            'salePrice'    => $salePrice.$suffix
        );
        
        return $this->fetch('price_role_with_sale_variable.phtml', $vars);
    } // end _fetchSalePriceRangeForVariableProduct
    
    public function fetchProductPriceRangeFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);
            
        $priceRangeType = PRICE_BY_ROLE_MIN_PRICE_RANGE_TYPE;
        
        $from = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            true
        );
        
        $priceRangeType = PRICE_BY_ROLE_MAX_PRICE_RANGE_TYPE;
        $to = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            true
        );
        
        if (!$from && !$to) {
            return $price;
        }
        
        $from = $this->getFormattedPrice($from);
        $to = $this->getFormattedPrice($to);
        
        $displayPrice = $this->fetchProductPriceRange($from, $to);
        
        $priceSuffix = $this->ecommerceFacade->getPriceSuffix($product);
        $price = $displayPrice.$priceSuffix;
        
        return $price;
    } // fetchProductPriceRangeFilter
    
    private function _getProductPriceRangeFilter($price, $product)
    {
        if ($this->_hasNewPriceForVariableProduct($product)) {
            return $price;
        }
        
        if ($this->hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            return $this->fetchProductPriceRangeFilter($price, $product);
        }
        
        return $this->_fetchRolePriceByVariableProduct($product);
    } // end onProductPriceRangeFilter
        
    private function _hasNewPriceForVariableProduct($product)
    {
        return !$this->_hasRolePriceByVariableProduct($product) &&
               !$this->hasDiscountOrMarkUpForUserRoleInGeneralOptions();
    } // end _hasNewPriceForVariableProduct

    protected function fetchProductPriceRange($from, $to)
    {
        if ($from == $to) {
            $template = '%1$s';
        } else {
            $template = '%1$s&ndash;%2$s';
        }
        
        $content = _x($template, 'Price range: from-to', 'woocommerce');
        
        $content = sprintf($content, $from, $to);
        
        return $content;
    } // end fetchProductPriceRange
    
    public function getMainProductID()
    {
        if ($this->mainProductOnPage) {
            return $this->mainProductOnPage;
        }
        
        if (!$this->isProductPage()) {
            return false;
        }

        $facade = EngineFacade::getInstance();

        $this->mainProductOnPage = $facade->getCurrentPostID();
        
        return $this->mainProductOnPage;
    } //end getMainProductID
    
    protected function onDisplayCustomerSavings()
    {
        if ($this->_isMarkupEnabledOrDiscountFromRolePrice()) {
            return false;
        }
        
        $this->products->onDisplayCustomerSavings();
        
        $this->mainTotals = true;
        
        $this->addFilterListener(
            'woocommerce_cart_totals_order_total_html',
            'onDisplayCustomerTotalSavingsFilter',
            10,
            2
        );
        
        $this->addFilterListener(
            'wcs_cart_totals_order_total_html',
            'onDisplayCustomerTotalSavingsFilter',
            10,
            2
        );
    } // end onDisplayCustomerSavings 
    
    private function _isMarkupEnabledOrDiscountFromRolePrice()
    {
        return !$this->isDiscountTypeEnabled() &&
               $this->isRolePriceDiscountTypeEnabled();
    } // end _isMarkupEnabledOrDiscountFromRolePrice

    public function getUserTotalWithSubscription($total)
    {
        return WooUserRoleModule::get('PriceSavings')
            ->getUserTotalWithSubscription($total);
    } // end getUserTotalWithSubscription
    
    public function isOnlySubscriptionInCart($cart)
    {
        $products = $cart->getProducts();
        
        return count($products) == 1;
    } // end isOnlySubscriptionInCart

    public function getProductID($product)
    {
        $facade = &$this->ecommerceFacade;

        if ($this->isSubscribeVariableProduct($product)) {
            $idProduct = $facade->getVariationProductID($product);
        } else {
            $idProduct = $facade->getProductID($product);
        }
        
        return $idProduct;
    } // end getProductID
    
    public function isSubscribeVariableProduct($product) 
    {
        return (bool) $this->ecommerceFacade->getVariationProductID($product);
    } // end isSubscribeVariableProduct

    public function getTotalRetailWithSubscription($total, $cart)
    {
        return WooUserRoleModule::get('PriceSavings')
            ->getTotalRetailWithSubscription($total, $cart);
    } // end getTotalRetailWithSubscription

    public function setSubscriptionProductOption($cart)
    {
        return WooUserRoleModule::get('PriceSavings')
            ->setSubscriptionProductOption($cart);
    } // end setSubscriptionProductOption
    
    public function onDisplayCustomerTotalSavingsFilter($total)
    {
        return WooUserRoleModule::get('PriceSavings')
            ->onDisplayCustomerTotalSavingsFilter($total);
    } // end onDisplayCustomerTotalSavingsFilter    

    protected function getRetailTotal()
    {
        return WooUserRoleModule::get('PriceSavings')->getRetailTotal();
    } // end getRetailTotal

    protected function getRetailSubTotalWithTax()
    {
        return WooUserRoleModule::get('PriceSavings')
            ->getRetailSubTotalWithTax();
    } // end getRetailSubTotalWithTax
    
    public function onVariationPriceFilter(
        $price, $product, $priceRangeType, $display
    )
    {
        $product = $this->getProductNewInstance($product);
               
        $userPrice = $this->getPriceByRangeType(
            $product,
            $priceRangeType,
            $display
        );
        
        if ($userPrice) {
            $price = $this->getPriceWithFixedFloat($userPrice);
        }

        return $price;
    } // end onVariationPriceFilter
    
    public function getPriceByRangeType($product, $rangeType, $display)
    {
        if ($this->_isMaxPriceRangeType($rangeType)) {
            $price = $this->products->getMaxProductPrice($product, $display);
        } else {
            $price = $this->products->getMinProductPrice($product, $display);
        }
        
        return $price;
    } // end getPriceByRangeType
    
    private function _isMaxPriceRangeType($rangeType)
    {
        return $rangeType == PRICE_BY_ROLE_MAX_PRICE_RANGE_TYPE;
    } // end _isMaxPriceRangeType
        
    public function fetchPrice(
        $price, $type = WooUserRolePricesFrontendFestiPlugin::TYPE_PRICE_REGULAR
    )
    {
        $vars = array(
            'price' => $price,
            'type'  => $type
        );
        
        return $this->fetch('price.phtml', $vars);
    } // end fetchRegularPrice
    
    private function _isRoleSalePriceLowerThenRolePrice($product)
    {
        return $this->getSalePrice($product) < $this->getPrice($product);
    } // end _isRoleSalePriceLowerThenRolePrice

    /**
     * Display price HTML for all product type like simple and variable.
     * 
     * @param string $price html content for display price
     * @param WC_Product $product
     * @return string
     */

    public function onDisplayCustomerSavingsFilter(
        $price, $product
    )
    {
        if ($this->_isOneHundredPercentDiscountEnabled()) {
            return $this->fetch('free.phtml');
        }

        if (
            $this->_hasRolePriceByVariableProduct($product) ||
            $this->isVariableTypeProduct($product)
        ) {
            if ($this->isEnableBothRegularSalePriceSetting()) {
                return $this->_fetchVariableBothRegularSalePrice(
                    $price,
                    $product
                );
            }
            return $price;
        }

        $product = $this->getProductNewInstance($product);

        $result = WooUserRoleModule::get('PriceSavings')
            ->hasConditionsForDisplayCustomerSavingsInProduct($product);

        if ($result) {
            return WooUserRoleModule::get('PriceSavings')
                ->onDisplayCustomerSavingsFilter($price, $product);
        }

        if ($this->_hasSalePriceForUserRole($product) &&
            $this->_isRoleSalePriceLowerThenRolePrice($product)
        ) {
            $content = $this->_fetchPriceAndSalePriceForUserRole(
                $product
            );
            return $content;
        }

        if (
            $this->_isDisplayDefaultPriceForCustomerSavingsFilter($product)
        ) {
            return $this->_fetchUserRolePrice($product);
        }

        if ($this->_hasSalePriceByDiscountOrMarkUpProduct($product)) {
            return $this->_fetchSimpleBothRegularSalePrice($product);
        }

        return $price;
    } // end onDisplayPriceContentForSingleProductFilter
    
    private function _isDisplayVariableRegularPrice($product, $salePrices)
    {
        if (!$salePrices) {
            return false;
        }

        $min = min($salePrices);
        $max = max($salePrices);

        $facade = $this->ecommerceFacade;

        $productsIDs = $facade->getVariationChildrenIDs($product);
        
        return $min == $max &&
               sizeof($productsIDs) == sizeof($salePrices);
    } // end _isDisplayVariableRegularPrice

    private function _fetchVariableBothRegularSalePrice($price, $product)
    {
        $facade = &$this->ecommerceFacade;
        
        $productsIDs = $facade->getVariationChildrenIDs($product);

        $regularPrices = array();
        $salePrices = array();

        foreach ($productsIDs as $id) {
            $productChild = $this->createProductInstance($id);

            $regularPrice = $facade->getRegularPrice($productChild);

            $regularPrice = $this->getPriceWithDiscountOrMarkUp(
                $productChild,
                $regularPrice,
                false
            );

            $salePrice = $facade->getSalePrice($productChild);

            $salePrice = $this->getPriceWithDiscountOrMarkUp(
                $productChild,
                $salePrice,
                true
            );

            if ($this->isIncludingTaxesToPrice()) {
                $regularPrice = $facade->doIncludeTaxesToPrice(
                    $product,
                    $regularPrice
                );
                $salePrice = $facade->doIncludeTaxesToPrice(
                    $product,
                    $salePrice
                );
            }

            $regularPrices[] = $regularPrice;

            if ($salePrice) {
                $salePrices[] = $salePrice;
            }
        }

        $regularPriceContent = false;

        if ($this->_isDisplayVariableRegularPrice($product, $salePrices)) {
            $regularPriceContent = $this->_fetchRolePriceRangeByVariableProduct(
                $regularPrices
            );
        }

        $priceSuffix = $this->ecommerceFacade->getPriceSuffix($product);

        if ($salePrices) {
            $salePrice = min($salePrices);
            $salePrice = $this->getFormattedPrice($salePrice);
            $price = $salePrice.$priceSuffix;
        }

        if (!$regularPriceContent) {
            $salePriceContent = $this->_fetchRolePriceRangeByVariableProduct(
                $salePrices
            );
            $price = $salePriceContent.$priceSuffix;
        }

        $vars = array(
            'price' => $regularPriceContent,
            'salePrice' => $price
        );

        $content = $this->fetch(
            'price_role_width_sale.phtml',
            $vars
        );
        
        return $content;
    } // end _fetchVariableBothRegularSalePrice

    private function _fetchSimpleBothRegularSalePrice($product)
    {
        $facade = &$this->ecommerceFacade;

        $originalRegularPrice = $facade->getRegularPrice($product);

        $regularPrice = $this->getPriceWithDiscountOrMarkUp(
            $product,
            $originalRegularPrice,
            false
        );

        $originalSalePrice = $facade->getSalePrice($product);

        $salePrice = $this->getPriceWithDiscountOrMarkUp(
            $product,
            $originalSalePrice
        );

        if ($this->isIncludingTaxesToPrice()) {
            $regularPrice = $facade->doIncludeTaxesToPrice(
                $product,
                $regularPrice
            );
            $salePrice = $facade->doIncludeTaxesToPrice($product, $salePrice);
        }

        $priceSuffix = $this->ecommerceFacade->getPriceSuffix($product);

        $salePrice = $this->getFormattedPrice($salePrice);

        $vars['salePrice'] = $salePrice.$priceSuffix;

        if ($this->_isDifferentPrices($regularPrice, $salePrice)) {
            $vars['price'] = $this->getFormattedPrice($regularPrice);
        }

        if (!$salePrice) {
            $regularPrice = $this->getFormattedPrice($regularPrice);
            $vars['salePrice'] = $regularPrice.$priceSuffix;
        }

        $content = $this->fetch(
            'price_role_width_sale.phtml',
            $vars
        );

        return $content;
    } // end _fetchSimpleBothRegularSalePrice

    private function _isDifferentPrices($regularPrice, $salePrice)
    {
        return $salePrice && $regularPrice != $salePrice;
    } // end _isDifferentPrices

    private function _isOneHundredPercentDiscountEnabled()
    {
        if ($this->isRegisteredUser()) {
            return $this->getAmountOfDiscountOrMarkUp() >= 100 &&
                   $this->isPercentDiscountType() &&
                   $this->isDiscountTypeEnabled();
        }

        return false;
    } // end _isOneHundredPercentDiscountEnabled

    private function _isDisplayDefaultPriceForCustomerSavingsFilter($product)
    {
        $productPrice = $this->getPrice($product);
        
        $emptyPriceSymbol = $this->ecommerceFacade->getEmptyPriceSymbol();
        
        return $productPrice &&
               !$this->_isDefaultCurrencyActive() &&
               $this->isRegisteredUser() &&
               $productPrice !== $emptyPriceSymbol;
    } // end _isDisplayDefaultPriceForCustomerSavingsFilter

    private function _hasSalePriceForUserRole($product)
    {
        if (!$this->isVariableTypeProduct($product)) {
            $salePrice = $this->getSalePrice($product);
            
            return (bool) $salePrice;
        }
    } // end _hasSalePriceForUserRole

    private function _isDefaultCurrencyActive()
    {
        $defaultCurrency = WooCommerceFacade::getDefaultCurrencyCode();
        $activeCurrency = WooCommerceFacade::getBaseCurrencyCode();

        return $defaultCurrency == $activeCurrency;
    } // end _isDefaultCurrencyActive
    
    private function _fetchUserRolePrice($product)
    {
        $price = $this->getPrice($product);
        
        $formatPrice = $this->getFormattedPrice($price);
        $typePriceUser = WooUserRolePricesFrontendFestiPlugin::TYPE_PRICE_USER;

        return $this->fetchPrice($formatPrice, $typePriceUser);
    } // end _fetchUserRolePrice
    
    private function _fetchPriceAndSalePriceForUserRole($product)
    {
        $price = $this->getPrice($product);
        $salePrice = $this->getSalePrice($product);

        $facade = $this->ecommerceFacade;

        if ($this->isIncludingTaxesToPrice()) {
            $price = $facade->doIncludeTaxesToPrice($product, $price);
            $salePrice = $facade->doIncludeTaxesToPrice($product, $salePrice);
        }

        $vars = array(
            'price' => $this->getFormattedPrice($price),
            'salePrice' => $this->getFormattedPrice($salePrice)
        );

        $content = $this->fetch(
            'price_role_width_sale.phtml',
            $vars
        );

        return $content;
    } // end _fetchPriceAndSalePriceForUserRole

    public function getFormattedPrice($price)
    {
        return wc_price($price);
    } // end getFormattedPrice

    public function isProductParentMainProduct($product)
    {
        $idParent = $this->ecommerceFacade->getProductParentID($product);

        if (!$idParent) {
            return false;
        }

        return $idParent == $this->mainProductOnPage;
    } // end isProductParentMainProduct

    public function getMainProductOnPage()
    {
        return $this->mainProductOnPage;
    } // end getMainProductOnPage

    public function removeFilter($hook, $methodName, $priority = 10)
    {
        if (!is_array($methodName)) {
            $methodName = array(&$this, $methodName);
        }
        remove_filter($hook, $methodName, $priority);
    } // end removeFilter

    public function onReplaceAllPriceToTextInSomeProductFilter($price, $product)
    {
        return WooUserRoleModule::get('HidePrice')
            ->onReplaceAllPriceToTextInSomeProductFilter($price, $product);
    } // end onReplaceAllPriceToTextInSomeProductFilter

    public function isProductPage()
    {
        return is_product();
    } // end isProductPage
    
    public function onRemoveAddToCartButtonInSomeProductsFilter(
        $button, $product
    )
    {
        return WooUserRoleModule::get('HidePrice')
            ->onRemoveAddToCartButtonInSomeProductsFilter($button, $product);
    } // end onRemoveAddToCartButtonInSomeProductsFilter
    
    public function onReplaceAllPriceToTextInAllProductFilter()
    {
        return $this->fetchContentInsteadOfPrices();
    } //end onReplaceAllPriceToTextInAllProductFilter
    
    public function fetchContentInsteadOfPrices()
    {
        $vars = array(
            'text' => $this->textInsteadPrices
        );
        
        return $this->fetch('custom_text.phtml', $vars);
    } // end fetchContentInsteadOfPrices
     
    public function removeGroupedAddToCartLinkAction()
    {
        $vars = array(
            'settings' => $this->getSettings(),
        );
        echo $this->fetch('hide_grouped_add_to_cart_buttons.phtml', $vars);
    } // end removeGroupedAddToCartLinkAction
    
    public function removeVariableAddToCartLinkAction()
    {
        $vars = array(
            'settings' => $this->getSettings(),
        );
        echo $this->fetch('hide_variable_add_to_cart_buttons.phtml', $vars);
    } // end removeVariableAddToCartLinkAction

    public function onRemoveAllAddToCartButtonFilter($button, $product)
    {
        if ($this->_hasAddToCartButtonText($product)) {
            $settings = $this->getSettings();
            return $settings['textForNonRegisteredUsers'];
        }
        
        return $button;
    } // end onRemoveAddToCartButtonFilter
    
    private function _hasAddToCartButtonText($product)
    {
        $facade = &$this->ecommerceFacade;

        return $facade->isProductPurchasableAndInStock($product);
    } // _hasAddToCartButtonText
    
    public function isAddToCartButtonHiddenAndProductPurchasable($product)
    {
        $settings = $this->getSettings();

        $facade = &$this->ecommerceFacade;

        return array_key_exists('textForNonRegisteredUsers', $settings) && 
               $facade->isProductPurchasableAndInStock($product);
    } // end isAddToCartButtonHiddenAndProductPurchasable

    public function getTemplatePath($fileName)
    {
        return $this->pluginTemplatePath.'frontend/'.$fileName;
    } // end getTemplatePath
    
    public function getPluginJsUrl($fileName, $customUrl = false)
    {
        if ($customUrl) {
            return $customUrl.$fileName;
        }

        return $this->pluginJsUrl.'frontend/'.$fileName;
    } // end getPluginJsUrl

    public function getPluginCssUrl($fileName, $customUrl = false)
    {
        if ($customUrl) {
            return $customUrl.$fileName;
        }
        
        return $this->pluginUrl.$fileName;
    } // end getPluginCssUrl
    
    public function onInitJsAction()
    {
        $this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-general',
            'general.js',
            'jquery',
            $this->version
        );
    } // end onInitJsAction
    
    public function onInitCssAction()
    {
        $this->addActionListener(
            'wp_head',
            'appendCssToHeaderForCustomerSavingsCustomize'
        );

        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-styles',
            'static/styles/frontend/style.css',
            array(),
            $this->version
        );
    } // end onInitCssAction
    
    public function appendCssToHeaderForCustomerSavingsCustomize()
    {
        if (!$this->hasOptionInSettings('showCustomerSavings')) {
            return false;
        }
        
        $vars = array(
            'settings' => $this->getSettings(),
        );

        echo $this->fetch('customer_savings_customize_style.phtml', $vars);
    } // end appendCssToHeaderForPriceCustomize
    
    public function hasOptionInSettings($option)
    {
        $settings = $this->getSettings();

        return array_key_exists($option, $settings);
    } // end hasOptionInSettings
    
    public function isWoocommerceMultiLanguageActive()
    {
        $pluginPath = 'woocommerce-multilingual/wpml-woocommerce.php';
        
        return $this->isPluginActive($pluginPath);
    } // end isWoocommerceMultiLanguageActive

    public function onDisplayOnlyProductStockStatusAction()
    {
        $vars = array(
            'settings' => $this->getSettings(),
        );
        echo $this->fetch('stock_status_for_simple_type_product.phtml', $vars);
    } // end onDisplayOnlyProductStockStatusAction

    private function _setPrepareDisplayUserRoleTaxes()
    {
        $ecommerceFacade = $this->ecommerceFacade;

        $settings = $this->getTaxByUserRoleOptions();

        if (!$settings) {
            return false;
        }

        $taxDisplayType = $settings['taxType'];

        if ($taxDisplayType == static::FESTI_DEFAULT_TAX_KEY) {
            return false;
        }

        $engineFacade = EngineFacade::getInstance();

        $shopHookName = $ecommerceFacade->getHookNameForDisplayPicesTax();

        $cartHookName = $ecommerceFacade->getHookNameForDisplayPicesTax('cart');

        $excludingTax = WooCommerceFacade::FESTI_DISPLAY_PRICES_EXCLUDING_TAX;

        switch ($taxDisplayType) {

            case static::FESTI_EXCLUDE_ALL_TAX_KEY:
                $engineFacade->updateOption(
                    $shopHookName,
                    $excludingTax
                );
                $engineFacade->updateOption(
                    $cartHookName,
                    $excludingTax
                );

                break;

            case static::FESTI_EXCLUDE_TAX_IN_SHOP_KEY:
                $engineFacade->updateOption(
                    $shopHookName,
                    $excludingTax
                );

                break;

            case static::FESTI_EXCLUDE_TAX_IN_CART_AND_CHECKOUT_KEY:
                $engineFacade->updateOption(
                    $cartHookName,
                    $excludingTax
                );

                break;
        }
    } // end _setPrepareDisplayUserRoleTaxes

    private function _setCartItemsCount()
    {
        $name = ModulesSwitchListener::QUANTITY_DISCOUNT_PLUGIN;

        if ($this->isModuleExist($name)) {
            $facade = WooCommerceCartFacade::getInstance();

            $count = $facade->getCartContentsCount();

            $pluginName = $name.'Plugin';

            $file = PRICE_BY_ROLE_EXTENSIONS_DIR.
                $name.DIRECTORY_SEPARATOR.$pluginName.'.php';

            require_once($file);

            $pluginName::$cartItemsCount = $count;
        }
    } // end _setCartItemsCount
}