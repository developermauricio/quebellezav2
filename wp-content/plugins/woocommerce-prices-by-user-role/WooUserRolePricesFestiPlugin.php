<?php

class WooUserRolePricesFestiPlugin extends WpmlCompatibleFestiPlugin
{
    public $languageDomain = PRICE_BY_ROLE_LANGUAGE_DOMAIN;
    public $version = PRICE_BY_ROLE_VERSION;
    public $products;

    protected $optionsPrefix = PRICE_BY_ROLE_OPTIONS_PREFIX;
    protected $ecommerceFacade;

    public static $userRole = false;
    public static $isSalePrices = array();

    protected static $filterPrices = array();
    protected static $settings = array();
    protected static $corePlugin = null;

    const MAX_EXECUTION_TIME = 180;
    const FESTI_DEFAULT_TAX_KEY = 'defaultTax';
    const FESTI_EXCLUDE_ALL_TAX_KEY = 'excludeAllTax';
    const FESTI_EXCLUDE_TAX_IN_SHOP_KEY = 'excludeTaxInShop';
    const FESTI_EXCLUDE_TAX_IN_CART_AND_CHECKOUT_KEY =
        'excludeTaxInCartAndCheckout';

    const ID_USER_ORDER_OPTION_KEY = 'id_user_order';

    const ADD_ORDER_ITEM_HOOK = 'woocommerce_add_order_item';

    protected function onInit()
    {
        $this->addActionListener('woocommerce_init', 'onEcommercePlatformInit');

        $this->addActionListener('plugins_loaded', 'onLanguagesInitAction');

        if ($this->_isWoocommercePluginNotActiveWhenFestiPluginActive()) {
            $this->addActionListener(
                'admin_notices',
                'onDisplayInfoAboutDisabledWoocommerceAction' 
            );
            
            return false;
        }

        $this->onInitCompatibilityManager();

        $this->oInitWpmlManager();
   
        $this->addActionListener('wp_loaded', 'onInitStringHelperAction');
        
        if ($this->isWmplCurrenciesPluginActive()) {
            $this->_doIncludeWpmlCurrencyCompatibilityManager();
        }
        
        $this->ecommerceFacade = EcommerceFactory::getInstance();

        $this->addActionListener(
            'woocommerce_is_purchasable',
            'onProductPurchasable',
            10,
            2
        );

        $this->addActionListener(
            'woocommerce_variation_is_purchasable',
            'onProductPurchasable',
            10,
            2
        );

        parent::onInit();
    } // end onInit

    private function _doIncludeWpmlCurrencyCompatibilityManager()
    {
        $path = $this->pluginPath.'/common/wpml/';
        $name = 'WpmlCurrencyCompatibilityManager.php';
        $file = $path.$name;       
        $files = array($file);
       
        $this->doIncludeFiles($files);
    } // end _doIncludeWpmlCurrencyCompatibilityManager

    public function doIncludeFiles($files)
    {
        foreach ($files as $file) {
            if (!file_exists($file)) {
                $message = "File does not exist: ".$file;
                throw new Exception($message);
            }
            
            require_once($file);
        }
    } // end doIncludeFiles
    
    protected function onInitCompatibilityManager()
    {
        $fileName = 'CompatibilityManagerWooUserRolePrices.php';
        require_once $this->pluginPath.'common'.DIRECTORY_SEPARATOR.$fileName;

        $pluginMainFile = $this->pluginMainFile;
        new CompatibilityManagerWooUserRolePrices($pluginMainFile);
    } // end onInitCompatibilityManager
    
    protected function oInitWpmlManager()
    {
        new FestiWpmlManager(PRICE_BY_ROLE_WPML_KEY, $this->pluginMainFile);
    } // end oInitWpmlManager
    
    public function onInitStringHelperAction()
    {
        static::$userRole = $this->getUserRole();

        StringManagerWooUserRolePrices::start();
    } // end onInitStringHelperAction
    
    public function onInstall()
    {
        if (!$this->_isWoocommercePluginActive()) {
            $this->onDisplayInfoAboutDisabledWoocommerceAction();
            return false;
        }

        $plugin = $this->onBackendInit();
        
        $plugin->onInstall();
    } // end onInstall
    
    public function onBackendInit()
    {
        $fileName = 'WooUserRolePricesBackendFestiPlugin.php';
        require_once $this->pluginPath.$fileName;
        
        if (!class_exists("WooUserRoleDisplayPricesBackendManager")) {
            $fileName = 'WooUserRoleDisplayPricesBackendManager.php';
            require_once __DIR__.'/common/backend/'.$fileName;
        }
        
        $pluginMainFile = $this->pluginMainFile;

        return new WooUserRolePricesBackendFestiPlugin($pluginMainFile);
    } // end onBackendInit
    
    protected function onFrontendInit()
    {
        $fileName = 'WooUserRolePricesFrontendFestiPlugin.php';
        require_once $this->pluginPath.$fileName;
        $pluginMainFile = $this->pluginMainFile;

        return new WooUserRolePricesFrontendFestiPlugin($pluginMainFile);
    } // end onFrontendInit
    
    private function _isWoocommercePluginNotActiveWhenFestiPluginActive()
    {
        return $this->_isPricesByUserRolePluginActive() &&
               !$this->_isWoocommercePluginActive();
    } // end _isWoocommercePluginNotActiveWhenFestiPluginActive
    
    private function _isPricesByUserRolePluginActive()
    {
        $plugin = 'woocommerce-prices-by-user-role/plugin.php';

        return $this->isPluginActive($plugin);
    } // end _isPricesByUserRolePluginActive
    
    private function _isWoocommercePluginActive()
    {
        return $this->isPluginActive('woocommerce/woocommerce.php');
    } // end _isWoocommercePluginActive
    
    public function onLanguagesInitAction()
    {
        load_plugin_textdomain(
            $this->languageDomain,
            false,
            $this->pluginLanguagesPath
        );
    } // end onLanguagesInitAction
    
    public function getMetaOptions($id, $optionName)
    {
        $value = $this->getPostMeta($id, $optionName);

        if (!$value) {
            $optionName = strtolower($optionName);
            $value = $this->getPostMeta($id, $optionName);
        }

        if (!$value) {
            return false;
        }
        
        if (is_array($value)) {
            return $value;
        }
        
        $value = json_decode($value, true);
        
        return $value;
    } // end getMetaOptions
    
    public function getActiveRoles()
    {
        $options = $this->getOptions('settings');
        
        if (!$this->_hasActiveRoleInOptions($options)) {
            return false;
        }

        $wordpressRoles = $this->getUserRoles();
        
        $diff = array_diff_key($wordpressRoles, $options['roles']);

        return array_diff_key($wordpressRoles, $diff);
    } // end getActiveRoles
    
    private function _hasActiveRoleInOptions($options)
    {
        return array_key_exists('roles', $options);
    } // end _hasActiveRoleInOptions
    
    public function getUserRoles()
    {
        if (!$this->_hasRolesInGlobals()) {
            return false;
        }
        
        $roles = $GLOBALS['wp_roles'];

        return $roles->roles; 
    } // getUserRoles
    
    private function _hasRolesInGlobals()
    {
        return array_key_exists('wp_roles', $GLOBALS);   
    } // end _hasRolesInGlobals
    
    public function onDisplayInfoAboutDisabledWooCommerceAction()
    {        
        $message = 'The Prices By User Role plugin requires ';
        $message .= 'WooCommerce Plugin installed and activated.';

        $this->displayUpdate($message);
    } //end onDisplayInfoAboutDisabledWooCommerceAction
    
    public function updateMetaOptions($idPost, $value, $optionName)
    {
        $value = json_encode($value);

        $facade = EngineFacade::getInstance();

        $facade->updatePostMeta($idPost, $optionName, $value);
    } // end updateMetaOptions
    
    public function updateProductPrices($idPost, $prices)
    {
        $plugin = $this->getCorePluginInstance();

        return $plugin->updateProductPrices($idPost, $prices);
    } // end updateProductPrices
    
    public function getProductPrices($idProduct)
    {
        $plugin = $this->getCorePluginInstance();

        return $plugin->getProductPrices($idProduct);
    } // end getProductPrices
    
    public function isIgnoreDiscountForProduct($idProduct = false)
    {
        return (bool) $this->getMetaOptionsForProduct(
            $idProduct,
            PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY
        );
    } // end isIgnoreDiscountForProduct
    
    public function getMetaOptionsForProduct($idProduct, $optionName)
    {
        $plugin = $this->getCorePluginInstance();

        return $plugin->getMetaOptionsForProduct($idProduct, $optionName);
    } // end getMetaOptionsForProduct
    
    public function getWordpressPostInstance()
    {
        $facade = EngineFacade::getInstance();

        return $facade->getWordpressPostInstance();
    } // end getWordpressPostInstance

    public function getPostMeta($idPost, $key, $single = true)
    {
        $facade = EngineFacade::getInstance();

        return $facade->getPostMeta($idPost, $key, $single);
    } // end getPostMeta
    
    public function getUserRole($idUser = false)
    {
        if (static::$userRole) {
            return static::$userRole;
        }

        $roles = $this->getAllUserRoles($idUser);
    
        if (!$roles) {
            return false;
        }

        static::$userRole = array_shift($roles);
    
        return static::$userRole;
    } // end getUserRole
    
    public function getAllUserRoles($idUser = false)
    {
        if (!$idUser) {
            $idUser = $this->getUserID();
        }
    
        if (!$idUser) {
            return false;
        }
    
        $userData = get_userdata($idUser);

        if (!$userData) {
            return false;
        }
    
        return $userData->roles;
    } // end getAllUserRoles
    
    public function getUserID()
    {
        $facade = EngineFacade::getInstance();

        if ($this->_hasUserIDInOrder($facade)) {
            return $facade->getTransient(self::ID_USER_ORDER_OPTION_KEY);
        }

        return $facade->getCurrentUserID();
    } // end getUserID
    
    private function _hasUserIDInOrder($facade)
    {
        return $facade->isAjax() &&
               $facade->isAdministrationInterfaceRequest() &&
               $this->_isOrderItemRequest() &&
               $facade->getTransient(self::ID_USER_ORDER_OPTION_KEY);
    } // end _hasUserIDInOrder

    private function _isOrderItemRequest()
    {
        return isset($_REQUEST['action']) &&
               $_REQUEST['action'] == self::ADD_ORDER_ITEM_HOOK;
    } // end _isOrderItemRequest

    public function getRolePrice($idProduct, $idUser = false)
    {
        $plugin = $this->getCorePluginInstance();

        $price = $plugin->getRolePrice($idProduct, $idUser);

        $facade = EngineFacade::getInstance();

        $price = $facade->dispatchFilter(
            'festi_get_role_price',
            $price,
            $idProduct,
            $idUser
        );

        return $price;
    } // end getRolePrice

    public function getRoleSalePrice($idProduct, $idUser=false)
    {
        $plugin = $this->getCorePluginInstance();

        return $plugin->getRoleSalePrice($idProduct, $idUser);
    } // end getRoleSalePrice

    public function getPriceWithFixedFloat($price)
    {
        $price = str_replace(',', '.', $price);
        $price = floatval($price);

        return strval($price);
    } // end getPriceWithFixedFloat
    
    public function hasRolePriceInProductOptions($priceList, $role)
    {
        return array_key_exists($role, $priceList) && $priceList[$role];
    } // end hasRolePriceInProductOptions

    public function hasDiscountOrMarkUpForUserRoleInGeneralOptions(
        $userRole = false, $idUser = false
    )
    {
        if (!$userRole) {
            $userRole = $this->getUserRole($idUser);
        }
        
        if (!$userRole) {
            return false;
        }
    
        $settings = $this->getSettings();
    
        return array_key_exists('discountByRoles', $settings) && 
               array_key_exists($userRole, $settings['discountByRoles']) && 
               $settings['discountByRoles'][$userRole]['value'] != false;
    } // end hasDiscountOrMarkUpForUserRoleInGeneralOptions
    
    public function isDiscountOrMarkupEnabledByRole($role)
    {
        if (empty($role)) {
            return false;
        }

        $settings = $this->getSettings();
        
        if (!array_key_exists('discountByRoles', $settings)) {
            return false;
        }
        
        $discountByRoles = $settings['discountByRoles'];

        if (!array_key_exists($role, $discountByRoles)) {
            return false;
        }
        
        return (bool) $discountByRoles[$role]['value'];
    } // end isDiscountOrMarkupEnabledByRole

    public function getSettings()
    {
        if (static::$settings) {
            return static::$settings;
        }

        static::$settings = $this->getOptions('settings');

        if (!static::$settings) {
            throw new Exception('The settings can not be empty.');
        }

        return static::$settings;
    } // end getSettings
    
    public function getRolePricesVariableProductByPriceType($product, $type)
    {
        $plugin = $this->getCorePluginInstance();

        return $plugin->getRolePricesVariableProductByPriceType(
            $product,
            $type
        );
    } // end getRolePricesVariableProductByPriceType

    public function isIncludingTaxesToPrice()
    {
        $facade = EcommerceFactory::getInstance();
        
        return $facade->isEnabledTaxCalculation() &&
               !$facade->isPricesEnteredWithTax();
    } // end isIncludingTaxesToPrice
    
    public function hasRoleRegularPriceByVariableProduct($product)
    {
        $rolePrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            PRICE_BY_ROLE_TYPE_PRODUCT_REGULAR_PRICE
        );

        return (bool) $rolePrices;
    } // end hasRoleRegularPriceByVariableProduct
    
    public function hasRoleSalePriceByVariableProduct($product)
    {
        $rolePrices = $this->getRolePricesVariableProductByPriceType(
            $product,
            PRICE_BY_ROLE_TYPE_PRODUCT_SALE_PRICE
        );

        return (bool) $rolePrices;
    } // end hasRoleSalePriceByVariableProduct

    protected function getProductsInstances()
    {
        return new FestiWooCommerceProduct($this);
    } // end getProductsInstances
    
    protected function onFilterPriceByRolePrice()
    {
        $this->products->onFilterPriceByRolePrice();
    } // end onFilterPriceByRolePrice
    
    protected function onFilterPriceByDiscountOrMarkup()
    {
        $this->products->onFilterPriceByDiscountOrMarkup();
    } // end onFilterPriceByDiscountOrMarkup
    
    public function onDisplayPriceByRolePriceFilter($price, $product)
    {
        $id = $this->ecommerceFacade->getProductID($product);

        if (!empty(static::$filterPrices[$id])) {
            return static::$filterPrices[$id];
        }

        $priceFacade = UserRolePriceFacade::getInstance();

        if (!$this->isSupportedProductType($product)) {
            return $price;
        }

        $price = $priceFacade->getPriceByRolePriceFilter(
            $price,
            $product,
            $this
        );

        $price = $this->_getQuantityDiscountByUserRole($price);

        static::$filterPrices[$id] = $price;
        
        return $price;
    } // end onDisplayPriceByRolePriceFilter

    public function hasUserRoleInActivePluginRoles()
    {
        $roles = $this->getAllUserRoles();
        
        if (!$roles) {
            return false;
        }
        
        $activeRoles = $this->getActiveRoles();

        if (!$activeRoles) {
            return false;
        }

        return $this->_hasOneOfUserRolesInActivePluginRoles(
            $activeRoles,
            $roles
        );
    } // end hasUserRoleInActivePluginRoles
    
    private function _hasOneOfUserRolesInActivePluginRoles($activeRoles, $roles)
    {
        foreach ($roles as $key => $role) {
            $result = array_key_exists($role, $activeRoles);
            
            if ($result) {
                return $result;
            }
        }

        return false;
    } // end _hasOneOfUserRolesInActivePluginRoles

    public function getProductNewInstance($product)
    { 
        $params = array(
            'product_type' => $this->ecommerceFacade->getProductType($product)
        );
        
        $idProduct = $this->getProductIDFromProductInstance($product);
       
        if (!$idProduct) {
            throw new Exception('Undefined product Id');
        }

        return $this->createProductInstance($idProduct, $params);
    } // end getProductNewInstance
    
    protected function getProductIDFromProductInstance($product)
    {
        $facade = &$this->ecommerceFacade;

        if ($this->_hasVariationIDInProductInstance($product)) {
            $idProduct = $facade->getVariationProductID($product);
        } else {
            $idProduct = $facade->getProductID($product);
        }
        
        return $idProduct;
    } // end getProductIDFromProductInstance
    
    private function _hasVariationIDInProductInstance($product)
    {
        return (bool) $this->ecommerceFacade->getVariationProductID($product);
    } // end _hasVariationIDInProductInstance

    public function createProductInstance($idProduct)
    {
        $facade = $this->ecommerceFacade;

        return $facade->getProductByID($idProduct);
    } // end createProductInstance
    
    public function getPrice($product)
    {
        return $this->products->getRolePrice($product);
    } // end getPrice
    
    public function getSalePrice($product)
    {
        return $this->products->getRoleSalePrice($product);
    } // end getSalePrice
    
    public function isRegisteredUser()
    {
        if (!static::$userRole) {
            return false;
        }

        return static::$userRole;
    } // end isRegisteredUser
    
    public function isVariableTypeProduct($product)
    {
        return $this->ecommerceFacade->getProductType($product) == 'variable';
    }  // end isVariableTypeProduct
    
    public function getPriceWithDiscountOrMarkUp(
        $product, $originalPrice, $isSalePrice = true
    )
    {
        if (!$this->isSupportedProductType($product)) {
            return $originalPrice;
        }

        $plugin = $this->getCorePluginInstance();

        $price = $plugin->getPriceWithDiscountOrMarkUp(
            $product,
            $originalPrice,
            $isSalePrice
        );

        return $this->_getQuantityDiscountByUserRole($price);
    } // end getPriceWithDiscountOrMarkUp

    public function getAmountOfDiscountOrMarkUp()
    {
        $settings = $this->getSettings();

        if (!$this->_hasOptionByDiscountRoles('value', $settings)) {
            return false;
        }

        $userRole = $this->getUserRole();

        return $settings['discountByRoles'][$userRole]['value'];
    } // end getAmountOfDiscountOrMarkUp

    private function _hasOptionByDiscountRoles(
        $option,
        $settings,
        $optionKey = 'discountByRoles'
    )
    {
        $userRole = $this->getUserRole();

        return array_key_exists($optionKey, $settings) &&
               array_key_exists($userRole, $settings[$optionKey]) &&
               array_key_exists($option, $settings[$optionKey][$userRole]);
    } // end _hasOptionByDiscountRoles

    public function isPercentDiscountType($optionKey = 'discountByRoles')
    {
        $settings = $this->getSettings();

        $settings = $this->getPreparedQuantityDiscountOptions($settings);

        if (!$this->_hasOptionByDiscountRoles('type', $settings, $optionKey)) {
            return false;
        }

        $discountType = $settings[$optionKey][static::$userRole]['type'];

        return $discountType == PRICE_BY_ROLE_PERCENT_DISCOUNT_TYPE;
    } // end isPercentDiscountType
    
    public function isRolePriceDiscountTypeEnabled()
    {
        $settings = $this->getSettings();

        $userRole = $this->getUserRole();

        if (!$settings) {
            return false;
        }
        
        if (!isset($settings['discountByRoles'][$userRole]['priceType'])) {
            return false;
        }
        
        $priceType = $settings['discountByRoles'][$userRole]['priceType'];
        
        return $priceType == PRICE_BY_ROLE_DISCOUNT_TYPE_ROLE_PRICE;
    } // end isRolePriceDiscountTypeEnabled
    
    public function isAllowSalePrices($product)
    {
        return $this->isEnableBothRegularSalePriceSetting() && 
               $this->hasSalePrice($product);
    } // end isAllowSalePrices
    
    protected function isEnableBothRegularSalePriceSetting()
    {
        $settings = $this->getSettings();

        return array_key_exists('bothRegularSalePrice', $settings) && 
               $settings['bothRegularSalePrice'] &&
               $this->hasDiscountOrMarkUpForUserRoleInGeneralOptions();
    } // end isEnableBothRegularSalePriceSetting
    
    protected function hasSalePrice($product)
    {
        return (bool) $this->ecommerceFacade->getSalePrice($product);
    } // end hasSalePrice
    
    public function getAmountOfDiscountOrMarkUpInPercentage($price, $discount)
    {
        return floatval($price) / 100 * $discount;
    } // end getAmountOfDiscountOrMarkUpInPercentage
    
    public function isDiscountTypeEnabled()
    {
        $settings = $this->getSettings();

        return $settings['discountOrMakeUp'] == 'discount';
    } // end isDiscountTypeEnabled

    public function onDisplayPriceByDiscountOrMarkupFilter($price, $product)
    {
        $product = $this->getProductNewInstance($product);

        if (!$this->isRegisteredUser()) {
            return $price;
        }

        $newPrice = $this->getPriceWithDiscountOrMarkUp($product, $price);

        return $this->getPriceWithFixedFloat($newPrice);
    } // end onDisplayPriceByDiscountOrMarkupFilter

    public function hasHideAllProductsOptionInSettings()
    {
        $settings = $this->getSettings();

        return array_key_exists('hideAllProducts', $settings) &&
               $settings['hideAllProducts'];
    } //end hasHideAllProductsOptionInSettings

    public function onProductPurchasable($result, $product)
    {
        $facade = $this->ecommerceFacade;

        $idProduct = $facade->getProductID($product);

        $regularPrice = $facade->getRegularPrice($product);

        if ($regularPrice) {
            return $result;
        }

        $rolePrice = $this->getRolePrice($idProduct);

        if (!$rolePrice) {
            return $result;
        }

        return $facade->isProductExists($product) &&
               $facade->isAllowProductEdit($product);
    } // end onProductPurchasable

    public function isEnabledUserRoleTaxOptions()
    {
        $facade = EcommerceFactory::getInstance();

        if (!$facade->isEnabledTaxCalculation()) {
            return false;
        }

        $settings = $this->getSettings();

        return array_key_exists('taxOptions', $settings) &&
               $settings['taxOptions'];
    } //end isEnabledUserRoleTaxOptions

    public function getTaxByUserRoleOptions()
    {
        $settings = $this->getSettings();

        $userRole = $this->getUserRole();

        if (!$userRole) {
            return false;
        }

        $taxByUserRoles = $settings['taxByUserRoles'];

        if ($this->hasUserRoleInOptions($userRole, $taxByUserRoles)) {
            return $taxByUserRoles[$userRole];
        }

        return false;
    } //end getTaxByUserRoleOptions

    public function hasUserRoleInOptions($userRole, $options)
    {
        return array_key_exists($userRole, $options);
    } //end hasUserRoleInOptions

    public function doRestoreDefaultDisplayTaxValues()
    {
        $plugin = $this->getFestiModuleInstance(
            ModulesSwitchListener::TAXES_PLUGIN
        );

        $plugin->doRestoreDefaultDisplayTaxValues();
    } // doRestoreDefaultDisplayTaxValues

    public function isUserRoleDisplayTaxOptionExist()
    {
        $facade = EngineFacade::getInstance();

        return $facade->getOption(PRICE_BY_ROLE_TAX_DISPLAY_OPTIONS);
    } // end isUserRoleDisplayTaxOptionExist

    private function _getMaxExecutionTime()
    {
        return ini_get('max_execution_time');
    } // end _getMaxExecutionTime

    protected function isMaxExecutionTimeLowerThanConstant()
    {
        $executionTime = static::MAX_EXECUTION_TIME;

        return $this->_getMaxExecutionTime() < $executionTime;
    } // end isMaxExecutionTimeLowerThanConstant

    /**
     * Returns absolute path to the engine.
     *
     * @return string
     */
    public function getBasePath()
    {
        return ENGINE_PATH;
    } // end getBasePath

    public function getTemplatePath($fileName)
    {
        return $this->getBasePath().'templates'.DIRECTORY_SEPARATOR.$fileName;
    } // end getTemplatesPath

    public function fetch($template, $vars = array())
    {
        if ($vars) {
            extract($vars);
        }

        ob_start();

        if (file_exists($template)) {
            $templatePath = $template;
        } else {
            $templatePath = $this->getTemplatePath($template);
        }

        include $templatePath;

        return ob_get_clean();
    } // end fetch

    public function getCorePluginInstance()
    {
        if (!isset(static::$corePlugin)) {
            static::$corePlugin = $this->getFestiModuleInstance(
                ModulesSwitchListener::CORE_PRICES_PLUGIN
            );
        }

        return static::$corePlugin;
    } // end getCorePluginInstance

    protected function getFestiModuleInstance($pluginName)
    {
        $core = Core::getInstance();

        $module = null;

        if ($pluginName) {
            $module = $core->getPluginInstance($pluginName);
        }

        return $module;
    } // end getFestiModuleInstance

    public function hasProductID($product)
    {
        return (bool) $this->ecommerceFacade->getProductID($product);
    } // end hasProductID

    public function onInitFestiProducts()
    {
        static::$userRole = $this->getUserRole();

        if (!isset($this->products)) {
            $this->products = $this->getProductsInstances();
        }
    } // end onInitFestiProducts

    public function onInitUserRoleTaxManager()
    {
        $plugin = $this->getFestiModuleInstance(
            ModulesSwitchListener::TAXES_PLUGIN
        );

        if ($this->isEnabledUserRoleTaxOptions()) {
            $plugin->onInitTaxListeners();
        }

        $plugin->onInitEcommerceSettingsTaxListener();
    } // end onInitUserRoleTaxManager

    public function onEcommercePlatformInit()
    {
        $this->onInitFestiProducts();
        $this->onInitUserRoleTaxManager();
    } // end onEcommercePlatformInit

    public function isUserRoleExist($userRole)
    {
        $userRoles = $this->getUserRoles();

        return array_key_exists($userRole, $userRoles);
    } // end isUserRoleExist

    protected function isTestEnvironmentDefined()
    {
        return $this->engineFacade->isTestEnvironmentDefined();
    } // end isTestEnvironmentDefined

    private function _getQuantityDiscountByUserRole($price)
    {
        if (!$this->_hasQuantityDiscountForUserRole($price)) {
            return $price;
        }

        $plugin = $this->getFestiModuleInstance(
            ModulesSwitchListener::QUANTITY_DISCOUNT_PLUGIN
        );

        return $plugin->getQuantityDiscountByUserRole($price);
    } // end _getQuantityDiscountByUserRole

    private function _hasQuantityDiscountForUserRole($price)
    {
        if (!$price) {
            return false;
        }

        $name = ModulesSwitchListener::QUANTITY_DISCOUNT_PLUGIN;

        return $this->_isFrontend() &&
               $this->getUserRole() &&
               $this->isModuleExist($name);
    } // end _hasQuantityDiscountForUserRole

    public function getPreparedQuantityDiscountOptions($options)
    {
        $key = SettingsWooUserRolePrices::QUANTITY_DISCOUNT_OPTION_KEY;

        if (!isset($options[$key])) {
            return $options;
        }

        $quantityDiscountRange = array();

        $userRoleNames = $this->getUserRoleNames();

        $quantityDiscount = $options[$key];

        foreach ($quantityDiscount as $value) {
            $index = $value['userRole'];
            $name = $userRoleNames[$index];
            if ($value['isVisible']) {
                $quantityDiscountRange[$name] = $value;
            }
        }

        $options[$key] = $quantityDiscountRange;

        return $options;
    } // end getPreparedQuantityDiscountOptions

    private function _isFrontend()
    {
        return get_class($this) == 'WooUserRolePricesFrontendFestiPlugin';
    } // end _isFrontend

    public function isModuleExist($name)
    {
        $pluginPath = $name.DIRECTORY_SEPARATOR.$name.'Plugin.php';

        return file_exists(PRICE_BY_ROLE_EXTENSIONS_DIR.$pluginPath);
    } // end isModuleExist

    protected function getUserRoleNames()
    {
        $userRoles = $this->getUserRoles();

        return array_keys($userRoles);
    } // end getUserRoleNames

    protected function isSupportedProductType($product)
    {
        $type = $this->products->getProductType($product);

        return $type != FestiWooCommerceProduct::UNKNOWN_PRODUCT_TYPE;
    } // end isSupportedProductType
}