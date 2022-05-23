<?php

class WpmlCurrencyCompatibilityManager
{
    private $_woocommerceCurrencies;
    private $_plugin;
    
    const DEFAULT_CURRENCY_RATE = 1;
    const AUTO_CALCULATION_OPTION_VALUE = "0";
    const ACTION_PRIORITY = 13;
    const ACTION_ARGUMENTS_COUNT = 3;
    const CUSTOM_PRICE_CALCULATION_KEY = '_wcml_custom_prices';
    const PRICE_CALCULATION_STATUS_META_KEY = '_wcml_custom_prices_status';

    public function __construct($plugin)
    {
        $this->_plugin = $plugin;    
        
        $this->_doIncludeFacadeFiles();    
        
        $this->_plugin->addActionListener(
            'woocommerce_loaded',
            array($this, 'onWoocommerceCurrenciesSetAction')
        );  
    } // end __construct
    
    public function onInitBackendActionListeners()
    {           
        $this->_plugin->addActionListener(
            'woocommerce_product_options_pricing',
            array($this, 'onDisplayFieldsAfterPriceOptionsAction'),
            self::ACTION_PRIORITY
        );
        
        $this->_plugin->addActionListener(
            'woocommerce_product_after_variable_attributes',
            array($this, 'onDisplayFieldsAfterVariableAttributesAction'),
            self::ACTION_PRIORITY,
            self::ACTION_ARGUMENTS_COUNT
        );
    } // end onInitBackendActionListeners
    
    private function _doIncludeFacadeFiles()
    {
        $woocommercePath = PRICE_BY_ROLE_PLUGIN_DIR.'/common/woocommerce/';
        $wpmlPath = PRICE_BY_ROLE_PLUGIN_DIR.'/common/wpml/';
        $files = array(
            'WooCommerceFacade'     => $woocommercePath.'WooCommerceFacade.php',
            'WooCommerceWpmlFacade' => $wpmlPath.'WooCommerceWpmlFacade.php'
        );
        
        foreach ($files as $key => $file) {
            if (class_exists($key)) {
                unset($files[$key]);
            }
        }

        $this->_plugin->doIncludeFiles($files);
    } // end _doIncludeFacadeFiles
    
    public function onWooCommerceCurrenciesSetAction()
    {
        $this->_woocommerceCurrencies = WooCommerceFacade::getCurrencies();
    } // end onWooCommerceCurrenciesSetAction
    
    public function getCurrenciesData() 
    {
        $wpml = new WooCommerceWpmlFacade();
        
        return $wpml->getActiveCurrenciesData();
    } // end getCurrenciesData
    
    public function onDisplayFieldsAfterVariableAttributesAction(
        $loop, $data, $item
    )
    {
        $this->_displayPriceFieldsForCurrencyInVariableProduct($loop, $item);
    } // end onDisplayFieldsAfterVariableAttributesAction
    
    private function _displayPriceFieldsForCurrencyInVariableProduct(
        $loop, $item
    )
    {
        $wpmlCurrencies = $this->getCurrenciesData();
        
        $values = $this->_getRolesPrices($item->ID);
        
        $roles = $this->_plugin->getActiveRoles();
        
        $vars = array(
            'wpmlCurrencies'        => $wpmlCurrencies,
            'woocommerceCurrencies' => $this->_woocommerceCurrencies,
            'roles'                 => $roles,
            'prices'                 => $values,
            'loop'                  => $loop,
            'idPost'                => $item->ID,
            'compabilityManager'    => $this
        );

        echo $this->_plugin->fetch('wpml_currency_fields.phtml', $vars);
    } // end _displayPriceFieldsForCurrencyInVariableProduct
    
    public function isVariationLoop($loop)
    {
        return !($loop === false);
    } // end isVariationLoop

    private function _getRolesPrices($idPost)
    {        
        $values = $this->_plugin->getPostMeta(
            $idPost, 
            PRICE_BY_ROLE_PRICE_META_KEY, 
            true
        );

        if (!$values) {
            return false;
        }

        return json_decode($values, true);
    } // end _getRolesPrices
    
    private function _isRolePriceForCurrencyExist($prices, $role, $currency)
    {
        return $prices && $this->_isPriceExistForRole($prices, $role) &&
               $this->_isRolePriceExistForCurrency($prices, $role, $currency);
    } // end _isRolePriceForCurrencyExist
    
    public function getRolePriceForChosenCurrency($prices, $role, $currency)
    {
        if (!$this->_isRolePriceForCurrencyExist($prices, $role, $currency)) {
            return null;
        }
        
        return $prices[$role][$currency];
    } // end getRolePriceForChosenCurrency
    
    public function getRolePriceForDefaultCurrency($prices, $role)
    {
        if (!$prices || !$this->_isPriceExistForRole($prices, $role)) {     
            return null;       
        }
            
        return $prices[$role];
    } // end getRolePriceForDefaultCurrency
    
    private function _isPriceExistForRole($prices, $role) 
    {
        return array_key_exists($role, $prices);
    } // end _isPriceExistForRole
    
    private function _isRolePriceExistForCurrency($prices, $role, $currency)
    {
        return array_key_exists($currency, $prices[$role]);
    } // end _isRolePriceExistForCurrency
    
    public function displayInputField($args)
    {
        WooCommerceFacade::displayMetaTextInputField($args);
    } // end displayInputField
    
    public function displayHiddenInputField($args)
    {
        WooCommerceFacade::displayHiddenMetaTextInputField($args);
    } // end displayHiddenInputField
   
    public function onDisplayFieldsAfterPriceOptionsAction()
    {    
        $this->_displayPriceFieldsForCurrencyInSimpleProduct();
    } // end onDisplayFieldsAfterPriceOptionsAction
    
    private function _displayPriceFieldsForCurrencyInSimpleProduct()
    {
        $wpmlCurrencies = $this->getCurrenciesData();
        
        if (!$this->_hasPostIDInRequest()) {
            return false;
        }
        
        $idPost = $_GET['post'];
        
        if (!$this->_isValidID($idPost)) {
            return false;
        }
        
        $values = $this->_getRolesPrices($idPost);
        
        $roles = $this->_plugin->getActiveRoles();

        $vars = array(
            'wpmlCurrencies'        => $wpmlCurrencies,
            'woocommerceCurrencies' => $this->_woocommerceCurrencies,
            'roles'                 => $roles,
            'prices'                 => $values,
            'idPost'                => $idPost,
            'loop'                  => false,
            'compabilityManager'    => $this
        );

        echo $this->_plugin->fetch('wpml_currency_fields.phtml', $vars);
    } // end _displayPriceFieldsForCurrencyInSimpleProduct
    
    private function _hasPostIDInRequest()
    {
        return array_key_exists('post', $_GET);
    } // end _hasPostIDInRequest
    
    private function _isValidID($id)
    {
        return (!filter_var($id, FILTER_VALIDATE_INT) === false);
    } // end _isValidID
    
    public function getRoleNameWithCurrencyCode($code, $roleKey, $loop)
    {
        $wpmlRole = '['.$roleKey.'-currency'.']';   
        $currency = '['.$code.']';
          
        if (!$this->isVariationLoop($loop)) {
            $prefix = PRICE_BY_ROLE_PRICE_META_KEY;
            
            return $prefix.$wpmlRole.$currency;
        }
        
        $prefix = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        $loop = '['.$loop.']';
        
        return $prefix.$loop.$wpmlRole.$currency;
    } // end getRoleNameWithCurrencyCode
    
    public function getRoleIdWithCurrencyCode($code, $roleKey, $loop)
    {
        $wpmlRole = '_'.$roleKey.'-currency';
        $code = '_'.$code;    
            
        if (!$this->isVariationLoop($loop)) {
            $prefix = PRICE_BY_ROLE_PRICE_META_KEY;
             
            return $prefix.$wpmlRole.$code;
        } 
        
        $prefix = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        $loop = '_'.$loop;
        
        return $prefix.$loop.$wpmlRole.$code;
    } // end getRoleIdWithCurrencyCode
    
    private function _getPostCurrencyCalculationOption($idPost)
    {
        $metaKey = self::PRICE_CALCULATION_STATUS_META_KEY;

        $rolePrices = $this->_getPreparedRolePrices($idPost);

        if (isset($rolePrices[$metaKey])) {
            return $rolePrices[$metaKey];
        }

        $option = $this->_plugin->getPostMeta($idPost, $metaKey, true);

        if ($this->_isOptionEmpty($option)) {
            return false;
        }
        
        return $option;
    } // end _getPostCurrencyCalculationOption
    
    private function _isOptionEmpty($option)
    {
        return $option === "";
    } // end _isOptionEmpty

    private function _isPriceCalculatedAutomatically($id)
    {          
        $option = $this->_getPostCurrencyCalculationOption($id);
        
        return $option === self::AUTO_CALCULATION_OPTION_VALUE;
    } // end _isPriceCalculatedAutomatically
    
    public function getDefaultCurrencyCode()
    {       
        return WooCommerceFacade::getBaseCurrencyCode();
    } // end getDefaultCurrencyCode
    
    private function _getCurrencyRate($currency, $code) 
    {            
        if ($this->_isRateExist($currency, $code)) {
            return $currency[$code]['rate'];
        } 
        
        return self::DEFAULT_CURRENCY_RATE;
    } // end _getCurrencyRate
    
    private function _isRateExist($currency, $code)
    {
        if (!is_array($currency)) {
            return false;
        }
            
        return array_key_exists($code, $currency);
    } // end _isRateExist
    
    public function getCurrencySymbol($code) 
    {
        return WooCommerceFacade::getCurrencySymbol($code);
    } // end getCurrencySymbol

    public function getPrices($priceList, $roles, $id, $salePrice = false)
    {
        $code = WooCommerceFacade::getBaseCurrencyCode();
        $prices = array();

        foreach ($roles as $key => $role) {
            $wpmlRole = $role.'-currency';
            if (
                !$this->_hasRolePrice($priceList, $role) &&
                !$this->_hasRolePrice($priceList, $wpmlRole)
            ) {
                continue;
            }

            $price = $priceList[$role];

            if ($this->_hasRoleSalePrice($role, $priceList, $salePrice)) {
                $price = $priceList['salePrice'][$role];
            }

            if ($this->_isPriceCalculatedAutomatically($id)) {
                $currencies = $this->getCurrenciesData();
                $rate = $this->_getCurrencyRate($currencies, $code);
                $rolePrice = $this->_getPriceWithFixedFloat($price);
                $price = $rolePrice * $rate;
            } else if ($this->_hasPriceInCurrency(
                $priceList,
                $wpmlRole,
                $code
            )) {
                $price = $priceList[$wpmlRole][$code];
            }

            if ($this->_isPriceEqualsZero($price)) {
                $facade = EcommerceFactory::getInstance();
                $prices[] = $facade->getEmptyPriceSymbol();
                continue;
            }

            $prices[] = $this->_getPriceWithFixedFloat($price);
        }

        return $prices;
    } // end getPrices

    private function _isPriceEqualsZero($price)
    {
        return $price === '0';
    } // end _isPriceEqualsZero
    
    private function _hasRolePrice($priceList, $role)
    {        
        return array_key_exists($role, $priceList) &&
               !empty($priceList[$role]);
    } // end _hasRolePrice
    
    private function _hasPriceInCurrency($priceList, $wpmlRole, $code)
    {
        return array_key_exists($wpmlRole, $priceList) &&
               array_key_exists($code, $priceList[$wpmlRole]) &&
               !empty($priceList[$wpmlRole][$code]);
    } // end _hasPriceInCurrency
    
    private function _getPriceWithFixedFloat($price)
    {
        $price = str_replace(',', '.', $price);
        $price = floatval($price);

        return strval($price);
    } // end _getPriceWithFixedFloat

    private function _hasRoleSalePrice($role, $prices, $salePrice = false)
    {
        return $salePrice &&
               array_key_exists('salePrice', $prices) &&
               array_key_exists($role, $prices['salePrice']) &&
               $prices['salePrice'][$role] > 0;
    } // end _hasRoleSalePrice

    private function _getPreparedRolePrices($idPost)
    {
        $rolePrices = $this->_plugin->getPostMeta(
            $idPost,
            PRICE_BY_ROLE_PRICE_META_KEY,
            true
        );

        if (is_string($rolePrices)) {
            $rolePrices = json_decode($rolePrices, true);
        }

        return $rolePrices;
    } // end _getPreparedRolePrices
}