<?php

if (!interface_exists('IWooCommerceCart')) {
    require_once __DIR__.DIRECTORY_SEPARATOR.'IWooCommerceCart.php';
}

class WooCommerceCartFacade implements IWooCommerceCart
{
    private static $_instance = null;

    private $_cart;

    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    } // end getInstance

    public function __construct()
    {
        $this->_cart = $this->getCartInstance();
    } // end __construct

    public function getTotal()
    {
        return $this->_cart->total;
    } // end getTotal

    public function getTotalExcludeTax()
    {
        $cart = $this->_cart;
        $total = $cart->total - $cart->tax_total - $cart->shipping_tax_total;
        $total = ($total < 0) ? 0 : $total;

        return $total;
    } // end getTotalExcludeTax

    public function getTaxTotal()
    {
        return $this->_cart->tax_total;
    } // end getTaxTotal

    public function getSubtotal()
    {
        return $this->_cart->subtotal;
    } // end getSubtotal

    public function getSubtotalExcludeTax()
    {
        return $this->_cart->subtotal_ex_tax;
    } // end getSubtotalExcludeTax

    public function getShippingTotal()
    {
        return $this->_cart->shipping_total;
    } // end getShippingTotal

    public function getShippingTaxTotal()
    {
        return $this->_cart->shipping_tax_total;
    } // end getShippingTaxTotal

    public function getProducts()
    {
        return $this->_cart->cart_contents;
    } // end getProducts

    public function &getCartInstance()
    {
        $facade = WooCommerceFacade::getInstance();

        $wooCommerce = $facade->getWooCommerceInstance();

        if (!isset($wooCommerce->cart)) {
            throw new Exception("WooCommerce Cart instance not defined");
        }

        return $wooCommerce->cart;
    } // end getCartInstance

    public function isDisplayPricesIncludeTax()
    {
        $facade = WooCommerceFacade::getInstance();

        return $facade->isDisplayPricesIncludeTax('cart');
    } // end isDisplayPricesIncludeTax

    public function isTaxInclusionOptionOn()
    {
        return $this->_cart->prices_include_tax == 1;
    } // end isTaxInclusionOptionOn

    public function getShippingCost($shippingMethods)
    {
        foreach (WC()->cart->get_shipping_packages() as $singlePackage) {

            $package = WC()->shipping->calculate_shipping_for_package(
                $singlePackage
            );

            // Only display the costs for the chosen shipping method
            foreach ($shippingMethods as $key) {
                if (isset($package['rates'][ $key ])) {
                    $shippingMethods['cost'] = $package['rates'][$key];
                }
            }
        }

        if (array_key_exists('cost', $shippingMethods)) {
            return $shippingMethods['cost']->cost;
        }

        return false;
    } // end getShippingCost

    public function getCoupons()
    {
        return $this->_cart->coupons;
    } // end getCoupons

    public function getTotalFullPrice()
    {
        return $this->_cart->cart_contents_total;
    } // end getTotalFullPrice

    public function doEmptyCart()
    {
        return $this->_cart->empty_cart();
    } // end doEmptyCart

    public function getCartContentsCount()
    {
        return $this->_cart->get_cart_contents_count();
    } // end getCartContentsCount

    public function addToCart(
        $idProduct = 0,
        $quantity = 1,
        $idVariation = 0,
        $variation = array(),
        $cartItemData = array()
    )
    {
        return $this->_cart->add_to_cart(
            $idProduct,
            $quantity,
            $idVariation,
            $variation,
            $cartItemData
        );
    } // end addToCart
}