<?php
class WooUserRoleDisplayPricesBackendManager
extends WooUserRolePricesBackendFestiPlugin
{
    private $_engine;
    
    public function __construct($engine)
    {
        $this->_engine = $engine;
    } // end __construct
    
    public function onAppendFieldsToSimpleOptionsAction()
    {
        $this->_displayIgnoreDiscountCheckbox();
        
        $roles = $this->getActiveRoles();
        
        if (!$roles) {
            return false;
        }
        
        $productPrices = $this->getProductPrices(false);
        
        $this->_doAppendPriceFieldsInSimpleProduct($productPrices, $roles); 
    } // end onAppendFieldsToSimpleOptionsAction

    private function _doAppendPriceFieldsInSimpleProduct($productPrices, $roles)
    {
        foreach ($roles as $roleKey => $role) {
            
            if ($this->_isRolePriceEmpty($productPrices, $roleKey)) {
                $productPrices[$roleKey] = 0;
            }
            
            $params = array(
                'roleKey'          => $roleKey,
                'role'             => $role,
                'productPrices'    => $productPrices
            );
            
            $this->_displayRolePrice($params);
            
            $argsSale = $this->_getArgsForSalePrice($params);
            
            $dateFromToSchedule = $this->_getDateFromToSchedule(
                $productPrices,
                $roleKey
            );
            
            $this->_displaySalePriceByUserRole(
                $argsSale,
                $roleKey,
                $dateFromToSchedule
            );
        }
    } // end _doAppendPriceFieldsInSimpleProduct
    
    private function _getDateFromToSchedule($productPrices, $roleKey)
    {
        $dateFromToSchedule = array();
            
        if ($this->_hasScheduleForUserSalePrice($productPrices, $roleKey)) {
            $dateFromToSchedule = $productPrices['schedule'][$roleKey];    
        }
        
        return $dateFromToSchedule;
    } // end _getDateFromToSchedule
    
    private function _getArgsForSalePrice($vars)
    {
        if ($vars) {
            extract($vars);
        }
        $salePrice = '';
        
        if ($this->_hasSalePriceForUserRole($productPrices, $roleKey)) {
            $salePrice = $productPrices['salePrice'][$roleKey];
            $salePrice = $this->_getPriceAfterFormat($salePrice);
        }
        
        $isDisabledSalePrice = $this->isDiscountOrMarkupEnabledByRole($roleKey);
        $disableSelector = $isDisabledSalePrice ? 'festi-field-disabled' : '';

        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        
        $args = array(
            'name' => PRICE_BY_ROLE_PRICE_META_KEY.'[salePrice]['.$roleKey.']',
            'class' => 'short wc_input_price festi-role-input '.
                       'festi-role-sale-price '.$disableSelector ,
            'id' => PRICE_BY_ROLE_PRICE_META_KEY.'_'.$roleKey. $salePriceKey,
            'label' => $this->_getLabelForPrice($role['name'], 'Sale Price'),
            'value' => $salePrice
        );
        
        return $args;
    } // end _getArgsForSalePrice

    private function _displayRolePrice($vars)
    {
        if ($vars) {
            extract($vars);
        }
        
        $price = $productPrices[$roleKey];
        
        $price = $this->_getPriceAfterFormat($price);
        
        $args = array(
            'name'  => PRICE_BY_ROLE_PRICE_META_KEY.'['.$roleKey.']',
            'class' => 'short wc_input_price festi-role-input',
            'label' => $this->_getLabelForPrice($role['name'], 'Price'),
            'id'    => PRICE_BY_ROLE_PRICE_META_KEY.'_'.$roleKey,
            'value' => $price,
            'custom_attributes' => array(
                'data-role' => $roleKey,
            )
        );
        
        woocommerce_wp_text_input($args);
    } // end _displayRolePrice
    
    private function _displaySalePriceByUserRole(
        $args,
        $roleKey,
        $datesScheduleSalePrice
    )
    {        
        $saleRolePriceDatesFrom = '';
        if (array_key_exists('date_from', $datesScheduleSalePrice)) {
            $saleRolePriceDatesFrom = $datesScheduleSalePrice['date_from'];
        }
        $saleRolePriceDatesTo = '';
        if (array_key_exists('date_to', $datesScheduleSalePrice)) {
            $saleRolePriceDatesTo = $datesScheduleSalePrice['date_to'];
        }

        $vars = array(
            'args'                   => $args,
            'name'                   => $roleKey,
            'saleRolePriceDatesFrom' => $saleRolePriceDatesFrom,
            'saleRolePriceDatesTo'   => $saleRolePriceDatesTo
        );
        
        echo $this->_engine->fetch('sale_price_by_role.phtml', $vars);  
    } // end _displaySalePriceByUserRole
    
    public function onAppendFieldsToVariableOptionsAction($loop, $data, $post)
    {   
        $this->_displayIgnoreDiscountCheckbox($post->ID);
        
        $roles = $this->getActiveRoles();
        
        if (!$roles) {
            return false;
        }
        
        $productPrices = $this->getProductPrices($post->ID);
        
        $this->_displayPriceFields($productPrices, $roles, $loop);
    } // end onAppendFieldsToVariableOptionsAction
    
    private function _displayPriceFields($prices, $roles, $loop)
    {
        foreach ($roles as $keyRole => $role) {
            
            $label = $this->_getLabelForPrice($role['name'], 'Price');
            
            $isDiscountEnabled = $this->isDiscountOrMarkupEnabledByRole(
                $keyRole
            );
            
            $rolePrice = $this->_getRolePrice($keyRole, $prices);
           
            $roleSalePrice = $this->_getRoleSalePrice($keyRole, $prices);
            
            $datesRole = $this->_getDatesRoleSalePrice($prices, $keyRole);
            
            $vars = array(
                'loop'              => $loop,
                'label'             => $label,
                'keyRole'           => $keyRole,
                'isDiscountEnabled' => $isDiscountEnabled,
                'rolePrice'         => $rolePrice,
                'roleSalePrice'     => $roleSalePrice,
                'datesRole'         => $datesRole,
                'role'              => $role
            );   
            
            echo $this->_engine->fetch('variable_field.phtml', $vars);
        }
    } // end _displayPriceFields
    
    private function _getRolePrice($keyRole, $productPrices)
    {
        $price = '';
            
        if (array_key_exists($keyRole, $productPrices)) {
            $price = $this->_getPriceAfterFormat($productPrices[$keyRole]);
        }
        
        return $price;
    } // end _getRolePrice
    
    private function _getRoleSalePrice($keyRole, $productPrices)
    {
        $salePrice = '';
    
        if ($this->_hasSalePriceForUserRole($productPrices, $keyRole)) {
            $salePrice = $productPrices['salePrice'][$keyRole];  
            $salePrice = $this->_getPriceAfterFormat($salePrice);
        }
        
        return $salePrice;
    } // end _getRoleSalePrice
    
    private function _getDatesRoleSalePrice($productPrices, $key)
    {
        $dates = array(
            'from' => '',
            'to'   => '' 
        );    
        if ($this->_hasScheduleForUserSalePrice($productPrices, $key)) {
            $dateSchedule = $productPrices['schedule'][$key];
        
            if (array_key_exists('date_from', $dateSchedule)) {
                $dates['from'] = $dateSchedule['date_from'];
            }
               
            if (array_key_exists('date_to', $dateSchedule)) {
                $dates['to'] = $dateSchedule['date_to'];
            }
        }
        
        return $dates;
    } // end _getDatesRoleSalePrice

    private function _getLabelForPrice($roleName, $priceName)
    {
        $symbol = $this->_engine->ecommerceFacade->getCurrencySymbol();

        $label = $roleName.' ';
        $label .= __($priceName, $this->languageDomain);
        $label .= ' ('.$symbol.')';
        
        return $label;
    } // end _getLabelForPrice

    private function _getPriceAfterFormat($price)
    {
        $search = array('.', ',');
        $separator = $this->_getWooCommerceDecimalSeparator();
        $priceFormat = str_replace($search, $separator, $price);
        
        return $priceFormat;
    } // end _getPriceAfterFormat

    private function _isRolePriceEmpty($productPrices, $roleKey)
    {
        return !array_key_exists($roleKey, $productPrices) ||
               $productPrices[$roleKey] == '';
    } // end _isRolePriceEmpty
    
    private function _hasScheduleForUserSalePrice($productPrices, $roleKey)
    {
        return array_key_exists('schedule', $productPrices) &&
               array_key_exists($roleKey, $productPrices['schedule']);
    } // end _hasScheduleForUserSalePrice
    
    private function _hasSalePriceForUserRole($productPrices, $roleKey)
    {
        return array_key_exists('salePrice', $productPrices) &&
               array_key_exists($roleKey, $productPrices['salePrice']);
    } // end _hasSalePriceForUserRole
    
    private function _getWooCommerceDecimalSeparator()
    {
        $facade = EngineFacade::getInstance();

        $decimalSeparator = $facade->getOption('woocommerce_price_decimal_sep');
        
        return stripslashes($decimalSeparator);
    } // end _getWooCommerceDecimalSeparator
    
    private function _displayIgnoreDiscountCheckbox($idPost = false)
    {
        if (!$idPost) {
            $post = $this->getWordpressPostInstance();
            $idPost = $post->ID;
        }
        
        $currentValue = (int) $this->isIgnoreDiscountForProduct(
            $idPost
        );
        
        $name = PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY.'['.$idPost.']';
        
        $args = array(
            'id'      => PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY,
            'name'    => $name,
            'class'   => 'checkbox festi-role-checkbox',
            'label'   => $this->getLang('Disable Discount'),
            'value'   => $currentValue,
            'cbvalue' => 1,
            'description' => $this->getLang(
                'Ignore Price By User Role discount'
            )
        );
        
        woocommerce_wp_checkbox($args);
    } // end _displayIgnoreDiscountCheckbox
}