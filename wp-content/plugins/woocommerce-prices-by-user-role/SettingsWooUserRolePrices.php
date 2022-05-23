<?php
class SettingsWooUserRolePrices
{
    const GENERAL_SETTINGS_CSS_CLASS = 'general-settings';
    const HIDING_SETTINGS_CSS_CLASS = 'hiding-settings';
    const TAX_SETTINGS_CSS_CLASS = 'tax-settings';

    const QUANTITY_DISCOUNT_OPTION_KEY = 'quantityDiscount';

    public function get()
    {
        $settings = array(
            'roles' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'User Roles for Special Pricing'
                ),
                'type' => 'multicheck',
                'default' => array(),
                'fieldSetKey' => 'generalTab',
                'classes' => 'festi-user-role-prices-top-border '.
                    static::GENERAL_SETTINGS_CSS_CLASS,
                'deleteButton' => true,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Select user roles which should be active on '.
                    'product page for special prices'
                ),
            ),
            'discountOrMakeUp' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Discount or Markup for Products'
                ),
                'type' => 'input_select',
                'values' => array(
                    'discount' => StringManagerWooUserRolePrices::getWord(
                        'discount'
                    ),
                    'markup' => StringManagerWooUserRolePrices::getWord(
                        'markup'
                    )
                ),
                'default' => 'discount',
                'fieldSetKey' => 'priceAdjustmentsTab',
                'classes' => 'festi-user-role-prices-top-border '.
                    static::GENERAL_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Provide discount or markups in fixed or percentage terms 
                    for all products on shop'
                ),
            ),
            'bothRegularSalePrice' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Use for both Regular price and Sale price'
                ),
                'type' => 'input_checkbox',
                'fieldSetKey' => 'priceAdjustmentsTab',
                'classes' => static::GENERAL_SETTINGS_CSS_CLASS,
                'lable' => '',
            ),
            'discountByRoles' => array(
                'caption' => '',
                'type' => 'multidiscount',
                'default' => array(),
                'fieldSetKey' => 'priceAdjustmentsTab',
                'deleteButton' => false,
                'classes' => static::GENERAL_SETTINGS_CSS_CLASS
            ),
            'showCustomerSavings' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Display Price Savings on'
                ),
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Display to customer regular price, the user role price '.
                    'with label &quot;Your Price&quot;, and the percent saved '.
                    'with label &quot;Savings&quot;'
                ),
                'type' => 'multi_select',
                'values' => array(
                    'product' => StringManagerWooUserRolePrices::getWord(
                        'Product Page'
                    ),
                    'archive' => StringManagerWooUserRolePrices::getWord(
                        'Products Archive Page (for Simple product)'
                    ),
                    'cartTotal' => StringManagerWooUserRolePrices::getWord(
                        'Cart Page (for Order Total)'
                    ),
                ),
                'default' => array(),
                'fieldSetKey' => 'generalTab',
                'classes' => 'festi-user-role-prices-top-border '.
                    static::GENERAL_SETTINGS_CSS_CLASS
            ),
            'customerSavingsLableColor' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Color for Savings Labels'
                ),
                'type'    => 'color_picker',
                'fieldSetKey' => 'generalTab',
                'default' => '#ff0000',
                'classes' => static::GENERAL_SETTINGS_CSS_CLASS,
                'eventClasses' => 'showCustomerSavings',
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Select color for text labels about customer savings '.
                    'Regular Price, Your Price, Savings'
                )
            ),
            'guestUserStatus' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Custom Text for Guest User'
                ),
                'type' => 'input_checkbox',
                'classes' => 'festi-user-role-prices-top-border '.
                    static::GENERAL_SETTINGS_CSS_CLASS,
                'lable' => StringManagerWooUserRolePrices::getWord(
                    'Enable Custom Text for Guest User'
                ),
                'fieldSetKey' => 'generalTab',
            ),
            'guestUserTextArea' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Custom User Text'
                ),
                'type' => 'textarea',
                'classes' => 'custom-guest-user-text '.
                    static::GENERAL_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Provide written text which '.
                    'will be displayed under of the price'
                ),
                'fieldSetKey' => 'generalTab',
            ),
            'hideAllProducts' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Enable Members-only WooCommerce Store'
                ),
                'type' => 'input_checkbox',
                'fieldSetKey' => 'hidingRulesTab',
                'classes' => static::HIDING_SETTINGS_CSS_CLASS,
                'lable' => StringManagerWooUserRolePrices::getWord(
                    'WooCommerce Hidden Store'
                ),
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Make your store invisible by default 
                    for all users except you chosen roles'
                )
            ),
            'rulesForNonRegistered' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Rules for Non-Registered Users'
                ),
                'type' => 'text',
                'fieldSetKey' => 'hidingRulesTab',
                'classes' => 'festi-h2 '.static::HIDING_SETTINGS_CSS_CLASS,
                'text' => ''
            ),
            'hideAddToCartButton' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Hide the &quot;Add to Cart&quot; Button'
                ),
                'type' => 'input_checkbox',
                'fieldSetKey' => 'hidingRulesTab',
                'classes' => 'festi-case-hide-add-to-cart-button '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'lable' => StringManagerWooUserRolePrices::getWord(
                    'Enable hidden the &quot;Add to Cart&quot; button'
                ),
            ),
            'textForNonRegisteredUsers' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Text Instead of '.
                    '&quot;Add to Cart&quot; button'
                ),
                'type' => 'textarea',
                'fieldSetKey' => 'hidingRulesTab',
                'classes' =>
                    'festi-case-text-instead-button-for-non-registered-users '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Provide written text which '.
                    'will be displayed on instead of &quot;Add to Cart&quot; 
                    button'
                )
            ),
            'onlyRegisteredUsers' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Hide the Prices'
                ),
                'type' => 'input_checkbox',
                'fieldSetKey' => 'hidingRulesTab',
                'classes' => 'festi-user-role-prices-top-border '.
                    'festi-case-only-registered-users  festi-padding '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'lable' => StringManagerWooUserRolePrices::getWord(
                    'Enable hidden prices for all products'
                ),
            ),
            'textForUnregisterUsers' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Text Instead of Price'
                ),
                'type' => 'textarea',
                'default' => StringManagerWooUserRolePrices::getWord(
                    'Please login or register to see price'
                ),
                'fieldSetKey' => 'hidingRulesTab',
                'classes' => 'festi-case-text-for-unregistered-users '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Provide written text which '.
                    'will be displayed on instead of the price'
                ),
            ),
            'rulesForRegistered' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Rules for Registered Users'
                ),
                'type' => 'text',
                'fieldSetKey' => 'hidingRulesTab',
                'classes' => 'festi-border-top-hiding-rules-tab festi-h2 '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'text' => ''
            ),
            'hideAddToCartButtonForUserRoles' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Hide the &quot;Add to Cart&quot; Button'
                ),
                'type' => 'multicheck',
                'default' => array(),
                'fieldSetKey' => 'hidingRulesTab',
                'deleteButton' => false,
                'classes' => static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Enable hidden the &quot;Add to Cart&quot;
                     button from certain user roles'
                ),
            ),
            'hidePriceForUserRoles' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Hide the Prices'
                ),
                'type' => 'multicheck',
                'default' => array(),
                'fieldSetKey' => 'hidingRulesTab',
                'deleteButton' => false,
                'classes' => 'festi-user-role-prices-top-border '.
                    'festi-case-hide-price-for-user-roles '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Enable hidden prices from certain user roles'
                ),
            ),
            'textForRegisterUsers' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Text with Hidden Price'
                ),
                'type' => 'textarea',
                'default' => StringManagerWooUserRolePrices::getWord(
                    'Price for your role is hidden'
                ),
                'fieldSetKey' => 'hidingRulesTab',
                'classes' => 'festi-case-text-for-registered-users '.
                    'festi-hint-upper '. static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Provide written text with certain '.
                    'roles which will be shown instead of the product price'
                )
            ),
            'hideEmptyPrice' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Hide Empty Price'
                ),
                'type' => 'multicheck',
                'default' => array(),
                'deleteButton' => false,
                'fieldSetKey' => 'hidingRulesTab',
                'classes' =>
                    'festi-case-hide-empty-price '.
                    'festi-user-role-prices-top-border '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Enable hidden empty price from certain user roles'
                ),
            ),
            'textForEmptyPrice' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Text Instead of Empty Price'
                ),
                'type' => 'textarea',
                'fieldSetKey' => 'hidingRulesTab',
                'classes' =>
                    'festi-case-text-instead-empty-price '.
                    static::HIDING_SETTINGS_CSS_CLASS,
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Provide written text which '.
                    'will be displayed on instead of empty price'
                )
            ),
            'taxHeader' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Genaral and Tax Options:'
                ),
                'type' => 'text',
                'fieldSetKey' => 'taxesTab',
                'classes' => 'festi-h2 '.static::TAX_SETTINGS_CSS_CLASS,
                'text' => ''
            ),
            'taxOptions' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Enable Tax Options'
                ),
                'type' => 'input_checkbox',
                'fieldSetKey' => 'taxesTab',
                'classes' => static::TAX_SETTINGS_CSS_CLASS,
                'lable' => StringManagerWooUserRolePrices::getWord(
                    'Enable'
                ),
                'hint' => StringManagerWooUserRolePrices::getWord(
                    'Check to enable Role specific tax options'
                )
            ),
            'taxTableHeader' => array(
                'caption' => StringManagerWooUserRolePrices::getWord(
                    'Tax Options Table'
                ),
                'type' => 'text',
                'fieldSetKey' => 'taxesTab',
                'text' => ''
            ),
            'taxByUserRoles' => array(
                'caption' => '',
                'type' => 'tax_table',
                'default' => array(),
                'fieldSetKey' => 'taxesTab',
                'deleteButton' => false,
                'classes' => static::TAX_SETTINGS_CSS_CLASS
            ),
            self::QUANTITY_DISCOUNT_OPTION_KEY => array(
                'caption' => __(
                    'Quantity Discount'
                ),
                'type' => 'quantity_discount_table',
                'default' => array(),
                'fieldSetKey' => 'quantityDiscountTab',
                'classes' => 'festi-h2 ',
                'text' => ''
            )
        );

        return $settings;
    } // end get
}