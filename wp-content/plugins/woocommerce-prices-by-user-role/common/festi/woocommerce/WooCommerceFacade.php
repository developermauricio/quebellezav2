<?php

class WooCommerceFacade extends EcommerceFacade
{
    private static $_instance = null;
    private $_adapter;
    
    const FESTI_EMPTY_PRICE_SYMBOL = '';
    const FESTI_DISPLAY_PRICES_INCLUDING_TAX = 'incl';
    const FESTI_DISPLAY_PRICES_EXCLUDING_TAX = 'excl';
    const PRODUCT_CATEGORY_KEY = 'product_cat';
    const QUERY_CLASS_NAME = 'WC_Query';

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
            $message .= 'use WooCommerceFacade::getInstance';
            throw new FacadeException($message);
         }
         
         $this->_adapter = $this->_createAdapter();
    } // end __construct
    
    private function _createAdapter()
    {
        $nameVersion = 'DaringDassie';
        
        if ($this->_isNameVersionButterfly()) {
            $nameVersion = 'Butterfly';
        }

        $className = 'WooCommerce'.$nameVersion.'Adapter';
        
        if (!class_exists($className)) {
            $path = __DIR__.'/adapters/'.$className.'.php';
            if (!include_once($path)) {
                throw new FacadeException(__('Class not found path: '.$path));
            }
        }

        return new $className();
    } // end _createAdapter
    
    public function isNameVersionDaringDassie()
    {
        $version = $this->_getCurrentVersion();
        
        return version_compare($version, '3.0.0', '<');
    } // end isNameVersionDaringDassie

    private function _isNameVersionButterfly()
    {
        $version = $this->_getCurrentVersion();

        return version_compare($version, '3.0.0', '>=');
    } // end _isNameVersionButterfly
    
    private function _getCurrentVersion()
    {
        if (!function_exists('get_plugins')) {
            require_once(ABSPATH.'wp-admin/includes/plugin.php');    
        }
        
        $pluginFolder = get_plugins('/'.'woocommerce');
        $pluginFile = 'woocommerce.php';
        
        if ($this->_hasPluginVersion($pluginFolder, $pluginFile)) {
            return $pluginFolder[$pluginFile]['Version'];
        }
        
        return false;    
    } // end _getCurrentVersion
    
    private function _hasPluginVersion($pluginFolder, $pluginFile)
    {
        return array_key_exists($pluginFile, $pluginFolder) && 
               array_key_exists('Version', $pluginFolder[$pluginFile]);
    } // end _hasPluginVersion
    
    public function getAttributes($search = array())
    {
        $db = FestiObject::getDatabaseInstance();
        
        $sql = "SELECT 
                    *
                FROM 
                    ".$db->getPrefix()."woocommerce_attribute_taxonomies";
        
        return $db->select($sql, $search);
    } // end getAttributes
    
    public function addAttribute($values)
    {
        $db = FestiObject::getDatabaseInstance();
        
        $tableName = $db->getPrefix()."woocommerce_attribute_taxonomies";
    
        $id = $db->insert($tableName, $values);
        
        delete_transient('wc_attribute_taxonomies');
        
        return $id;
    } // end getAttributes

    public function createAttributesHelper()
    {
        if (!class_exists('WooCommerceAttributesHelper')) {
            require_once dirname(__FILE__).'/WooCommerceAttributesHelper.php';
        }
        
        return new WooCommerceAttributesHelper();
    } // end createAttributesHelper

    public function getNumberOfDecimals()
    {
        $facade = EngineFacade::getInstance();

        return $facade->getOption('woocommerce_price_num_decimals');
    } // end getNumberOfDecimals
    
    public function getWooCommerceInstance()
    {
        if (!function_exists("WC")) {
            throw new Exception("Not Found WooCommerce Instance", 1);
        }
        
        return WC();
    } // end getWooCommerceInstance
    
    public static function getCurrencies()
    {
        return get_woocommerce_currencies();
    } // end getCurrencies
    
    public static function getCurrencySymbol($code = false)
    {
        return get_woocommerce_currency_symbol($code);
    } // end getCurrencySymbol
    
    public static function getBaseCurrencyCode()
    {
        return get_woocommerce_currency();
    } // end getBaseCurrencyCode

    public static function getDefaultCurrencyCode()
    {
        $facade = EngineFacade::getInstance();

        return $facade->getOption('woocommerce_currency');
    } // end getDefaultCurrencyCode
    
    public static function displayMetaTextInputField($args)
    {
        woocommerce_wp_text_input($args);
    } // end displayMetaTextInputField
    
    public static function displayHiddenMetaTextInputField($args)
    {
        woocommerce_wp_hidden_input($args);
    } // end displayHiddenMetaTextInputField

    public static function createQueryInstance($params = array())
    {
        return new WC_Query($params);
    } // end createQueryInstance
    
    public function getProductAttributeValues($idProduct, $attrName)
    {
        $terms = wp_get_object_terms($idProduct, $attrName);
        
        if (!$terms || $terms instanceof WP_Error) {
            return array();
        }
        
        $result = array();
        foreach ($terms as $term) {
            $result[] = $term->name;
        }
        
        return $result;
    } // end getProductAttributeValues
    
    public function getEmptyPriceSymbol()
    {
        return static::FESTI_EMPTY_PRICE_SYMBOL;
    } // end getEmptyPriceSymbol

    public function isEnabledTaxCalculation()
    {
        return wc_tax_enabled();
    } // end isEnabledTaxCalculation

    public function isPricesEnteredWithTax()
    {
        return wc_prices_include_tax();
    } // end isPricesEnteredWithTax
    
    public function getPriceDisplaySuffix()
    {
        $facade = EngineFacade::getInstance();

        return $facade->getOption('woocommerce_price_display_suffix');
    } // end getPriceDisplaySuffix

    public function hasPriceDisplaySuffixPriceIncludingOrExcludingTax()
    {
        $suffix = $this->getPriceDisplaySuffix();

        return (bool)$suffix;
    } // end hasPriceDisplaySuffixPriceIncludingOrExcludingTax
    
    public function doIncludeTaxesToPrice($product, $price)
    {
        $taxRates = $this->_getTaxRates($product);
        
        $taxValues = $this->_doCalculateTaxes($price, $taxRates);
        
        if (!$taxValues) {
            $taxValues = array();
        }

        if (!$this->isDisplayPricesIncludeTax()) {
            return $price;
        }

        $priceWithTaxes = $price + $this->_getTaxTotal($taxValues);
        
        return $priceWithTaxes;
    } // end doIncludeTaxesToPrice
    
    private function _getTaxTotal($taxValues)
    {
        return WC_Tax::get_tax_total($taxValues);
    } // end _getTaxTotal
    
    private function _doCalculateTaxes($price, $taxRates)
    {
        return WC_Tax::calc_tax(floatval($price), $taxRates);
    } // end _doCalculateTaxes
    
    private function _getTaxRates($product)
    {
        return WC_Tax::get_rates($product->get_tax_class());
    } // end _getTaxRates
    
    public function getPriceSuffix($product)
    {
        return $product->get_price_suffix();
    } // end getPriceSuffix
    
    public function updateProductAttributeValues($idProduct, $attrName, $values)
    {
        $facade = EngineFacade::getInstance();

        $facade->setObjectTerms($idProduct, $values, $attrName);
    } // end updateProductAttributeValues
    
    public function setProductTypeToVariable($idProduct)
    {
        $facade = EngineFacade::getInstance();

        $facade->setObjectTerms($idProduct, 'variable', 'product_type');
    } // end updateProductAttributeValues
    
    public function getAttributeIdent($key)
    {
        return str_replace(" ", "_", strtolower($key));
    } // end getAttributeIdent
    
    public function getTaxonomyName($name)
    {
        return wc_sanitize_taxonomy_name($name);
    } // end getTaxonomyName
    
    public function getAttributeNameByKey($key)
    {
        return wc_attribute_taxonomy_name($key);
    } // end getAttributeNameByKey
    
    public function updateProductAttributes($idProduct, $attributes)
    {
        $facade = EngineFacade::getInstance();

        $facade->updatePostMeta($idProduct, '_product_attributes', $attributes);
    } // end updateProductAttributes
    
    public function isProductPurchasableAndInStock($product)
    {
        return $product->is_purchasable() && $product->is_in_stock();
    } // end isProductPurchasableAndInStock
    
    /**
     * Returns values object for woocommerce product.
     *
     * @param string $sku
     * @return WooCommerceProductValuesObject
     */
    public function loadProductValuesObjectBySKU($sku)
    {
        $facade = EngineFacade::getInstance();
        $engineClassName = get_class($facade);

        $existingPostQuery = array(
            'numberposts' => 1,
            'meta_key'    => '_sku',
            'post_type'   => 'product',
            $engineClassName::META_QUERY_KEY  => array(
                array(
                    'key'     =>'_sku',
                    'value'   => $sku,
                    'compare' => '='
                )
            )
        );
    
        $posts = get_posts($existingPostQuery);
        if (!$posts) {
            return false;
        }
        
        return new WooCommerceProductValuesObject($posts[0]);
    } // end loadProductValuesObjectBySKU

    public function getProductsIDsForRangeWidgetFilter()
    {        
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        $facade = EngineFacade::getInstance();
        $engineClassName = get_class($facade);

        $postIDsQuery = array(
            'numberposts'         => -1,
            'post_meta'           => $priceKey,
            'post_type'           => array('product', 'product_variation'),
            'post_status'         => 'publish',
            'ignore_sticky_posts' => 1,
            'fields'              => 'ids',
            $engineClassName::META_QUERY_KEY => array(
                array(
                    'key'     => '_visibility',
                    'value'   => array('catalog', 'visible'),
                    'compare' => 'IN'
                )
            ),
        );
        
        $queryObject = get_queried_object();
        
        if ($this->_hasCategoryByQueryObject($queryObject)) {
            $postIDsQuery[static::PRODUCT_CATEGORY_KEY] = $queryObject->slug;
        }
        
        $productsIDs = get_posts($postIDsQuery);
        
        $postParentIDsQuery = array(
            'numberposts' => -1,
            'post_meta'   => $priceKey,
            'post_type'   => array('product', 'product_variation'),
            'post_status' => 'publish',
            'post_parent__in' => $productsIDs,
            'fields' => 'ids', 
        );
        
        $parentProductsIDs = get_posts($postParentIDsQuery);
        
        $productsIDs = array_merge($productsIDs, $parentProductsIDs);

        return $productsIDs;
    } // end getProductsIDsForRangeWidgetFilter

    private function _hasCategoryByQueryObject($queryObject)
    {
        return !empty($queryObject->term_id);
    } // end _hasCategoryByQueryObject
    
    public function getProductsByIDsForWidgetFilter($productIDs)
    {
        $products = array();
        
        if ($productIDs) {
                 
             $postQuery = array(
                'numberposts' => -1,    
                'post_type'   => array('product', 'product_variation'),
                'post_status' => 'publish',
                'include' => $productIDs,
            );
    
            $products = get_posts($postQuery);
        }

        return $products;
    } // end getProductsByIDsForWidgetFilter
    
    public function getProductsForWidgetFilter()
    {
        $priceKey = WooCommerceProductValuesObject::PRICE_KEY;

        $postQuery = array(
            'numberposts' => -1,
            'meta_key'    => $priceKey,
            'post_type'   => array('product', 'product_variation'),
            'post_status' => 'publish',
        );

        $products = get_posts($postQuery);

        return $products;                    
    } // end getProductsForWidgetFilter

    public function setAllPrivateProductStatusToPublic()
    {
        $database = FestiObject::getDatabaseInstance();

        $tableName = $database->getPrefix().'posts';

        $values = array(
            'post_status' => 'publish'
        );

        $condition = array(
            'post_type' => 'product',
            'post_status' => 'private'
        );

       return $database->update($tableName, $values, $condition);
    } // end setAllPrivateProductStatusToPublic

    public function getPricesFromVariationProduct($product)
    {
        return $this->_adapter->getPricesFromVariationProduct($product);
    } // end getPricesFromVariationProduct
    
    public function getProductType($product)
    {
        $productType = $this->_adapter->getProductType($product);

        if (isset($productType)) {
            return $productType;
        }

        $idProduct = $this->getProductID($product);

        $facade = EngineFacade::getInstance();

        return $facade->getProductType($idProduct);
    } // end getProductType
    
    public function getVariationProductID($product)
    {
        return $this->_adapter->getVariationProductID($product);
    } // end getVariationProductID
    
    public function isChildProduct($product)
    {
        return $this->_adapter->isChildProduct($product);
    } // end isChildProduct
    
    public function getProductParentID($product)
    {
        return $this->_adapter->getProductParentID($product);
    } // end getProductParentID
    
    public function getProductID($product)
    {
        return $this->_adapter->getProductID($product);
    } // end getProductID
    
    public function getPriceExcludingTax($product, $options = array())
    {
        return $this->_adapter->getPriceExcludingTax($product, $options);
    } // end getPriceExcludingTax
    
    public function getPriceIncludingTax($product, $options = array())
    {   
        return $this->_adapter->getPriceIncludingTax($product, $options);
    } // end getPriceIncludingTax
    
    public function getHookNameForWritePanels()
    {
        return $this->_adapter->getHookNameForWritePanels();
    } // end getHookNameForWritePanels
    
    public function getHookNameForGetPrice()
    {
        return $this->_adapter->getHookNameForGetPrice();
    } // end getHookNameForGetPrice
    
    public function getHookNameForPriceFilter()
    {
        return $this->_adapter->getHookNameForPriceFilter();
    } // end getHookNameForPriceFilter
    
    public function getMethodNameForPriceFilter()
    {
        return $this->_adapter->getMethodNameForPriceFilter();
    } // end getMethodNameForPriceFilter

    public function getProductPrice($product)
    {
        return $product->get_price();
    } // end getProductPrice
    
    public function getSalePrice($product)
    {
        return $product->get_sale_price();
    } // end getSalePrice
    
    public function getVariationChildrenIDs($product)
    {
        return $product->get_children();
    } // end getVariationChildrenIDs
    
    public function getRegularPrice($product)
    {
        return $product->get_regular_price();
    } // end getRegularPrice

    public function setSalePrice($product, $salePrice)
    {
        return $this->_adapter->setSalePrice($product, $salePrice);
    } // end setSalePrice

    public function getSubscriptionSignUpFee($product)
    {
        return $this->_adapter->getSubscriptionSignUpFee($product);
    } // end getSubscriptionSignUpFee

    public function getSubscriptionPrice($product)
    {
        return $this->_adapter->getSubscriptionPrice($product);
    } // end getSubscriptionPrice
    
    public function getPriceHtml($product)
    {
        return $product->get_price_html();
    } // end getPriceHtml

    public function isProductPost($query)
    {
        $facade = EngineFacade::getInstance();

        $queryVars = $facade->getQueryVars($query);

        return !$facade->isAdminPanel() &&
                (array_key_exists('post_type', $queryVars) &&
                $queryVars['post_type'] == 'product' ||
                array_key_exists(static::PRODUCT_CATEGORY_KEY, $queryVars));
    } // end isProductPost

    public function doRemoveMetaOptionByKeyInProducts($metaKey)
    {
        $params = array(
            'post_type' => array('product', 'product_variation'),
            'meta_key' => $metaKey
        );

        $facade = EngineFacade::getInstance();

        $engineClassName = get_class($facade);

        $query = $engineClassName::createQueryInstance($params);

        while ($query->have_posts()) {

            $query->the_post();

            $idPost = $facade->getCurrentPostID();

            delete_post_meta($idPost, $metaKey);
        };
    } // end doRemoveMetaOptionByKeyInProducts

    public function doUpdateMetaOptionByKeyInProducts(
        $needleKey,
        $metaKey,
        $metaValue = false
    )
    {
        $params = array(
            'post_type' => array('product', 'product_variation'),
            'meta_key' => $needleKey
        );

        $facade = EngineFacade::getInstance();

        $engineClassName = get_class($facade);

        $query = $engineClassName::createQueryInstance($params);

        while ($query->have_posts()) {

            $query->the_post();

            $idPost = $facade->getCurrentPostID();

            $facade->updatePostMeta($idPost, $metaKey, $metaValue);
        };
    } // end doUpdateMetaOptionByKeyInProducts

    public function getProductsForRangeWidgetFilter()
    {
        return $this->_adapter->getProductsForRangeWidgetFilter();
    } // end getProductsForRangeWidgetFilter

    public function getTaxClasses($dafaultClass = true)
    {
        $taxClasses = WC_Tax::get_tax_classes();

        if (!$dafaultClass) {
            return $taxClasses;
        }

        $standardClass = array('Standard rate');

        $taxClasses = array_merge(
            $standardClass,
            $taxClasses
        );

        return $taxClasses;
    } // end getTaxClasses

    private function _getCountries()
    {
        $wooCommerce = $this->getWooCommerceInstance();

        return $wooCommerce->countries;
    } // end _getCountries

    public function getBaseCountry()
    {
        $countries = $this->_getCountries();

        return $countries->get_base_country();
    } // end getBaseCountry

    public function getBaseState()
    {
        $countries = $this->_getCountries();

        return $countries->get_base_state();
    } // end getBaseState

    public function getBasePostCode()
    {
        $countries = $this->_getCountries();

        return $countries->get_base_postcode();
    } // end getBasePostCode

    public function getBaseCity()
    {
        $countries = $this->_getCountries();

        return $countries->get_base_city();
    } // end getBaseCity

    public function findTaxRates($args = array())
    {
        return WC_Tax::find_rates($args);
    } // end findTaxRates

    public function getTaxDisplayMode($default = 'shop')
    {
        $optionName = $this->getHookNameForDisplayPicesTax($default);

        $facade = EngineFacade::getInstance();

        return $facade->getOption($optionName);
    } // end getTaxDisplayMode

    public function isDisplayPricesIncludeTax($default = 'shop')
    {
        $taxDisplayMode = $this->getTaxDisplayMode($default);

        return $taxDisplayMode == static::FESTI_DISPLAY_PRICES_INCLUDING_TAX;
    } // end isDisplayPricesIncludeTax

    public function getProductByID($idProduct)
    {
        return wc_get_product($idProduct);
    } // end getProductByID

    public function doSynchronizeProductVariations($idParent)
    {
        WC_Product_Variable::sync($idParent);

        wc_delete_product_transients($idParent);
    } // end doSynchronizeProductVariations

    public function isProductExists($product)
    {
        return $product->exists();
    } // end isProductExists

    public function getProductStatus($product)
    {
        return $product->get_status();
    } // end getProductStatus

    public function isAllowProductEdit($product)
    {
        $idProduct = $this->getProductID($product);

        $facade = EngineFacade::getInstance();

        return 'publish' === $this->getProductStatus($product) ||
                $facade->isCurrentUserCan('edit_post', $idProduct);
    } // end isAllowProductEdit

    public function getHookNameForDisplayPicesTax($default = 'shop')
    {
       return 'woocommerce_tax_display_'.$default;
    } // end getHookNameForDisplayPicesTax

    public function getTaxLocation($taxClass, $customer = null)
    {
        if (!is_object($customer)) {
            $customer = null;
        }

        return WC_Tax::get_tax_location($taxClass, $customer);
    } // end getTaxLocation

    public function getDisplayTaxHookNames()
    {
        $shopHookName = $this->getHookNameForDisplayPicesTax();

        $cartHookName = $this->getHookNameForDisplayPicesTax(
            'cart'
        );

        return array($shopHookName, $cartHookName);
    } // getDisplayTaxHookNames

    public function doStoreProductPropertiesByID($idProduct)
    {
        $product = $this->getProductByID($idProduct);

        $product->save();
    } // doStoreProductPropertiesByID
    
    public function getProductCsvExporter()
    {
        if (!$this->_isWooCommerceVersionSupportCsvImportExport()) {
            return null;
        }
        
        include_once WC_ABSPATH.
            'includes/export/class-wc-product-csv-exporter.php';
        
        return new WC_Product_CSV_Exporter();
    } // getProductCsvExporter
    
    public function getDefaultColumnNamesForExport()
    {
        $exporter = $this->getProductCsvExporter();
        
        if (!$exporter) {
            return false;            
        }
        
        return $exporter->get_default_column_names();
    } // end getDefaultColumnNamesForExport
    
    private function _isWooCommerceVersionSupportCsvImportExport()
    {
        $version = $this->_getCurrentVersion();
        
        return version_compare($version, '3.1.0', '>=');
    } // end _isWooCommerceVersionSupportCsvImportExport

    public function isWooCommerceProductExporterPage()
    {
        return array_key_exists('post_type', $_REQUEST) &&
               $_REQUEST['post_type'] == 'product' &&
               array_key_exists('page', $_REQUEST) &&
               $_REQUEST['page'] == 'product_exporter' ||
               $this->_isWooCommerceAjaxProductExport();
    } // end isWooCommerceProductExporterPage

    private function _isWooCommerceAjaxProductExport()
    {
        return array_key_exists('action', $_REQUEST) &&
               $_REQUEST['action'] == 'woocommerce_do_ajax_product_export';
    } // end _isWooCommerceAjaxProductExport

    public function removeTaxRate($idTaxRate)
    {
        WC_Tax::_delete_tax_rate($idTaxRate);
    } // end removeTaxRate

    public function addTaxRate($taxRate)
    {
        WC_Tax::_insert_tax_rate($taxRate);
    } // end addTaxRate

    public function getMetaQuery(WC_Query $query)
    {
        return $query->get_meta_query();
    } // end getMetaQuery
}