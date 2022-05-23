<?php

class WooUserRolePriceSavingsModule extends AbstractWooUserRoleModule
{
    public function onShowVariationPriceForCustomerSavings($isShow)
    {
        if ($this->_isShowCustomerSavingsOnProductPage()) {
            return true;
        }

        if ($this->_hasEqualRegularPriceinVariations()) {
            return true;
        }

        return $isShow;
    } // end onShowVariationPriceForCustomerSavings
    
    private function _isShowCustomerSavingsOnProductPage()
    {
        $settings = $this->frontend->getSettings();
        
        return !empty($settings) &&
               array_key_exists('showCustomerSavings', $settings) && 
               in_array('product', $settings['showCustomerSavings']);
    } // end _isShowCustomerSavingsOnProductPage
    
    private function _hasEqualRegularPriceInVariations()
    {
        $idProduct = $this->frontend->getMainProductID();

        if (!$idProduct) {
            return false;
        }

        $product = $this->frontend->createProductInstance($idProduct);

        $variableProduct = new FestiWooCommerceVariableProduct($product);

        $productsIDs = $variableProduct->getChildren($product);

        foreach ($productsIDs as $id) {

            $product = $this->frontend->createProductInstance($id);
            $regularPrice = $this->frontend->products->getRegularPrice(
                $product
            );

            if (isset($previousPrice) && $previousPrice != $regularPrice) {
                return false;
            }

            $previousPrice = $regularPrice;
        }

        return true;
    } // end _hasEqualRegularPriceInVariations
    
    public function hasConditionsForDisplayCustomerSavingsInProduct($product)
    {
        if (!$this->_hasNewPriceForProduct($product)) {
            return false;
        }

        return $this->frontend->hasOptionInSettings('showCustomerSavings') &&
               $this->frontend->isRegisteredUser() &&
               $this->_isAllowedPageToDisplayCustomerSavings($product) &&
               $this->_isAvailableProductTypeToDisplaySavings($product);
    } // end hasConditionsForDisplayCustomerSavingsInProduct
    
    private function _hasNewPriceForProduct($product)
    {
        if ($this->frontend->isVariableTypeProduct($product)) {
            return $this->_hasNewPriceForRangeProduct($product);
        }
        
        $idProduct = $this->frontend->products->getProductID($product);
        $rolePrice = $this->frontend->getRolePrice($idProduct);
        $hasDiscount = $this->frontend->
            hasDiscountOrMarkUpForUserRoleInGeneralOptions();
        
        return $rolePrice ||
               ($hasDiscount && !$this->frontend
                    ->isIgnoreDiscountForProduct($idProduct));
    } // end _hasNewPriceForProduct
    
    private function _hasNewPriceForRangeProduct($product)
    {
        $facade = $this->ecommerceFacade;

        $productsIDs = $facade->getVariationChildrenIDs($product);

        $flag = false;
        
        if ($productsIDs) {
            foreach ($productsIDs as $id) {
                $product = $this->frontend->createProductInstance($id);
                $hasNewPrice = $this->frontend->products->getUserPrice(
                    $product
                );
                if ($hasNewPrice) {
                    $flag = true;
                    break;
                }
            }
        }
        return $flag;
    } // end _hasNewPriceForRangeProduct
    
    private function _isAllowedPageToDisplayCustomerSavings($product)
    {
        $isEnabledProductPage = $this->_isEnabledPageInCustomerSavingsOption(
            'product'
        );
        
        $isEnabledArchivePage = $this->_isEnabledPageInCustomerSavingsOption(
            'archive'
        );
        
        $mainProduct = $this->_isMainProductInSimpleProductPage($product);
        
        $frontend = &$this->frontend;
        $isProductPage = $frontend->isProductPage();
        
        if ($isProductPage && $isEnabledProductPage && $mainProduct) {
            return true;
        }

        if (!$isProductPage && $isEnabledArchivePage) {
            return true;
        }
        
        if ($frontend->isProductParentMainProduct($product, $mainProduct)) {
            return true;
        }

        return false;
    } // end _isAllowedPageToDisplayCustomerSavings
    
    private function _isMainProductInSimpleProductPage($product)
    {
        $frontend = &$this->frontend;
        $idProduct = $this->ecommerceFacade->getProductID($product);

        return $idProduct == $frontend->getMainProductOnPage();
    } // end _isMainProductInSimpleProductPage
    
    private function _isAvailableProductTypeToDisplaySavings($product)
    {
        $frontend = &$this->frontend;

        return $frontend->products->isAvailableProductTypeToDisplaySavings(
            $product
        );
    } // end _isAvailableProductTypeToDisplaySavings
    
    public function onDisplayCustomerSavingsFilter($price, $product)
    {
        $frontend = &$this->frontend;
        $regularPrice = $frontend->products->getRegularPrice($product, true);
        
        $userPrice = $frontend->products->getUserPrice($product, true);
        
        $result = $this->_isAvailablePricesToDisplayCustomerSavings(
            $regularPrice,
            $userPrice
        );
        
        if (!$result) {
            return $price;
        }
        
        $regularPriceSuffix = $this->_getSuffixForRegularPrice($product);
        
        $userDiscount = $this->_fetchUserDiscount(
            $regularPrice,
            $userPrice,
            $product
        );
        $regularPrice = $frontend->getFormattedPrice($regularPrice);
        $formattedPrice = $frontend->getFormattedPrice($userPrice);
        
        $userPrice = $frontend->fetchPrice(
            $formattedPrice,
            WooUserRolePricesFrontendFestiPlugin::TYPE_PRICE_USER
        );
        
        $vars = array(
            'regularPrice'       => $frontend->fetchPrice($regularPrice),
            'userPrice'          => $userPrice,
            'userDiscount'       => $userDiscount,
            'priceSuffix'        => $frontend->products->getPriceSuffix(
                $product
            ),
            'regularPriceSuffix' => $regularPriceSuffix
        );

        if ($this->_isSubscribePluginProducts($product)) {
            $content = $frontend->fetch(
                'customer_subscription_product_savings_price.phtml', 
                $vars
            );
            
            return $content;
        }
        
        if (!$userPrice) {
            return $this->_fetchFreePrice($price);
        }

        return $frontend->fetch('customer_product_savings_price.phtml', $vars);
    }
    
    private function _isAvailablePricesToDisplayCustomerSavings(
        $regularPrice, $userPrice
    )
    {
        return $userPrice < $regularPrice;
    } // end _isAvailablePricesToDisplayCustomerSavings
    
    private function _getSuffixForRegularPrice($product)
    {
        return $this->frontend->products->getPriceSuffix(
            $product,
            $this->_getRegularPriceBeforeAnyTaxCalculationsProcessed($product)
        );
    } // end _getSuffixForRegularPrice
    
    private function _getRegularPriceBeforeAnyTaxCalculationsProcessed($product)
    {
        return $this->frontend->products->getRegularPrice($product, false);
    } // end _getRegularPriceBeforeAnyTaxCalculationsProcessed
    
    private function _fetchUserDiscount($regularPrice, $userPrice, $product)
    {
        $discount = round(100 - ($userPrice/$regularPrice * 100), 2);
        
        $vars = array(
            'discount' => $discount
        );

        return $this->frontend->fetch('discount.phtml', $vars);
    } // end _fetchUserDiscount
    
    private function _fetchFreePrice($price)
    {
        if ($this->frontend->hasDiscountOrMarkUpForUserRoleInGeneralOptions()) {
            return $price;
        }
        
        return $this->frontend->fetch('free.phtml');
    } // end _fetchFreePrice
    
    public function onDisplayCustomerTotalSavingsFilter($total)
    {
        $frontend = &$this->frontend;
        if (!$frontend->hasOptionInSettings('showCustomerSavings')
            || !$this->_isEnabledPageInCustomerSavingsOption('cartTotal')
            || !$frontend->isRegisteredUser()) {
            return $total;
        }
        
        $cart = WooCommerceCartFacade::getInstance();

        $userTotal = $cart->getTotal(); 
        $retailTotal = $this->getRetailTotal();
        $isGeneralTotals = $frontend->mainTotals;
        
        if ($this->_isSubscriptionInCart($cart)) {
            $this->setSubscriptionProductOption($cart);
            $userTotal = $this->getUserTotalWithSubscription($userTotal);
            $retailTotal = $this->getTotalRetailWithSubscription(
                $retailTotal, 
                $cart
            );
            $frontend->mainTotals = false;
        }
        
        if (!$this->_isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)) {
            return $total;
        }

        $totalSavings = $this->_getTotalSavings($retailTotal, $userTotal);

        $userTotal = $frontend->getFormattedPrice($userTotal);
        $retailTotal = $frontend->getFormattedPrice($retailTotal);
        
        $userPrice = $frontend->fetchPrice(
            $userTotal,
            WooUserRolePricesFrontendFestiPlugin::TYPE_PRICE_USER
        );
        
        $vars = array(
            'regularPrice'    => $frontend->fetchPrice($retailTotal),
            'userPrice'       => $userPrice,
            'userDiscount'    => $this->_fetchTotalSavings(
                $totalSavings
            ),
            'isGeneralTotals' => $isGeneralTotals
        );
        
        if ($isGeneralTotals && $this->_hasSubscriptionFee()) {
            $vars['fee'] = $frontend->getFormattedPrice(
                $frontend->subscriptionFee
            );
        }
        
        return $frontend->fetch('customer_total_savings_price.phtml', $vars);
    } // end onDisplayCustomerTotalSavingsFilter
    
    private function _isEnabledPageInCustomerSavingsOption($page)
    {
        $settings = $this->frontend->getSettings();

        return in_array($page, $settings['showCustomerSavings']);
    } // end _isEnabledPageInCustomerSavingsOption
    
    public function getRetailTotal()
    {
        $retailSubTotal = $this->getRetailSubTotalWithTax();
        $shippingTotal = $this->_getShippingTotalWithTax();
        $retailTotal = $retailSubTotal + $shippingTotal;

        return $retailTotal;
    } // end getRetailTotal
    
    public function getRetailSubTotalWithTax()
    {
        $cart = WooCommerceCartFacade::getInstance();
        
        $subtotal = $cart->getTotalFullPrice(); 
        
        $taxTotal = $cart->getTaxTotal();

        $taxPersent = $this->_getTaxTotalPersent($subtotal, $taxTotal);
        
        $this->frontend->taxPersent = $taxPersent;
        
        $retailSubTotal = $this->getRetailSubTotal();
        
        $retailSubTotalTax = $retailSubTotal / 100 * $taxPersent;
        
        $retailSubTotalWithTax = $retailSubTotal;
        
        if ($this->_isTaxExcludedFromPriceAndDisplaysSeparately($cart)) {
            $retailSubTotalWithTax += $retailSubTotalTax;
        }
        
        return $retailSubTotalWithTax;
    } // end getRetailSubTotalWithTax
    
    public function getUserTotalWithSubscription($total)
    {
        $frontend = &$this->frontend;
        $product = $frontend->subscribeProduct;
        
        if (!$frontend->mainTotals) {
            $userPrice = $this->_getUserPriceForSubscriptions($product);
            $userPrice = $userPrice * $frontend->subscriptionCount;
            $userPrice += $this->_getShippingTotalWithTax();
            $subscriptionTax = $frontend->subscriptionTax;
            $userPrice = $userPrice * $subscriptionTax / 100 + $userPrice;
            
            return $userPrice;
        }
        
        if ($frontend->subscriptionFee) {
            $total = $total - $frontend->subscriptionFee;
        }
        
        return $total;
    } // end getUserTotalWithSubscription
    
    public function getTotalRetailWithSubscription($total, $cart)
    {
        $frontend = &$this->frontend;
        $product = $frontend->subscribeProduct;

        $shippingCost = $this->_getShippingCost($cart);
        
        if (!$frontend->mainTotals) {
            
            $regularPrice = $this->_getRegularPriceForSubscription($product);
            $regularPrice += $shippingCost;
            
            $priceWidthTax = $regularPrice * $frontend->subscriptionTax / 100;
            $priceWidthTax += $regularPrice;
            
            $regularPrice = $priceWidthTax * $frontend->subscriptionCount;
            return $regularPrice; 
        }
        
        $isTrial = $this->_isTrialSubscription($product);
        
        if ($isTrial) {
            $price = $this->subscriptionPrice * $frontend->subscriptionCount;
            $total = $total - $price;
        }
        
        if ($frontend->isOnlySubscriptionInCart($cart) || !$isTrial) {
            return $total;
        }
        
        $total = $this->_getTotalRetailWithoutSubscription($cart);
        
        $total += $shippingCost;
        
        return $total;
    } // end getTotalRetailWithSubscription
    
    private function _getTaxTotalPersent($subtotal, $taxTotal)
    {
        if ($subtotal == 0) {
            return 0;
        }   
            
        $taxPersent = 100 * $taxTotal / $subtotal;
        
        return $taxPersent;
    } // end _getTaxTotalPersent
    
    public function getRetailSubTotal()
    {
        $cart = WooCommerceCartFacade::getInstance();
        $products = $cart->getProducts();

        $total = 0;
        $displayMode = ($cart->isDisplayPricesIncludeTax()) ? true : false;

        foreach ($products as $key => $product) {
            if ($this->_isVariableProduct($product)) {
                $idProduct = $product['variation_id'];
            } else {
                $idProduct = $product['product_id'];
            }
            
            $productInstance = $this->frontend->createProductInstance(
                $idProduct
            );
            $price = $this->frontend->products->getRegularPrice(
                $productInstance,
                $displayMode
            );
            
            $total += $price * $product['quantity'];
        }
        
        return $total;
    } // end getRetailSubTotal
    
    private function _isVariableProduct($product)
    {
        return array_key_exists('variation_id', $product) &&
               !empty($product['variation_id']);
    } // end _isVariableProduct
    
    private function _isTaxExcludedFromPriceAndDisplaysSeparately($cart)
    {
        return (!$cart->isDisplayPricesIncludeTax() &&
               !$cart->isTaxInclusionOptionOn());
    } // end _isTaxExcludedFromPriceAndDisplaysSeparately
    
    private function _getUserPriceForSubscriptions($subscribeProduct)
    {
        $id = $this->frontend->getProductID($subscribeProduct);
        $product = $this->frontend->createProductInstance($id);
        
        $userPrice = $this->frontend->products->getUserPrice($product, true);
        
        return $userPrice;
    } // _getUserPriceForSubscriptions
    
    private function _getShippingTotalWithTax()
    {
        $cart = WooCommerceCartFacade::getInstance();
        
        $shippingTotal = $cart->getShippingTotal();
        $shippingTaxTotal = $cart->getShippingTaxTotal();

        return $shippingTotal + $shippingTaxTotal;
    } // end _getShippingTotalWithTax
    
    private function _isRetailTotalMoreThanUserTotal($retailTotal, $userTotal)
    {
        return $retailTotal > $userTotal;
    } // end _isRetailTotalMoreThanUserTotal
    
    private function _getTotalSavings($retailTotal, $userTotal)
    {        
        $savings = round(100 - ($userTotal/$retailTotal * 100), 2);
        
        return $savings;
    } // end _getTotalSavings
    
    private function _fetchTotalSavings($totalSavings)
    {
        $vars = array(
            'discount' => $totalSavings
        );

        return $this->frontend->fetch('discount.phtml', $vars);
    } // end _fetchTotalSavings
    
    private function _isSubscriptionInCart($cart)
    {   
        $products = $cart->getProducts();

        foreach ($products as $key => $value) {
            $product = $value['data'];
            
            if (!$this->_isSubscribePluginProducts($product)) {
                continue;
            }
            
            if ($this->_isSubscriptionRenewal($value)) {
                return false;
            }
            
            $this->frontend->subscriptionKey = $key;

            return true;
        }
        
        return false;
    } // end _isSubscriptionInCart
    
    private function _isSubscriptionRenewal($subscription)
    {
        return !empty($subscription['subscription_renewal']);
    } // end _isSubscriptionRenewal
    
    public function setSubscriptionProductOption($cart)
    {
        $frontend = &$this->frontend;
        $products = $cart->getProducts();
        $product = $products[$frontend->subscriptionKey];
        
        $frontend->subscribeProduct = $product['data'];
        $frontend->subscriptionCount = $this->_getSubscriptionProductsCount(
            $product
        );
       
        $frontend->subscriptionTax = $this->_getSubscriptionTaxPersent(
            $product
        );
        
        $coupons = $cart->getCoupons();
        
        $frontend->subscriptionFee = $this->_getFee($product['data'], $coupons);
        
        $this->subscriptionPrice = $this->_getSubscriptionPriceWithTaxAndFee(
            $product
        );
    } // end setSubscriptionProductOption
    
    private function _getSubscriptionProductsCount($product)
    {
        return $product['quantity'];
    } // end getSubscriptionProductsCount

    private function _getSubscriptionTaxPersent($product)
    {
        $total = $product['line_total'];
        $taxTotal = $product['line_tax'];
        
        $percent = $taxTotal / ($total / 100);

        return $percent;
    } // end _getSubscriptionTaxPersent
    
    private function _getTotalRetailWithoutSubscription($cart)
    {
        $products = $cart->getProducts();
        $total = 0;
        
        foreach ($products as $product) {
            if ($this->_isSubscribePluginProducts($product['data'])) {
                continue;
            }

            $price = $product['data']->price * $product['quantity'];
            $taxPersent = $this->_getSubscriptionTaxPersent($product);
            $tax = $price / 100 * $taxPersent;
            $total += $price + $tax;
        }
        
        return $total;
    } // end _getTotalRetailWithoutSubscription
    
    private function _getFee($subscription, $coupons = false)
    {
        $fee = false;
        
        if (!$this->_isFeeExist($subscription)) {
            return $fee;
        }
        
        $frontend = &$this->frontend;

        $facade = $this->ecommerceFacade;
        
        $fee = $facade->getSubscriptionSignUpFee($subscription);
        
        if ($this->_isTaxExist()) {
            $discountCoupon = $this->_getCouponsDiscount($coupons);
            if ($discountCoupon) {
               $feeCupon = $fee - $fee * $discountCoupon / 100;
               $fee = $feeCupon + $feeCupon * $frontend->subscriptionTax / 100; 
            } else {
                $feeTax = $fee * $frontend->subscriptionTax / 100; 
                $fee = $fee + $feeTax;
            }
           
        }

        $fee = $fee * $frontend->subscriptionCount;
        
        return $fee;
    } // end _getFee

    private function _isFeeExist($product)
    {
        $facade = $this->ecommerceFacade;

        return $facade->getSubscriptionSignUpFee($product);
    } // end _isFeeExist
    
    private function _isTaxExist()
    {
        return $this->frontend->taxPersent > 0;
    } // end _isTaxExist
    
    private function _isSubscribePluginProducts($product)
    {
        $types = array(
            'subscription_variation',
            'subscription',
            'variable-subscription'
        );
        
        $type = $this->ecommerceFacade->getProductType($product);
        
        return in_array($type, $types);
    } // end _isSubscribePluginProducts
    
    private function _getSubscriptionPriceWithTaxAndFee($product)
    {
        $fee = $this->frontend->subscriptionFee;

        $facade = $this->ecommerceFacade;

        $price = $facade->getSubscriptionPrice($product['data']);
        
        $priceTax = ($price / 100) * $this->frontend->subscriptionTax;
        
        return $fee + $price + $priceTax;
    } // end _getSubscriptionPriceWithTaxAndFee
    
    private function _hasSubscriptionFee()
    {
        return $this->frontend->subscriptionFee;
    } // end _hasSubscriptionFee
    
    private function _isTrialSubscription($product)
    {
        return !empty($product->subscription_trial_length);
    } // end _isTrialSubscription
    
    private function _getCouponsDiscount($coupons)
    {
        $discounts = array();
        
        foreach ($coupons as $key => $item) {
            if ($item->discount_type == 'sign_up_fee_percent') {
                $discounts[] = $item->coupon_amount; 
            }
        }
        
        return count($discounts) > 0 ? max($discounts) : false;
    } // end _getCouponsDiscount
    
    private function _getShippingCost($cart)
    {
        $shippingMethods = WC()->session->get(
            'chosen_shipping_methods',
            array()
        );

        $shippingCost = $cart->getShippingCost($shippingMethods);
        
        return $shippingCost;
    } // end _getShippingCost
    
    private function _getRegularPriceForSubscription($subscribeProduct) 
    {
        $frontend = &$this->frontend;
        $id = $frontend->getProductID($subscribeProduct);
        $product = $frontend->createProductInstance($id);
        
        $regularPrice = $frontend->products->getRegularPrice($product, true);

        return $regularPrice;
    } // end _getRegularPriceForSubscription
}