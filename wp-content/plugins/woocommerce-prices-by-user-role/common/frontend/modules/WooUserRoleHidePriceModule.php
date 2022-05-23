<?php

class WooUserRoleHidePriceModule extends AbstractWooUserRoleModule
{
    public function onHidePrice()
    {
        if (!$this->_hasAvailableRoleToViewPricesInAllProducts()) {
            $this->frontend->products->replaceAllPriceToText();
            $this->frontend->removeFilter(
                'woocommerce_get_price_html',
                array(
                    $this->frontend,
                    'onDisplayCustomerSavingsFilter',
                )
            );
            
            $this->_doHideSubscriptionProductPrice();   
            
        } else {
            $this->frontend->products->replaceAllPriceToTextInSomeProduct();
        }
    } // end onHidePrice
    
    private function _doHideSubscriptionProductPrice()
    {
        $this->frontend->addFilterListener(
            'woocommerce_subscriptions_product_price_string',
            array(
                $this->frontend,
                'onReplaceAllPriceToTextInAllProductFilter',
            ),
            10,
            3
        );
        $this->frontend->addFilterListener(
            'woocommerce_variable_subscription_price_html',
            array(
                $this->frontend,
                'onReplaceAllPriceToTextInAllProductFilter',
            ),
            10,
            2
        );
        
        $this->frontend->addFilterListener(
            'woocommerce_order_formatted_line_subtotal',
            array(
                $this->frontend,
                'onReplaceAllPriceToTextInAllProductFilter',
            ),
            10,
            2
        );
        
        $this->frontend->addFilterListener(
            'woocommerce_order_subtotal_to_display',
            array(
                $this->frontend,
                'onReplaceAllPriceToTextInAllProductFilter',
            ),
            10,
            2
        );
        
        $this->frontend->addFilterListener(
            'woocommerce_get_formatted_order_total',
            array(
                $this->frontend,
                'onReplaceAllPriceToTextInAllProductFilter',
            ),
            10,
            2
        );
    } // end _doHideSubscriptionProductPrice
    
    public function onProductPriceOnlyRegisteredUsers($price)
    {
       if (!$this->_hasAvailableRoleToViewPricesInAllProducts()) {
           $price = $this->frontend->getTextInsteadPrices();
       }
       
       return $price;
    } // end onProductPriceOnlyRegisteredUsers
    
    private function _hasAvailableRoleToViewPricesInAllProducts()
    {
        if (!$this->_isAvailablePriceInAllProductsForUnregisteredUsers()) {
            $this->_setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }

        if (!$this->_isAvailablePriceInAllProductsForRegisteredUsers()) {
            $this->_setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
        }

        return true;
    } // end _hasAvailableRoleToViewPricesInAllProducts
    
    public function onRemovePriceForUnregisteredUsers($price, $product)
    {
        if ($this->_hasReadMoreButtonText($price)) {
            return $price;
        }

        if (!$this->_isAvailablePriceInAllProductsForUnregisteredUsers()) {
             $price = null;
        }

        return $price;
    } // end onRemovePriceForUnregisteredUsers
    
    private function _hasReadMoreButtonText($price)
    {
        return $price === $this->ecommerceFacade->getEmptyPriceSymbol();
    } // end _hasReadMoreButtonText
    
    private function _isAvailablePriceInAllProductsForUnregisteredUsers()
    {
        return $this->frontend->isRegisteredUser() ||
               (!$this->frontend->isRegisteredUser() && 
               !$this->_hasOnlyRegisteredUsersInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForUnregisteredUsers
    
    private function _hasOnlyRegisteredUsersInGeneralSettings()
    {
        $settings = $this->frontend->getSettings();

        return array_key_exists('onlyRegisteredUsers', $settings);
    } // end _hasOnlyRegisteredUsersInGeneralSettings
    
    private function _isAvailablePriceInAllProductsForRegisteredUsers()
    {
        return !$this->frontend->isRegisteredUser() || 
               ($this->frontend->isRegisteredUser() &&
               !$this->_hasHidePriceOptionForRoleInGeneralSettings());
    } //end _isAvailablePriceInAllProductsForRegisteredUsers
    
    private function _hasHidePriceOptionForRoleInGeneralSettings()
    {
        $settings = $this->frontend->getSettings();

        return array_key_exists('hidePriceForUserRoles', $settings) &&
               array_key_exists($this->userRole, $settings['hidePriceForUserRoles']);
    } // end _hasHidePriceOptionForRoleInGeneralSettings
    
    public function onReplaceAllPriceToTextInSomeProductFilter($price, $product)
    {
        $product = $this->frontend->getProductNewInstance($product);

        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            return $this->frontend->fetchContentInsteadOfPrices();
        }

        if ($this->_isGuestUserTextEnabled()) {
            $guestText = $this->_getGuestUserCustomText();

            $vars = array(
                'text' => $guestText
            );

            $template = $this->frontend->fetch('guest_text.phtml', $vars);
            $price .= $template;
        };
        
        return $price;
    } // end onReplaceAllPriceToTextInSomeProductFilter
    
    public function onHideAddToCartButton()
    {
        if ($this->_isEnabledHideAddToCartButtonOptionInAllProducts()) {
            $this->_removeAllAddToCartButtons();
        } else {
            $this->_removeAddToCartButtonsInSomeProduct();
        }
    } // end onHideAddToCartButton
    
    private function _isEnabledHideAddToCartButtonOptionInAllProducts()
    {
        return (!$this->frontend->isRegisteredUser() &&
               $this->_hasHideAddToCartButtonOptionInSettings()) ||
               ($this->frontend->isRegisteredUser() &&
               $this->_hasHideAddToCartButtonOptionForUserRole());
    } // end _isEnabledHideAddToCartButtonOptionInAllProducts
    
    private function _hasHideAddToCartButtonOptionInSettings()
    {
        $settings = $this->frontend->getSettings();
            
        return array_key_exists('hideAddToCartButton', $settings);
    } //end _hasHideAddToCartButtonOptionInSettings
    
    private function _hasHideAddToCartButtonOptionForUserRole()
    {
        $key = 'hideAddToCartButtonForUserRoles';
        $settings = $this->frontend->getSettings();

        return array_key_exists($key, $settings) &&
               array_key_exists($this->userRole, $settings[$key]);
    } //end _hasHideAddToCartButtonOptionForUserRole
    
    private function _removeAddToCartButtonsInSomeProduct()
    {
        $this->frontend->products->removeLoopAddToCartLinksInSomeProducts();
        $this->_removeAddToCartButtonInProductPage();
    } // end _removeAddToCartButtonsInSomeProduct
    
    private function _removeAddToCartButtonInProductPage()
    {
        if (!$this->frontend->isProductPage()) {
            return false;
        }

        $facade = EngineFacade::getInstance();

        $idPost = $facade->getCurrentPostID();

        $product = $this->frontend->createProductInstance($idPost);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            $type = $this->ecommerceFacade->getProductType($product);
            $this->frontend->products->removeAddToCartButton($type);
        }
    } // end _removeAddToCartButtonInProductPage
    
    public function onRemoveAddToCartButtonInSomeProductsFilter(
        $button, $product
    )
    {
        $product = $this->frontend->getProductNewInstance($product);
        
        if (!$this->_hasAvailableRoleToViewPricesInProduct($product)) {
            return '';
        }

        return $button;
    } // end onRemoveAddToCartButtonInSomeProductsFilter
    
    private function _hasAvailableRoleToViewPricesInProduct($product)
    {
        $parentProduct = false;

        if ($this->_isChildProduct($product)) {
            $parentID = $this->ecommerceFacade->getProductParentID($product);
            $parentProduct = $this->frontend->createProductInstance($parentID);
        }

        if ($parentProduct) {
            $product = $parentProduct;
        }

        if (!$this->_isAvailablePriceInProductForUnregisteredUsers($product)) {
            $this->_setValueForContentInsteadOfPrices('textForUnregisterUsers');
            return false;
        }
    
        if (!$this->_isAvailablePriceInProductForRegisteredUsers($product)) {
            $this->_setValueForContentInsteadOfPrices('textForRegisterUsers');
            return false;
        }
        
        return true;
    } // end _hasAvailableRoleToViewPricesInProduct
    
    private function _isChildProduct($product)
    {
        return $this->ecommerceFacade->isChildProduct($product);
    } // end _isChildProduct
    
    private function _isAvailablePriceInProductForUnregisteredUsers($product)
    {
        return $this->frontend->isRegisteredUser() || 
               (!$this->frontend->isRegisteredUser() &&
               !$this->_hasOnlyRegisteredUsersInProductSettings($product));
    } // end _isAvailablePriceInProductForUnregisteredUsers
    
    private function _hasOnlyRegisteredUsersInProductSettings($product)
    {
        $idProduct = $this->ecommerceFacade->getProductID($product);
        
        if (!$idProduct) {
            return false;
        }

        $options = $this->frontend->getMetaOptions(
            $idProduct,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
       
        if (!$options) {
            return false;
        }

        return array_key_exists('onlyRegisteredUsers', $options);
    } // end _hasOnlyRegisteredUsersInProductSettings
    
    private function _isAvailablePriceInProductForRegisteredUsers($product)
    {
        return !$this->frontend->isRegisteredUser() || 
               ($this->frontend->isRegisteredUser() &&
               !$this->_hasHidePriceOptionForRoleInProductSettings($product));
    } // end _isAvailablePriceInProductForRegisteredUsers
    
    private function _hasHidePriceOptionForRoleInProductSettings($product)
    { 
        $idProduct = $this->ecommerceFacade->getProductID($product);
        
        if (!$idProduct) {
            return false;
        }
        
        $options = $this->frontend->getMetaOptions(
            $idProduct,
            PRICE_BY_ROLE_HIDDEN_RICE_META_KEY
        );
        
        if (!$options) {
            return false;
        }
        
        if (!array_key_exists('hidePriceForUserRoles', $options)) {
            return false;
        }
        
        return $options && array_key_exists(
            $this->userRole,
            $options['hidePriceForUserRoles']
        );
    } // end _hasHidePriceOptionForRoleInProductSettings

    private function _setValueForContentInsteadOfPrices($optionName)
    {
        $settings = $this->frontend->getSettings();

        $this->frontend->setTextInsteadPrices($settings[$optionName]);
    } // end _setValueForContentInsteadOfPrices
    
    private function _removeAllAddToCartButtons()
    {
        $this->frontend->products->removeAllLoopAddToCartLinks();
        $this->frontend->products->removeAddToCartButton();
    } //end _removeAllAddToCartButtons

    private function _getGuestUserCustomText()
    {
        $settings = $this->frontend->getSettings();

        if (array_key_exists('guestUserTextArea', $settings)) {
            return $settings['guestUserTextArea'];
        }

        return false;
    } // end _getGuestUserCustomText

    private function _isGuestUserTextEnabled()
    {
        $settings = $this->frontend->getSettings();

        return !$this->frontend->isRegisteredUser() &&
               $this->frontend->isProductPage() &&
               array_key_exists('guestUserStatus', $settings);
    } // end _isGuestUserTextEnabled
}