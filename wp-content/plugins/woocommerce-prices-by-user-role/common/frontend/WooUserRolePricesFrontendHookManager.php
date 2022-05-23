<?php

class WooUserRolePricesFrontendHookManager
{
    private $_engine;
    private $_ecommerceFacade;
    
    public function __construct(&$engine)
    {
        $this->_engine = &$engine;
        $this->_ecommerceFacade = EcommerceFactory::getInstance();
    } // end __construct

    public function onInit()
    {
        $this->_engine->addFilterListener(
            'woocommerce_variable_price_html',
            'onProductPriceRangeFilter',
            10,
            4
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_variable_sale_price_html',
            'onProductPriceRangeFilter',
            10,
            4
        );

        $this->_engine->addFilterListener(
            'woocommerce_get_variation_price',
            'onVariationPriceFilter',
            10,
            4
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_variable_empty_price_html',
            'onProductPriceRangeFilter',
            10,
            4
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_get_price_html_from_to',
            'onSalePriceToNewPriceTemplateFilter',
            10,
            4
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_grouped_price_html',
            'onProductPriceRangeFilter',
            10,
            2
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_grouped_empty_price_html',
            'onProductPriceRangeFilter',
            10,
            2
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_cart_item_price',
            'onProductPriceOnlyRegisteredUsers',
            10,
            1
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_cart_item_subtotal',
            'onProductPriceOnlyRegisteredUsers',
            10,
            1
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_cart_subtotal',
            'onProductPriceOnlyRegisteredUsers',
            10,
            1
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_cart_totals_order_total_html',
            'onProductPriceOnlyRegisteredUsers',
            10,
            1
        );
        
        $this->_engine->addActionListener(
            'pre_get_posts',
            'onHideProductByUserRole'
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_show_variation_price',
            'onShowVariationPriceForCustomerSavings'
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_price_filter_widget_max_amount',
            'onPriceFilterWidgetMaxAmount'
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_price_filter_widget_min_amount',
            'onPriceFilterWidgetMinAmount'
        );
        
        $this->_engine->addFilterListener(
            $this->_ecommerceFacade->getHookNameForPriceFilter(),
            $this->_ecommerceFacade->getMethodNameForPriceFilter(),
            10,
            3
        );
        
        $this->_engine->addFilterListener(
            'woocommerce_sale_flash',
            'onHideSelectorSaleForProduct',
            10,
            3
        );

        $this->_engine->addFilterListener(
            'woocommerce_product_is_on_sale',
            'onSalePriceCheck',
            10,
            2
        );
        
        $this->_engine->addFilterListener(
            $this->_ecommerceFacade->getHookNameForGetPrice(),
            'onRemovePriceForUnregisteredUsers',
            10,
            2
        );
    } // end onInit
}