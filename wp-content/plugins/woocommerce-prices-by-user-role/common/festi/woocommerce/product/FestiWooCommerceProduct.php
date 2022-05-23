<?php
if (!class_exists('AbstractFestiWooCommerceProduct')) {
    require_once __DIR__.DIRECTORY_SEPARATOR.'AbstractFestiWooCommerceProduct.php';
}

class FestiWooCommerceProduct
{
    const FILTER_GET_PRICE_HTML_PRIORITY = 200;
    const FILTER_GET_PRICE_PRIORITY = 200;
    const UNKNOWN_PRODUCT_TYPE = 'unknown';

    private $_engine;
    private $_ecommerceFacade;
    private $_types = array(
        'simple',
        'variable',
        'grouped',
        'variation',
        'addons',
        'bundle',
        'external',
        'composite',
        'subscription',
        'variable-subscription',
        'subscription_variation',
        'yith_bundle'
    );
    private $_instances = array();
    
    public function __construct($engine)
    {
        $this->_engine = $engine;
        $this->_ecommerceFacade = EcommerceFactory::getInstance();
        $this->_prepareInstances();
        $this->onInit();
    } // end __construct
    
    public function doFormatProductTypeName($typeName)
    {
        $delimiter = $this->_getDelimiterPosition($typeName);
        
        $length = strlen($typeName);
        $firstPart = substr($typeName, 0, $delimiter);
        $secondPart = substr($typeName, $delimiter + 1, $length);
        
        return $firstPart.ucfirst($secondPart);
    } // end doFormatProductTypeName
    
    public function isTypeNameComposedOfTwoWords($typeName)
    {
        return strpos($typeName, '-') !== false ||
               strpos($typeName, '_') !== false;
    } // end isTypeNameComposedOfTwoWords

    private function _getDelimiterPosition($typeName)
    {
        $delimiters = array('-', '_');
        
        foreach ($delimiters as $delimiter) {
            $position = strpos($typeName, $delimiter);
            
            if (!$position === false) {
                return $position;
            }
        }
    } // end _getDelimiterPosition
    
    private function _prepareInstances()
    {
        $this->_types[] = static::UNKNOWN_PRODUCT_TYPE;

        foreach ($this->_types as $type) {
            $originalTypeName = $type;
            
            if ($this->isTypeNameComposedOfTwoWords($type)) {
                $type = $this->doFormatProductTypeName($type);
            }
            
            $className = 'FestiWooCommerce'.ucfirst($type).'Product';
            
            $this->_onInitInstance($className);
            
            $this->_instances[$originalTypeName] = new $className($this);
        }
    } // end _prepareInstances
    
    private function _onInitInstance($className)
    {
        $fileName = $className.'.php';
        $filePath = dirname(__FILE__).'/'.$fileName;
        
        if (!file_exists($filePath)) {
            throw new Exception("The ".$fileName." not found!");
        }
        
        require_once $filePath;
        
        if (!class_exists($className)) {
            $message = "The class ".$className." is not exists in ".$filePath;
            throw new Exception($message);
        }
    } // end _onInitInstance
    
    protected function onInit()
    {
        foreach ($this->_instances as $instance) {
            $instance->onInit();
        }
    } // end onInit
    
    public function getInstance($productType)
    {
        if (!array_key_exists($productType, $this->_instances)) {
            throw new Exception('Not found instance with type '.$productType);
        }
        
        return $this->_instances[$productType];
    } // end getInstance
    
    public function addActionListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        $this->_engine->addActionListener(
            $hook,
            $method,
            $priority,
            $acceptedArgs
        );
    } // end addActionListener
    
    public function addFilterListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        $this->_engine->addFilterListener(
            $hook,
            $method,
            $priority,
            $acceptedArgs
        );
    } // end addFilterListener
    
    public function removeAllLoopAddToCartLinks()
    {
        $this->addFilterListener(
            'woocommerce_loop_add_to_cart_link',
            'onRemoveAllAddToCartButtonFilter',
            10,
            2
        );
    } // end removeAllLoopAddToCartLinks
    
    public function removeLoopAddToCartLinksInSomeProducts()
    {
        $this->addFilterListener(
            'woocommerce_loop_add_to_cart_link',
            'onRemoveAddToCartButtonInSomeProductsFilter',
            10,
            2
        );
    } // end removeLoopAddToCartLinksInSomeProducts
    
    public function removeAddToCartButton($type = false)
    {
        if ($type) {
            $this->_instances[$type]->removeAddToCartButton();
            return true;
        }
        
        foreach ($this->_instances as $instance) {
            $instance->removeAddToCartButton();
        }
    } // end removeAddToCartButton
    
    public function replaceAllPriceToText()
    { 
        $this->addFilterListener(
            'woocommerce_get_price_html',
            'onReplaceAllPriceToTextInAllProductFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onReplaceAllPriceToTextInAllProductFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
    } // end replaceAllPriceToText
    
    public function replaceAllPriceToTextInSomeProduct()
    {
        $this->addFilterListener(
            'woocommerce_get_price_html',
            'onReplaceAllPriceToTextInSomeProductFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onReplaceAllPriceToTextInSomeProductFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
    } // end replaceAllPriceToTextInSomeProduct
    
    public function fetchContentInsteadOfPrices()
    {
        $vars = array(
            'text' => $this->textInsteadPrices
        );
        
        return $this->fetch('custom_text.phtml', $vars);
    } // end fetchContentInsteadOfPrices
    
    public function onFilterPriceByRolePrice()
    {
        $this->addFilterListener(
            $this->_ecommerceFacade->getHookNameForGetPrice(),
            'onDisplayPriceByRolePriceFilter',
            static::FILTER_GET_PRICE_PRIORITY,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_product_variation_get_price',
            'onDisplayPriceByRolePriceFilter',
            static::FILTER_GET_PRICE_PRIORITY,
            2
        );
    } // end onFilterPriceByRolePrice
    
    public function onFilterPriceByDiscountOrMarkup()
    {
        $this->addFilterListener(
            $this->_ecommerceFacade->getHookNameForGetPrice(),
            'onDisplayPriceByDiscountOrMarkupFilter',
            static::FILTER_GET_PRICE_PRIORITY,
            2
        );

        $this->addFilterListener(
            'woocommerce_product_variation_get_price',
            'onDisplayPriceByDiscountOrMarkupFilter',
            static::FILTER_GET_PRICE_PRIORITY,
            2
        );
    } // end onFilterPriceByDiscountOrMarkup
    
    public function getRolePrice($product)
    {
        if (!$product) {
            return false;
        }

        $idProduct = $this->getProductID($product);
        $type = $this->_ecommerceFacade->getProductType($product);
        
        if (!$idProduct) {
            throw new Exception('Undefined idProduct Product type is '.$type);
        }

        return $this->_engine->getRolePrice($idProduct);
    } // end getRolePrice
    
    public function getRoleSalePrice($product)
    {
        if (!$product) {
            return false;
        }

        $idProduct = $this->getProductID($product);
        
        if (!$idProduct) {
            $type = $this->_ecommerceFacade->getProductType($product);
            throw new Exception('Undefined idProduct Product type is '.$type);
        }
    
        if (!method_exists($this->_engine, 'getRoleSalePrice')) {
            throw new Exception('Undefined method getRoleSalePrice');
        }
        
        return $this->_engine->getRoleSalePrice($idProduct);
    } // end getRoleSalePrice
    
    public function getProductID($product)
    {
        if (!$product) {
            return false;
        }

        $type = $this->getProductType($product);

        return $this->_instances[$type]->getProductID($product);
    } // end getProductID
    
    private function _hasProductInstanceWithType($type)
    {
        return in_array($type, $this->_types);
    } // end _hasProductInstanceWithType
    
    public function onDisplayCustomerSavings()
    {
        $this->addFilterListener(
            'woocommerce_get_price_html',
            'onDisplayCustomerSavingsFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
        
        $this->addFilterListener(
            'woocommerce_get_variation_price_html',
            'onDisplayCustomerSavingsFilter',
            static::FILTER_GET_PRICE_HTML_PRIORITY,
            2
        );
    } // end onDisplayCustomerSavings
    
    public function isAvailableProductTypeToDisplaySavings($product)
    {
        $type = $this->getProductType($product);

        return $this->_instances[$type]->isAvailableToDisplaySavings($product);
    } // end isAvailableProductTypeToDisplaySavings
    
    public function isProductPage()
    {
        return $this->_engine->isProductPage();
    } // end isProductPage
    
    public function getMaxProductPrice($product, $display)
    {
        $type = $this->getProductType($product);

        return $this->_instances[$type]->getMaxProductPrice(
            $product,
            $display
        );
    } // end getMaxProductPrice
    
    public function getMinProductPrice($product, $display)
    {
        $type = $this->getProductType($product);

        return $this->_instances[$type]->getMinProductPrice(
            $product,
            $display
        );
    } // end getMinProductPrice
    
    public function createProductInstance($idProduct)
    {
        return $this->_engine->createProductInstance($idProduct);
    } // end createProductInstance
    
    public function getPriceRange($product)
    {
        $type = $this->getProductType($product);
        
        return $this->_instances[$type]->getPriceRange($product);
    } // end getPriceRange
    
    public function isWoocommerceMultiLanguageActive()
    {
        return $this->_engine->isWoocommerceMultiLanguageActive();
    } // end isWoocommerceMultiLanguageActive
    
    public function getPostMeta($idPost, $key, $single = true)
    {
        return $this->_engine->getPostMeta($idPost, $key, $single);
    } // end getPostMeta
    
    public function isAvailableToDisplaySaleRange($product)
    {
        $type = $this->getProductType($product);

        return $this->_instances[$type]->isAvailableToDisplaySaleRange(
            $product
        );
    } // end isAvailableToDisplaySaleRange
    
    public function getListOfProductsWithRolePrice()
    {
        return $this->_engine->getListOfProductsWithRolePrice();
    } // end getListOfProductsWithRolePrice
    
    public function getRegularPrice($product, $display = false)
    {
        $type = $this->getProductType($product);
        
        return $this->_instances[$type]->getRegularPrice($product, $display);
    } // end getRegularPrice
    
    public function getUserPrice($product, $display = false)
    {
        $type = $this->getProductType($product);
        
        return $this->_instances[$type]->getUserPrice($product, $display);
    } // end getUserPrice
    
    public function getUserPrices($product, $display = false)
    {
        $type = $this->getProductType($product);
        
        return $this->_instances[$type]->getUserPrices($product, $display);
    } // end getUserPrices
    
    public function getFormattedPriceForSaleRange($product, $userPrice)
    {
        $type = $this->getProductType($product);

        return $this->_instances[$type]->getFormattedPriceForSaleRange(
            $product,
            $userPrice
        );
    } // end getFormattedPriceForSaleRange
    
    public function fetch($template, $vars = array())
    {
        return $this->_engine->fetch($template, $vars);
    } // end fetch
    
    public function getPriceSuffix($product, $price = '')
    {
        $type = $this->getProductType($product);

        return $this->_instances[$type]->getPriceSuffix($product, $price);
    } // end getPriceSuffix

    public function getProductType($product)
    {
        $type = $this->_ecommerceFacade->getProductType($product);

        if (!$type) {
            throw new Exception('Not defined WooCommerce product type');
        }

        if (!$this->_hasProductInstanceWithType($type)) {
            $type = static::UNKNOWN_PRODUCT_TYPE;
        }

        return $type;
    } // end getProductType
}