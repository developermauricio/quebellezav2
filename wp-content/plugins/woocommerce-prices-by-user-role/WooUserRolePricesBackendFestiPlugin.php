<?php

class WooUserRolePricesBackendFestiPlugin extends WooUserRolePricesFestiPlugin
{
    protected $menuOptions = array(
        PRICE_BY_ROLE_SETTINGS_PAGE_SLUG => 'General',
        'priceAdjustmentsTab' => 'Price Adjustments',
        'hidingRulesTab' => 'Hiding Rules',
        //'modules' => 'Modules'
    );

    const MENU_OPTIONS_CAPABILITY = 'manage_woocommerce';

    protected function onInit()
    {
        $this->ecommerceFacade = EcommerceFactory::getInstance();

        $this->addActionListener('admin_menu', 'onAdminMenuAction', 100);
        
        $this->addActionListener(
            'wp_ajax_onSetUserIDForAjaxAction',
            'onSetUserIDForAjaxAction'
        );
        
        $this->addActionListener(
            'woocommerce_product_write_panel_tabs',
            'onAppendTabToAdminProductPanelAction'
        );

        $this->addActionListener(
            $this->ecommerceFacade->getHookNameForWritePanels(),
            'onAppendTabContentToAdminProductPanelAction'
        );
        
        $this->addActionListener(
            'woocommerce_product_options_pricing',
            'onAppendFieldsToSimpleOptionsAction'
        );
        
        $this->addActionListener(
            'woocommerce_product_after_variable_attributes',
            'onAppendFieldsToVariableOptionsAction',
            11,
            3
        );
        
        $this->addActionListener(
            'woocommerce_process_product_meta',
            'onUpdateProductMetaOptionsAction'
        );

        $this->addActionListener(
            'woocommerce_process_product_meta',
            'onUpdateAllTypeProductMetaOptionsAction'
        );

        $this->addActionListener(
            'woocommerce_save_product_variation',
            'onUpdateVariableProductMetaOptionsAction',
            10,
            2
        );
        
        $this->addActionListener(
            'admin_print_styles', 
            'onInitCssForWooCommerceProductAdminPanelAction'
        );
        
        $this->addActionListener(
            'admin_print_scripts', 
            'onInitJsForWooCommerceProductAdminPanelAction'
        );
        
        $this->addFilterListener(
            'plugin_action_links_woocommerce-prices-by-user-role/plugin.php',
            'onFilterPluginActionLinks'
        );
        
        $this->addActionListener(
            'bulk_edit_custom_box',
            'onInitHideProductFieldForBulkEdit',
            10,
            2
        );
        
        $this->addActionListener(
            'wp_ajax_onHideProductsByRoleAjaxAction',
            'onHideProductsByRoleAjaxAction'
        );
        
        $this->addActionListener(
            'wp_ajax_'.self::ADD_ORDER_ITEM_HOOK,
            'onInitPriceFiltersAction'
        );

        $this->addActionListener('wp_loaded', 'onInitUserRole');

        $this->addActionListener(
            'woocommerce_update_options_tax',
            'onUserRoleUpdateDisplayTax'
        );

        $this->addActionListener(
            'upgrader_package_options',
            'onModifyPackageInstallerOptions'
        );

        $this->addActionListener(
            'upgrader_process_complete',
            'onUpgradeProcessComplete',
            10,
            2
        );

        if ($this->isWpmlMultiCurrencyOptionOn()) {
            $wmplCurrencyManager = new WpmlCurrencyCompatibilityManager($this);
            $wmplCurrencyManager->onInitBackendActionListeners();
        }

        $envato = $this->_getEnvatoUtilInstance();
        $envato->displayLicenseNotice();

        $facade = EngineFacade::getInstance();

        $facade->registerDeactivationHook(
            array(&$this, 'onUninstall'),
            $this->pluginMainFile
        );

        if ($this->ecommerceFacade->isWooCommerceProductExporterPage()) {
            $this->addActionListener('woocommerce_init', 'onInitExportManager');
        }
    } // end onInit

    public function onSetUserIDForAjaxAction()
    {
        $result = array('status' => false);

        $facade = EngineFacade::getInstance();

        if (!$this->_hasUserIDInRequest()) {
            $facade->doSendJson($result);
        }

        $result['status'] = $facade->setTransient(
            self::ID_USER_ORDER_OPTION_KEY,
            $_POST['idUser'],
            HOUR_IN_SECONDS
        );

        $facade->doSendJson($result);
    } // end onSetUserIDForAjaxAction
    
    public function onInitCssForWooCommerceProductAdminPanelAction()
    {
        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-product-admin-panel-styles',
            'product_admin_panel.css',
            array(),
            $this->version
        );
        
        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-product-admin-panel-tooltip',
            'tooltip.css',
            array(),
            $this->version
        );
    } // end onInitCssForWooCommerceProductAdminPanelAction
    
    public function onInitJsForWooCommerceProductAdminPanelAction()
    {
        $this->onEnqueueJsFileAction('jquery');

        $this->onEnqueueJsFileAction(
            'festi-checkout-steps-wizard-tooltip',
            'tooltip.js',
            'jquery',
            $this->version
        );
        
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-product-admin-panel-tooltip',
            'product_admin_panel.js',
            'jquery',
            $this->version
        );
        
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-product-admin-add-new-order',
            'add_new_order.js',
            'jquery',
            $this->version,
            true
        );

        $vars = array(
            'ajaxurl' => $this->engineFacade->getAdminUrl('admin-ajax.php')
        );

        $this->engineFacade->doLocalizeScript(
            'festi-user-role-prices-product-admin-add-new-order',
            'fesiWooPriceRole',
            $vars
        );
    } // end onInitJsForWooCommerceProductAdminPanelAction
    
    public function onAppendTabToAdminProductPanelAction()
    {
        if ($this->isAllowToDisplayPluginOptionsOnProductAdminPanel()) {
            echo $this->fetch('product_tab.phtml');
        }
    } // end onAppendTabToAdminProductPanelAction

    public function onAppendTabContentToAdminProductPanelAction()
    {
        $settings = $this->getOptions('settings');

        if (!is_array($settings)) {
            $settings = array();
        }

        $vars = array(
            'onlyRegisteredUsers' => $this->getValueFromProductMetaOption(
                'onlyRegisteredUsers'
            ),
            'hidePriceForUserRoles' => $this->getValueFromProductMetaOption(
                'hidePriceForUserRoles'
            ),
            'settings' => $settings,
            'settingsForHideProduct' => $this->_getSettingsForHideProduct(),
            'isIgnoreHideOptionByProduct' =>
                $this->_isIgnoreHiddenAllProductsOptionOn()
        );

        echo $this->fetch('product_tab_content.phtml', $vars);
    } // end onAppendTabContentToAdminProductPanelAction
    
    private function _getSettingsForHideProduct()
    {
        $hideProductForUserRoles = $this->getMetaOptionsForProduct(
            false,
            PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY
        );

        $settings = array(
            'hideProductForUserRoles' => $hideProductForUserRoles,
            'roles' => $this->getUserRoles()
        );

        if ($this->_isIgnoreHiddenAllProductsOptionOn()) {

            $newSettings = $this->_getPreparedSettingsForNewUserRoles(
                $settings['hideProductForUserRoles']
            );

            $settings['hideProductForUserRoles'] = $newSettings;
        }

        return $settings;
    } // end _getSettingsForHideProduct
    
    public function onInitHideProductFieldForBulkEdit($columnName, $postType)
    {
        if (!$this->_isBulkEditForProducts($columnName, $postType)) {
            return true;
        }
        
        $vars = array(
            'roles' => $this->getUserRoles()
        );
        echo $this->fetch('bulk_edit_hide_product.phtml', $vars);
    } // end onInitHideProductFieldForBulkEdit
    
    private function _isBulkEditForProducts($columnName, $postType)
    {
        return $columnName == 'price' && $postType == 'product';
    } // end _isBulkEditForProducts
    
    public function onHideProductsByRoleAjaxAction()
    {
        if ($this->_hasPostIDsInRequest()) {
            $postIDs = $_POST['postIDs'];    
            $formBulkEdit = array();
            parse_str($_POST['form'], $formBulkEdit);
            $this->_doUpdateHideProductsForBulkEditForm(
                $postIDs,
                $formBulkEdit
            );
        }
    } // end onHideProductsByRoleAjaxAction
    
    private function _doUpdateHideProductsForBulkEditForm(
        $postIDs, $formBulkEdit
    )
    {
        if ($postIDs && is_array($postIDs)) {
            foreach ($postIDs as $id) {
                $this->updateMetaOptions(
                    $id,
                    $formBulkEdit[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY],
                    PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY
                );
                $this->_doUpdateHideProductOptions($id, $formBulkEdit);
            }
        }
    } // end _doUpdateHideProductsForBulkEditForm
    
    private function _hasPostIDsInRequest()
    {
        return array_key_exists('postIDs', $_POST) &&
               !empty($_POST['postIDs']);
    } // end _hasPostIDsInRequest
    
    public function hasOnlyRegisteredUsersOptionInPluginSettings($settings)
    {
        return array_key_exists('onlyRegisteredUsers', $settings);
    } // end _hasOnlyRegisteredUsersOptionInPluginSettings
    
    public function hasRoleInHidePriceForUserRolesOption(
        $settings, $role
    )
    {
        return array_key_exists('hidePriceForUserRoles', $settings) &&
               array_key_exists($role, $settings['hidePriceForUserRoles']);
    } // end hasOnlyRegisteredUsersOptionInPluginSettings
    
    public function getValueFromProductMetaOption($optionName)
    {
        $options = $this->getMetaOptionsForProduct(
            false,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );

        if (!$this->_hasItemInOptionsList($optionName, $options)) {
            return false;
        }
        
        return $options[$optionName];
    } //end getValueFromProductMetaOption
    
    private function _hasItemInOptionsList($optionName, $options)
    {
        return array_key_exists($optionName, $options);
    } //end _hasItemInOptionsList

    public function onUpdateVariableProductMetaOptionsAction(
        $idVariation, $loop
    )
    {
        $this->_updateIgnoreDiscountMetaOption($idVariation);
        
        $metaKey = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        
        if (!$this->_hasVariableItemInRequest($loop)) {
            $_POST[$metaKey][$loop] = array();
        }
        
        $value = $_POST[$metaKey][$loop];
        
        $this->updateProductPrices($idVariation, $value);
    } // end onUpdateVariableProductMetaOptionsAction

    public function onUpdateProductMetaOptionsAction($idPost)
    {
        if (!$this->_hasHidePriceProductOptionsInRequest()) {
            $_POST[PRICE_BY_ROLE_HIDDEN_RICE_META_KEY] = array();
        }

        $this->updateMetaOptions(
            $idPost,
            $_POST[PRICE_BY_ROLE_HIDDEN_RICE_META_KEY],
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
        
        if (!$this->_hasHideProductOptionsInRequest($_POST)) {
            $_POST[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY] = array();
        }
        $this->updateMetaOptions(
            $idPost,
            $_POST[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY],
            PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY
        );
        
        $this->_doUpdateHideProductOptions($idPost, $_POST);
    } // end onUpdateProductMetaOptionsAction
    
    private function _doUpdateHideProductOptions($idPost, $data)
    {
        $hiddenProductsByRole = $this->getOptions(
            PRICE_BY_ROLE_HIDDEN_PRODUCT_OPTIONS
        );
        
        if (!$hiddenProductsByRole) {
            $hiddenProductsByRole = array();
        }
        
        if ($this->_hasHideProductOptionsInRequest($data)) {
            $slectedRoles = $data[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY];
            
            foreach ($slectedRoles as $key => $item) {    
                $slectedRoles[$key] = array($idPost);
            }
            
            $hiddenProductsByRole = $this->_doRemoveIdPostInHideProductOptions(
                $idPost,
                $hiddenProductsByRole
            );

            $hiddenProductsByRole = $this->_doPrepareHidePostsOptions(
                $slectedRoles,
                $hiddenProductsByRole
            );            
            
        } else {
            $hiddenProductsByRole = $this->_doRemoveIdPostInHideProductOptions(
                $idPost,
                $hiddenProductsByRole
            );
        }

        $this->updateOptions(
            PRICE_BY_ROLE_HIDDEN_PRODUCT_OPTIONS,
            $hiddenProductsByRole
        );

        if ($this->hasHideAllProductsOptionInSettings()) {
            $this->_setProductsAndCategoriesVisibilityByID($idPost, $data);
        }
    } // end _doUpdateHideProductOptions
    
    private function _doPrepareHidePostsOptions($roles, $options)
    {
        $options = array_merge_recursive($roles, $options);
            
        foreach ($options as $key => $item) {
            if (is_array($item)) {
                $options[$key] = array_unique($item, SORT_NUMERIC);
            }
                
        } 
        return $options;
    } // end _doPrepareHidePostsOptions
    
    private function _doRemoveIdPostInHideProductOptions($idPost, $options)
    {
        foreach ($options as $role => $postIDs) {
            if (is_array($postIDs)) {
                foreach ($postIDs as $key => $id) {
                   if ($id == $idPost) {
                       unset($options[$role][$key]);
                   }
                }
            }
        }
        return $options;
    } // end _doRemoveIdPostInHideProductOptions
    
    private function _hasHidePriceProductOptionsInRequest()
    {
        return array_key_exists(PRICE_BY_ROLE_HIDDEN_RICE_META_KEY, $_POST) &&
               !empty($_POST[PRICE_BY_ROLE_HIDDEN_RICE_META_KEY]);
    } // end _hasHidePriceProductOptionsInRequest
    
    private function _hasHideProductOptionsInRequest($data)
    {
        return array_key_exists(PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY, $data) &&
               !empty($data[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY]);
    } // end _hasHidePriceProductOptionsInRequest
    
    private function _hasIgnoreDiscountOptionInRequest($idPost)
    {
        $key = PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY;

        return array_key_exists($key, $_POST) && 
               array_key_exists($idPost, $_POST[$key]) &&
               !empty($_POST[$key][$idPost]);
    } // end _hasIgnoreDiscountOptionInRequest
    
    private function _hasRolePriceProductOptionsInRequest()
    {
        return array_key_exists(PRICE_BY_ROLE_PRICE_META_KEY, $_POST) &&
               !empty($_POST[PRICE_BY_ROLE_PRICE_META_KEY]);
    } // end _hasRolePriceProductOptionsInRequest
    
    public function getSelectorClassForDisplayEvent($class)
    {
        $selector = $class.'-visible';
        
        $options = $this->getOptions('settings');
                
        if (!isset($options[$class]) || $options[$class] == 'disable') {
            $selector.=  ' festi-user-role-prices-hidden ';
        }
        
        return $selector;
    } // end getSelectorClassForDisplayEvent
    
    private function _hasVariableItemInRequest($loop)
    {
        $metaKey = PRICE_BY_ROLE_VARIATION_RICE_KEY;
        
        return array_key_exists($metaKey, $_POST) &&
               array_key_exists($loop, $_POST[$metaKey]);
    } // end _hasVariableItemInRequest
    
    public function onUpdateAllTypeProductMetaOptionsAction($idPost)
    {
        $this->_updateIgnoreDiscountMetaOption($idPost);
        
        if (!$this->_hasRolePriceProductOptionsInRequest()) {
            $_POST[PRICE_BY_ROLE_PRICE_META_KEY] = array();
        }

        if ($this->_hasAutomaticCurrencyCalculationInRequest()) {
            $wpml = 'WpmlCurrencyCompatibilityManager';
            $value = $wpml::AUTO_CALCULATION_OPTION_VALUE;
            $key = $wpml::PRICE_CALCULATION_STATUS_META_KEY;
            $_POST[PRICE_BY_ROLE_PRICE_META_KEY][$key] = $value;
        }

        $this->updateProductPrices(
            $idPost, 
            $_POST[PRICE_BY_ROLE_PRICE_META_KEY]
        );
    } // end onUpdateAllTypeProductMetaOptionsAction
    
    private function _updateIgnoreDiscountMetaOption($idPost)
    {
        $value = (int) $this->_hasIgnoreDiscountOptionInRequest($idPost);
        $this->updateMetaOptions(
            $idPost,
            $value,
            PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY
        );
    } // end _updateIgnoreDiscountMetaOption
    
    public function onAppendFieldsToSimpleOptionsAction()
    {
        if (!$this->isAllowToDisplayPluginOptionsOnProductAdminPanel()) {
            return false;
        }

        $displayManager = new WooUserRoleDisplayPricesBackendManager($this);
        $displayManager->onAppendFieldsToSimpleOptionsAction();

        $this->removeAction(
            'woocommerce_product_options_pricing',
            'onAppendFieldsToSimpleOptionsAction'
        );
    } // end onAppendFieldsToSimpleOptionsAction
    
    protected function removeAction($hook, $methodName, $priority = 10)
    {        
        remove_action($hook, array($this, $methodName), $priority);
    } // end removeAction
    
    public function onAppendFieldsToVariableOptionsAction($loop, $data, $post)
    {
        $displayManager = new WooUserRoleDisplayPricesBackendManager($this);
        $displayManager->onAppendFieldsToVariableOptionsAction(
            $loop,
            $data,
            $post
        );
    } // end onAppendFieldsToVariableOptionsAction

    public function onInstall($refresh = false, $settings = false)
    {
        if (!$this->fileSystem) {
            $this->fileSystem = $this->getFileSystemInstance();
        }
        
        if ($this->_hasPermissionToCreateCacheFolder()) {
            $this->fileSystem->mkdir($this->pluginCachePath, 0777);
        }
        
        if (!$refresh) {
            $settings = $this->getOptions('settings');
        }

        if (!$refresh && !$settings) {
            $this->_doInitDefaultOptions('settings');
            $this->updateOptions('roles', array());
        }
        
        $this->_removeObsoleteOptions();

        $this->_doInitDisplayTaxOptions();
        
        $options = array();

        if (!$refresh && !$settings) {
            $options['skip_validation_system_plugin'] = true; 
        }

        if (!$this->isTestEnvironmentDefined()) {
            FestiCoreStandalone::install($options);
        }

        FestiTeamApiClient::addInstallStatistics(PRICE_BY_ROLE_PLUGIN_ID);
    } // end onInstall
    
    private function _removeObsoleteOptions()
    {
        $optionName = $this->optionsPrefix.'additionalSettings';
        
        return $this->engineFacade->deleteOption($optionName);
    } // end _removeObsoleteOptions
    
    private function _hasPermissionToCreateCacheFolder()
    {
        return $this->fileSystem->is_writable($this->pluginPath) &&
               !file_exists($this->pluginCachePath);
    } // end _hasPermissionToCreateFolder
    
    public function getTemplatePath($fileName)
    {
        return $this->pluginTemplatePath.'backend/'.$fileName;
    } // end getTemplatePath
    
    public function getPluginCssUrl($fileName, $customUrl = false)
    {
        if ($customUrl) {
            return $customUrl.$fileName;
        }

        return $this->pluginCssUrl.'backend/'.$fileName;
    } // end getPluginCssUrl

    public function getPluginJsUrl($fileName, $customUrl = false)
    {
        if ($customUrl) {
            return $customUrl.$fileName;
        }

        return $this->pluginJsUrl.'backend/'.$fileName;
    } // end getPluginJsUrl
    
    protected function hasOptionPageInRequest()
    {
        return array_key_exists('page', $_GET) &&
               array_key_exists($_GET['page'], $this->menuOptions);
    } // end hasOptionPageInRequest
    
    public function _onFileSystemInstanceAction()
    {
        $this->fileSystem = $this->getFileSystemInstance();
    } // end _onFileSystemInstanceAction
    
    public function onAdminMenuAction() 
    {
        $engineFacade = $this->engineFacade;

        $this->menuOptions = $engineFacade->dispatchFilter(
            EngineFacade::FILTER_BACKEND_MENU_OPTIONS,
            $this->menuOptions
        );

        $this->_addAdminMenuItems();
    } // end onAdminMenuAction
    
    public function onInitCssAction()
    {
        $this->onEnqueueCssFileAction(
            'festi-user-role-prices-styles',
            'style.css',
            array(),
            $this->version
        );
        
        $this->onEnqueueCssFileAction(
            'festi-admin-menu',
            'menu.css',
            array(),
            $this->version
        );
        
        $this->onEnqueueCssFileAction(
            'festi-checkout-steps-wizard-colorpicker',
            'colorpicker.css',
            array(),
            $this->version
        );
    } // end onInitCssAction
    
    public function onInitJsAction()
    {
        $this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-colorpicker',
            'colorpicker.js',
            'jquery',
            $this->version
        );
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-general',
            'general.js',
            'jquery',
            $this->version
        );
        $this->onEnqueueJsFileAction(
            'festi-user-role-prices-modal',
            'modal.js',
            'jquery',
            $this->version
        );
    } // end onInitJsAction
    
    private function _doAppendSubMenu($args = array())
    {
        $facade = EngineFacade::getInstance();

        $hookName = $facade->addSubmenuPage(
            $args['parent'],
            $args['title'], 
            $args['caption'], 
            $args['capability'], 
            $args['slug'], 
            $args['method']
        );

        $this->_initMenuListeners($hookName);
    } //end _doAppendSubMenu
    
    public function onDisplayOptionPage()
    {
        if ($this->_isRefreshPlugin()) {
            $this->onRefreshPlugin();
        }
        
        if ($this->_isRefreshCompleted()) {
            $message = __(
                'Success refresh plugin',
                $this->languageDomain
            );
            
            $this->displayUpdate($message);   
        }
        
        $this->_displayPluginErrors();
        
        $this->displayOptionsHeader();

        if ($this->menuOptions) {
            echo $this->fetch('menu.phtml');
        }
        
        $methodName = 'display';

        $postfix = $this->getDefaultMenuKey();
        
        if ($this->hasOptionPageInRequest()) {
            $postfix = $_GET['page'];
        }

        if (empty($this->menuOptions[$postfix])) {
            throw new Exception("Undefined tab name: ".$postfix);
        }
        
        $data = $this->menuOptions[$postfix];
        
        if (is_array($data) && !empty($data['callback'])) {
            $method = $data['callback'];
        } else {
            $methodName.= ucfirst($postfix);
            
            $method = array(&$this, $methodName);
        }

        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }

        $this->onPrepareScreen();

        $this->tab = new WooUserRolePricesBackendTab($this);

        call_user_func_array($method, array());
    } // end onDisplayOptionPage
    
    public function displayImportProductTab()
    {
        $plugin = $this->getFestiModuleInstance(
            ModulesSwitchListener::IMPORT_PRODUCTS_PLUGIN
        );

        $plugin->displayImportPage($this);
    } // end displayImportProductTab

    public function displayGeneralTab()
    {
        if ($this->_isDeleteRole()) {
            try {
                $this->deleteRole();
                           
                $this->displayOptionPageUpdateMessage(
                    'The role was successfully deleted'
                ); 
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->displayError($message);
            }
        }

        if ($this->isUpdateOptions('save')) {
            try {
                $this->tab->doUpdateOptions($_POST);

                WooCommerceCacheHelper::doRefreshPriceCache();
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->displayError($message);
            }
        }
        
        if ($this->isUpdateOptions('new_role')) {
            try {
                $this->doAppendNewRoleToWordpressRolesList();
    
                $this->displayOptionPageUpdateMessage(
                    'The role was successfully added'
                );   
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->displayError($message);
            }
        }

        $this->tab->display();
    } // end displayGeneralTab

    public function displayPriceAdjustmentsTab()
    {
        if ($this->isUpdateOptions('save')) {
            $this->tab->doUpdateOptions($_POST);
        }

        $this->tab->display();
    } // end displayPriceAdjustmentsTab

    /**
     * Displayed the modules tab content.
     */
    public function displayModules()
    {
        $plugin = $this->getCorePluginInstance();

        $store = $plugin->createStoreInstance('festi_plugins');

        $response = new Response();

        $store->onRequest($response);

        $response->send();
    } // end displayModules

    public function displayHidingRulesTab()
    {
        if ($this->isUpdateOptions('save')) {

            $this->tab->doUpdateOptions($_POST);

            if (!$this->_hasHideAllProductsOptionInRequest($_POST)) {
                $this->_doRemoveHideAllProductsIgnoreOptions();

                $this->updateOptions(
                    PRICE_BY_ROLE_CATEGORY_VISIBILITY_OPTIONS
                );
            }
        }

        $this->tab->display();
    } // end displayHidingRulesTab

    public function displayTaxesTab()
    {
        if ($this->isUpdateOptions('save')) {

            $this->tab->doUpdateOptions($_POST);

            $facade = EcommerceFactory::getInstance();

            if ($facade->isEnabledTaxCalculation()) {
                $this->doRestoreDefaultDisplayTaxValues();
            }
        }

        $this->tab->display();
    } // end displayTaxesTab

    public function onPrepareScreen()
    {
        $this->addFilterListener(
            'admin_footer_text',
            'onFilterDisplayFooter'
        );
    } // end onPrepareScreen
    
    public function displayOptionsHeader()
    { 
        $vars = array(
            'content' => __(
                'Prices by User Role Options',
                $this->languageDomain
            )
        );
        
        echo $this->fetch('options_header.phtml', $vars);
    } // end displayOptionsHeader
    
    public function deleteRole()
    {
        $roleKey = $_GET['delete_role'];

        if (!$this->_isRoleCreatedOfPlugin($roleKey)) {
            $message = __(
                'Unable to remove a role. Key does not exist.',
                $this->languageDomain
            );
            throw new Exception($message);
        }
        
        $this->doDeleteWordpressUserRole($roleKey);
    } // end deleteRole
    
    private function _isRoleCreatedOfPlugin($key)
    {
        $roles = $this->getUserRoles();
        $pluginRoles = $this->getCreatedRolesOptionsOfPlugin();
        
        return array_key_exists($key, $roles) &&
               array_key_exists($key, $pluginRoles);
    } // end _isRoleCreatedOfPlugin
    
    public function doDeleteWordpressUserRole($key)
    {
        remove_role($key);
    } // end doDeleteWordpressUserRole
    
    private function _isDeleteRole()
    {
        return array_key_exists('delete_role', $_GET) &&
               !empty($_GET['delete_role']);
    } // end _isDeleteRole
    
    public function doAppendNewRoleToWordpressRolesList()
    {
        if (!$this->_hasNewRoleInRequest()) {
            $message = __(
                'You have not entered the name of the role',
                $this->languageDomain
            );
            throw new Exception($message, PRICE_BY_ROLE_EXCEPTION_EMPTY_VALUE);
        }
        
        $key = $this->getKeyForNewRole();
        if (!$key) {
            $message = __(
                'An error has occurred, the Role Name contains unacceptable '.
                'characters. Please use the Role Identifier field to add the '.
                'user role.',
                $this->languageDomain
            );
            
            throw new Exception(
                $message, 
                PRICE_BY_ROLE_EXCEPTION_INVALID_VALUE
            );
        }
        
        $this->doAddWordpressUserRole($key, $_POST['roleName']);
        
        $this->updateCreatedRolesOptions($key);
        
        if ($this->_hasActiveOptionForNewRoleInRequest()) {
            $this->updateListOfEnabledRoles($key);
        } 
    } // end doAppendNewRoleToWordpressRolesList
    
    public function updateListOfEnabledRoles($key)
    {
        $settings = $this->getOptions('settings');
        
        $settings['roles'][$key] = true;
        
        $this->updateOptions('settings', $settings);
    } // end updateListOfEnabledRoles
    
    public function updateCreatedRolesOptions($newKey)
    {
        $roleOptions = $this->getCreatedRolesOptionsOfPlugin();

        if (!$roleOptions) {
            $roleOptions = array();
        }
        
        $roleOptions[$newKey] = $_POST['roleName'];

        $this->updateOptions('roles', $roleOptions);
    } // end updateCreatedRolesOptions
    
    public function getCreatedRolesOptionsOfPlugin()
    {
        return $this->getOptions('roles');
    } // end getCreatedRolesOptionsOfPlugin
    
    public function doAddWordpressUserRole($key, $name)
    {
        $capabilities = array(
            'read' => true
        );

        $facade = EngineFacade::getInstance();

        $result = $facade->addUserRole($key, $name, $capabilities);
        
        if (!$result) {
            $message = __(
                'Unsuccessful attempt to create a role',
                $this->languageDomain
            );
            throw new Exception($message);
        }
    } // end doAddWordpressUserRole
    
    public function getKeyForNewRole()
    {
        $roleKey = $_POST['roleName'];

        if (!empty($_POST['roleIdent'])) {
            $roleKey = $_POST['roleIdent'];
        }
        
        if (!preg_match("#^[a-zA-Z0-9_\s]+$#Umis", $roleKey)) {
            return false;
        }
        
        $roleKey = $this->_cleaningExtraCharacters($roleKey);

        $roleKey = $this->getAvailableKeyName($roleKey);
       
        return $roleKey;
    } // end getKeyForNewRole
    
    public function getAvailableKeyName($key)
    {
        $result = false;
        $sufix = '';
        $i = 0;
        
        $roles = $this->getUserRoles();

        while ($result === false) {
            $keyName = $key.$sufix;
            
            if (!$this->_hasKeyInExistingRoles($keyName, $roles)) {
                return $keyName;
            }

            $i++;
            $sufix = '_'.$i;
        }
    } // edn getAvailableKeyName
    
    private function _hasKeyInExistingRoles($keyName, $rols)
    {
        return array_key_exists($keyName, $rols);      
    } // end _hasKeyInExistingRoles
    
    private function _cleaningExtraCharacters($string)
    {
        $key = strtolower($string);
        $key = preg_replace('/[^a-z0-9\s]+/', '', $key);
        $key = trim($key);
        $key = preg_replace('/\s+/', '_', $key);
        
        return $key;
    } // end _cleaningExtraCharacters
    
    private function _hasNewRoleInRequest()
    {
        return array_key_exists('roleName', $_POST) &&
               !empty($_POST['roleName']);
    } // end _hasNewRoleInRequest
    
    private function _hasActiveOptionForNewRoleInRequest()
    {
        return array_key_exists('active', $_POST);
    } // end _hasActiveOptionForNewRoleInRequest
    
    public function displayOptionPageUpdateMessage($text)
    {
        $message = __(
            $text,
            $this->languageDomain
        );
            
        $this->displayUpdate($message);   
    } // end displayOptionPageUpdateMessage
    
    public function getOptionsFieldSet()
    {
        $fieldSet = array(
            PRICE_BY_ROLE_SETTINGS_PAGE_SLUG => array(),
        );
        
        $settings = $this->loadSettings();
        
        if ($settings) {
            foreach ($settings as $ident => &$item) {
                if (array_key_exists('fieldSetKey', $item)) {
                   $key = $item['fieldSetKey'];
                   $fieldSet[$key]['fields'][$ident] = $settings[$ident];
                }
            }
            unset($item);
        }
        
        return $fieldSet;
    } // end getOptionsFieldSet
    
    public function loadSettings()
    {
        $settings = new SettingsWooUserRolePrices();

        $options = $settings->get();

        $values = $this->getOptions('settings');

        if ($values) {
            foreach ($options as $ident => &$item) {
                if (array_key_exists($ident, $values)) {
                    $item['value'] = $values[$ident];
                }
            }
            unset($item);
        }
        
        return $options;
    } // end loadSettings
    
    private function _displayPluginErrors()
    {        
        $cacheFolderError = $this->_detectTheCacheFolderAccessErrors();

        if ($cacheFolderError) {
            echo $this->fetch('refresh.phtml');
        }
    } // end _displayPluginErrors
    
    private function _isRefreshPlugin()
    {
        return array_key_exists('refresh_plugin', $_GET);
    } // end _isRefreshPlugin
    
    public function onRefreshPlugin()
    {
        $this->onInstall(true);
    } // end onRefreshPlugin
    
    private function _doInitDefaultOptions($option, $instance = NULL)
    {
        $methodName = $this->getMethodName('load', $option);
        
        if (is_null($instance)) {
            $instance = $this;
        }

        $method = array($instance, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }

        $options = call_user_func_array($method, array());
        foreach ($options as $ident => &$item) {
            if ($this->_hasDefaultValueInItem($item)) {
                $values[$ident] = $item['default'];
            }
        }
        unset($item);
        
        $this->updateOptions($option, $values);
    } // end _doInitDefaultOptions
    
    private function _hasDefaultValueInItem($item)
    {
        return isset($item['default']);
    } //end _hasDefaultValueInItem
    
    public function getMethodName($prefix, $option)
    {
        $option = explode('_', $option);
        
        $option = array_map('ucfirst', $option);
        
        $option = implode('', $option);
        
        $methodName = $prefix.$option;
        
        return $methodName;
    } // end getMethodName
    
    private function _isRefreshCompleted()
    {
        return array_key_exists('refresh_completed', $_GET);
    } // end _isRefreshCompleted
    
    private function _detectTheCacheFolderAccessErrors()
    {
        if (!$this->fileSystem->is_writable($this->pluginCachePath)) {

            $message = __(
                "Caching does not work! ",
                $this->languageDomain
            );
            
            $message .= __(
                "You don't have permission to access: ",
                $this->languageDomain
            );
            
            $path = $this->pluginCachePath;
            
            if (!$this->fileSystem->exists($path)) {
                $path = $this->pluginPath;
            }
            
            $message .= $path;

            $this->displayError($message);
            
            return true;
        }
        
        return false;
    } // end _detectTheCacheFolderAccessErrors
    
    public function isUpdateOptions($action)
    {
        return array_key_exists('__action', $_POST) &&
               $_POST['__action'] == $action;
    } // end isUpdateOptions

    /**
     * @filter admin_footer_text
     */
    public function onFilterDisplayFooter()
    {
        return $this->fetch('footer.phtml');
    } // end onFilterDisplayFooter
    
    /**
     * @filter plugin_action_links_
     */
    public function onFilterPluginActionLinks($links)
    {
        $link = $this->fetch('settings_link.phtml');
        
        return array_merge($links, array($link));
    } // end onFilterPluginActionLinks
    
    public function displayRegistrationTab()
    {
        /*
         * $licensesPlugin = Controller::getInstance()
         * ->getPluginInstance('FestiTeamLicenses');
        $response = new Response();
        $licensesPlugin->onDisplayInformation($response);
        $response->send();
        die("---");
        */
        
        try {
            $envatoInstance = $this->_getEnvatoUtilInstance();

            $apiUrl = $envatoInstance->getApiUrl();

            $licenseInfo = $envatoInstance->doValidateLicense($_REQUEST);

            $urlPage = $envatoInstance->getPrepareUrl();
            
            $facade = $this->engineFacade;
            
            $pluginData = $facade->getPluginData($this->pluginMainFile);
    
            $statusFestiAPI = $envatoInstance->getStatusFestiApiService();
            
            $vars = array(
                'pluginData'  => $pluginData,
                'licenseInfo' => $licenseInfo,
                'apiUrl'      => $apiUrl,
                'urlPage'     => $urlPage,
                'statusAPI'   => $statusFestiAPI,
                'newVersionLink' => false
            );
            
            $currentVersion = $envatoInstance->getVersion();
            
            if ($currentVersion && $currentVersion != $pluginData['Version']) {
                $vars['newVersionLink'] = $envatoInstance->getDownloadLink(
                    $licenseInfo['purchase_code']
                );
                
                $vars['newVersion'] = $currentVersion;
            }
            
        } catch (ConnectionLibraryNotFound $exp) {
            $vars = array(
                'statusAPI'  =>  array(
                    'status'  => 'error',
                    'message' => $exp->getMessage(),
                    'code'    => $exp->getCode()
                )
            );
        }
        
        echo $this->fetch('registration.phtml', $vars);
    } // end displayRegistrationTab
    
    private function _getEnvatoUtilInstance()
    {
        $url = 'admin.php?page=registrationTab';

        $message = __(
            'HI! Would you like unlock premium support? '.
            'Please activate your copy of '
        );

        $vars = array(
            'message' => 'Prices by User Role',
            'url' => $this->engineFacade->getAdminUrl().$url,
        );

        $message .= $this->fetch('message_url.phtml', $vars);

        $options = array(
            'id_plugin'   => PRICE_BY_ROLE_PLUGIN_ID,
            'message'     => $message,
            'slug_plugin' => PRICE_BY_ROLE_SETTINGS_PAGE_SLUG
        );

        return new EnvatoUtil($this, $options);
    } // end _getEnvatoUtilInstance

    protected function hasRolePrice($role, $prices)
    {
        return array_key_exists($role, $prices) &&
               $prices[$role] > 0;
    } // end hasRolePrice

    protected function hasRoleSalePrice($role, $prices)
    {
        return array_key_exists('salePrice', $prices) &&
               array_key_exists($role, $prices['salePrice']) &&
               $prices['salePrice'][$role] > 0;
    } // end hasRoleSalePrice

    private function _hasUserIDInRequest()
    {
        return array_key_exists('idUser', $_POST);
    } // end _hasUserIDInRequest
    
    public function onInitPriceFiltersAction()
    {  
        $this->products = $this->getProductsInstances();

        if ($this->hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            $this->onFilterPriceByDiscountOrMarkup();   
        } else {
            $this->onFilterPriceByRolePrice();
        }
    } // end onInitPriceFiltersAction

    private function _isIgnoreHiddenAllProductsOptionOn()
    {
        $facade = EngineFacade::getInstance();

        $idPost = $facade->getCurrentPostID();

        $options = $this->getMetaOptions(
            $idPost,
            PRICE_BY_ROLE_IGNORE_HIDE_ALL_PRODUCT_OPTION_META_KEY
        );

        return (bool)$options;
    } // end _isIgnoreHiddenAllProductsOptionOn

    private function _hasHideAllProductsOptionInRequest()
    {
        return array_key_exists('hideAllProducts', $_POST) &&
               $_POST['hideAllProducts'];
    } // end _hasHideAllProductsOptionInRequest
    
    private function _doRemoveHideAllProductsIgnoreOptions()
    {
        $facade = $this->ecommerceFacade;

        $facade->doRemoveMetaOptionByKeyInProducts(
            PRICE_BY_ROLE_IGNORE_HIDE_ALL_PRODUCT_OPTION_META_KEY
        );
    } // end _doRemoveHideAllProductsIgnoreOptions

    private function _getRolesForProductAccess($request)
    {
        if (!is_array($request)) {
            return array();
        }

        $userRoles = $this->getUserRoleNames();

        $rolesByRequest = array_keys($request);

        $userRoles = array_diff($userRoles, $rolesByRequest);

        foreach ($userRoles as $key => $role) {
            $userRoles[$key] = base64_encode($role);
        }

        return array_values($userRoles);
    } // end _getRolesForProductAccess

    private function _getPreparedSettingsForNewUserRoles(
        $hideProductForUserRoles
    )
    {
        $facade = EngineFacade::getInstance();

        $idPost = $facade->getCurrentPostID();

        $showProductForUserRoles = $this->getMetaOptions(
            $idPost,
            PRICE_BY_ROLE_IGNORE_HIDE_ALL_PRODUCT_OPTION_META_KEY
        );

        foreach ($showProductForUserRoles as $key => $role) {
            $showProductForUserRoles[$key] = base64_decode($role);
        }

        $userRoles = $this->getUserRoleNames();

        $rolesFromMetaOptions = array_merge(
            $showProductForUserRoles,
            array_keys($hideProductForUserRoles)
        );

        $newUserRoles = array_diff($userRoles, $rolesFromMetaOptions);

        if (!$newUserRoles) {
            return $hideProductForUserRoles;
        };

        $newSettings = array();

        foreach ($newUserRoles as $role) {
            $newSettings[$role] = true;
        }

        $hideProductForUserRoles = array_merge(
            $newSettings,
            $hideProductForUserRoles
        );

        return $hideProductForUserRoles;
    } // end _getPreparedSettingsForNewUserRoles

    public function onInitUserRole()
    {
        static::$userRole = $this->getUserRole();
    } // end onInitUserRole

    public function onUserRoleUpdateDisplayTax()
    {
        $ecommerceFacade = $this->ecommerceFacade;

        list($shopHookName, $cartHookName) =
            $ecommerceFacade->getDisplayTaxHookNames();

        $displayTaxOptions = array();

        if ($this->_hasDisplayTaxOptionInRequest($shopHookName)) {
            $displayTaxOptions[$shopHookName] = $_POST[$shopHookName];
        }

        if ($this->_hasDisplayTaxOptionInRequest($cartHookName)) {
            $displayTaxOptions[$cartHookName] = $_POST[$cartHookName];
        }

        if ($displayTaxOptions) {
            $engineFacade = EngineFacade::getInstance();

            $engineFacade->updateOption(
                PRICE_BY_ROLE_TAX_DISPLAY_OPTIONS,
                $displayTaxOptions
            );
        }
    } //end onUserRoleUpdateDisplayTax

    private function _hasDisplayTaxOptionInRequest($optionName)
    {
        return array_key_exists($optionName, $_POST);
    } //end _hasDisplayTaxOptionInRequest

    private function _doInitDisplayTaxOptions()
    {
        if ($this->isUserRoleDisplayTaxOptionExist()) {
            return false;
        }

        $ecommerceFacade = $this->ecommerceFacade;

        list($shopHookName, $cartHookName)
            = $ecommerceFacade->getDisplayTaxHookNames();

        $facade = EngineFacade::getInstance();

        $displayTaxOptions[$shopHookName] = $facade->getOption(
            $shopHookName
        );

        $displayTaxOptions[$cartHookName] = $facade->getOption(
            $shopHookName
        );

        $facade->updateOption(
            PRICE_BY_ROLE_TAX_DISPLAY_OPTIONS,
            $displayTaxOptions
        );
    } // end _doInitDisplayTaxOptions

    public function onUninstall()
    {
        $facade = EngineFacade::getInstance();

        $this->doRestoreDefaultDisplayTaxValues();

        return $facade->deleteOption(PRICE_BY_ROLE_TAX_DISPLAY_OPTIONS);
    } // end onUninstall

    public function onInitExportManager()
    {
        $plugin = $this->getFestiModuleInstance(
            ModulesSwitchListener::PRODUCTS_EXPORT_PLUGIN
        );

        $plugin->onInitDefaultExportNames();
    } // end onInitExportManager

    private function _setProductsAndCategoriesVisibilityByID($idPost, $data)
    {
        $rolesByRequest = $data[PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY];

        $userRoles = $this->_getRolesForProductAccess($rolesByRequest);

        $this->updateMetaOptions(
            $idPost,
            $userRoles,
            PRICE_BY_ROLE_IGNORE_HIDE_ALL_PRODUCT_OPTION_META_KEY
        );

        $engineFacade = EngineFacade::getInstance();

        $ecommerceFacade = EcommerceFactory::getInstance();

        $className = get_class($ecommerceFacade);

        $productCategoryIDs = $engineFacade->getPostTermsByPostID(
            $idPost,
            $className::PRODUCT_CATEGORY_KEY,
            array('fields' => 'ids')
        );

        $categoriesByRoles = array();

        foreach ($userRoles as $role) {
            $role = base64_decode($role);
            $categoriesByRoles[$role] = $productCategoryIDs;
        }

        $categories = $this->getOptions(
            PRICE_BY_ROLE_CATEGORY_VISIBILITY_OPTIONS
        );

        if ($categories) {
            $categories = $this->_doPrepareHidePostsOptions(
                $categories,
                $categoriesByRoles
            );
        } else {
            $categories = $categoriesByRoles;
        }

        $this->updateOptions(
            PRICE_BY_ROLE_CATEGORY_VISIBILITY_OPTIONS,
            $categories
        );
    } // end _setProductsAndCategoriesVisibilityByID

    private function _hasAutomaticCurrencyCalculationInRequest()
    {
        if (!$this->isWmplCurrenciesPluginActive()) {
            return false;
        }

        $wpml = 'WpmlCurrencyCompatibilityManager';

        $key = $wpml::CUSTOM_PRICE_CALCULATION_KEY;
        $value = $wpml::AUTO_CALCULATION_OPTION_VALUE;

        return $this->_hasRolePriceProductOptionsInRequest() &&
               array_key_exists($key, $_POST) &&
               $_POST[$key] &&
               is_array($_POST[$key]) &&
               current($_POST[$key]) == $value;
    } // end _hasAutomaticCurrencyCalculationInRequest

    protected function getDefaultMenuKey()
    {
        reset($this->menuOptions);

        return key($this->menuOptions);
    } // end getDefaultMenuKey

    private function _addAdminMenuItems()
    {
        $pageTitle = __(PRICE_BY_ROLE_PLUGIN_NAME, $this->languageDomain);
        $menuTitle = $pageTitle;
        $menuSlug = PRICE_BY_ROLE_SETTINGS_PAGE_SLUG;
        $iconClass = 'dashicons-buddicons-buddypress-logo';
        $position = 55.5;

        $facade = EngineFacade::getInstance();

        $hookName = $facade->addAdminMenuPage(
            $pageTitle,
            $menuTitle,
            static::MENU_OPTIONS_CAPABILITY,
            $menuSlug,
            array($this, 'onDisplayOptionPage'),
            $iconClass,
            $position
        );

        $this->_initMenuListeners($hookName);

        $menuOptions = $this->menuOptions;
        $firstOption = reset($menuOptions);

        foreach ($menuOptions as $key => $title) {

            $slug = $key;

            if ($firstOption == $title) {
                $slug = $menuSlug;
            }

            $title = __($title, $this->languageDomain);

            $args = array(
                'parent' => PRICE_BY_ROLE_SETTINGS_PAGE_SLUG,
                'title' => $title,
                'caption' => $title,
                'capability' => static::MENU_OPTIONS_CAPABILITY,
                'slug' => $slug,
                'method' => array($this, 'onDisplayOptionPage')
            );

            $this->_doAppendSubMenu($args);
        }
    } // end _addAdminMenuItems

    private function _initMenuListeners($hookName)
    {
        $this->addActionListener(
            'admin_print_styles-'.$hookName,
            'onInitCssAction'
        );

        $this->addActionListener(
            'admin_print_scripts-'.$hookName,
            'onInitJsAction'
        );

        $this->addActionListener(
            'admin_head-'.$hookName,
            '_onFileSystemInstanceAction'
        );
    } // end _initMenuListeners

    public function displayExtensionsTab()
    {
        echo $this->fetch('load_extension.phtml');
    } // end displayExtensionsTab

    public function onModifyPackageInstallerOptions($options)
    {
        $extension = $this->_getExtensionName($options['package']);

        if ($extension) {
            $options['destination'] = PRICE_BY_ROLE_PLUGIN_DIR.
                DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.$extension;
        }

        return $options;
    } // end onModifyPackageInstallerOptions

    private function _getExtensionName($packagePath)
    {
        $extensions = array(
            ModulesSwitchListener::TAXES_PLUGIN,
            ModulesSwitchListener::IMPORT_PRODUCTS_PLUGIN,
            ModulesSwitchListener::PRODUCTS_EXPORT_PLUGIN,
            ModulesSwitchListener::QUANTITY_DISCOUNT_PLUGIN,
        );

        foreach ($extensions as $extension) {
            $position = strpos($packagePath, $extension);

            if (is_int($position)) {
                return $extension;
            }
        }

        return false;
    } // end _getExtensionName

    public function onUpgradeProcessComplete($upgrade, $hook)
    {
        $result = $upgrade->result;

        if ($result && $this->_getExtensionName($result['source'])) {
            $this->redirectPage = 'generalTab';
            $this->onInstall();
            echo $this->fetch('redirect_after_load.phtml');
        }
    } // end onUpgradeProcessComplete

    public function displayQuantityDiscountTab()
    {
        if ($this->isUpdateOptions('save')) {
            $this->tab->doUpdateOptions($_POST);
        }

        $this->tab->display();
    } // end displayQuantityDiscountTab

    private function isAllowToDisplayPluginOptionsOnProductAdminPanel()
    {
        $idProduct = $this->engineFacade->getCurrentPostID();

        $product = $this->createProductInstance($idProduct);

        $this->onInitFestiProducts();

        return $this->isSupportedProductType($product);
    } // end isAllowToDisplayPluginOptionsOnProductAdminPanel
}