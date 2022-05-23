<?php

class PriceByUserRoleCorePlugin extends DisplayPlugin
{
    const PRICE_BY_ROLE_PRODUCT_PRICES_TABLE = 'festi_user_role_product_prices';
    private $_ecommercePlugin;
    private $_ecommerceFacade;

    /**
     * @override
     */
    public function onInit()
    {
        parent::onInit();

        $this->_ecommercePlugin =
            FestiCoreStandalone::getActiveEcommercePluginInstance();

        $this->_ecommerceFacade = EcommerceFactory::getInstance();

        new ModulesSwitchListener(
            $this->core,
            $this->object,
            $this->_ecommercePlugin
        );
    }

    public function getRoleSalePrice($idProduct, $idUser = false)
    {
        $roles = $this->_ecommercePlugin->getAllUserRoles($idUser);

        if (!$roles) {
            return false;
        }

        $priceList = $this->getProductPrices($idProduct);

        $prices = array();

        foreach ($roles as $key => $role) {
            if ($this->_hasSalePriceForUserRole($priceList, $role)) {
                $prices[] = $this->_ecommercePlugin->getPriceWithFixedFloat(
                    $priceList['salePrice'][$role]
                );
            }
        }

        if ($this->_ecommercePlugin->isWpmlMultiCurrencyOptionOn()) {
            $prices = $this->_getWpmlMultiCurrencyRolePrices(
                $priceList,
                $roles,
                $idProduct,
                true
            );
        }

        if ($prices) {
            return min($prices);
        }

        return false;
    } // end getRolePrice

    public function getProductPrices($idProduct)
    {
        return $this->getMetaOptionsForProduct(
            $idProduct,
            PRICE_BY_ROLE_PRICE_META_KEY
        );
    } // end getProductPrices

    public function getMetaOptionsForProduct($idProduct, $optionName)
    {
        if (!$idProduct) {
            $post = $this->_ecommercePlugin->getWordpressPostInstance();
            $idProduct = $post->ID;
        }

        $values = $this->_ecommercePlugin->getMetaOptions($idProduct, $optionName);

        if (!$values) {
            $values = array();
        }

        return $values;
    } // end getMetaOptionsForProduct

    private function _hasSalePriceForUserRole($priceList, $role)
    {
        $plugin = $this->_ecommercePlugin;

        return $plugin->hasRolePriceInProductOptions($priceList, $role) &&
            !$plugin->isDiscountOrMarkupEnabledByRole($role) &&
            $this->_hasExistSalePriceForUserRole($priceList, $role) &&
            $this->_hasScheduleForSalePriceRole($priceList, $role);
    } // end _getUserPrices

    private function _hasExistSalePriceForUserRole($priceList, $role)
    {
        return array_key_exists('salePrice', $priceList) &&
            array_key_exists($role, $priceList['salePrice']) &&
            !empty($priceList['salePrice'][$role]);
    } // end _getWpmlMultiCurrencyRolePrices

    private function _hasScheduleForSalePriceRole($priceList, $role)
    {
        if ($this->_hasScheduleFiledForSalePrice($priceList, $role)) {
            $dateNow = time();

            $dateFrom = $this->_getTimeSalePrice(
                $priceList,
                $role,
                'date_from'
            );

            $dateTo = $this->_getTimeSalePrice($priceList, $role, 'date_to');

            if ($dateFrom && $dateTo) {
                return ($dateNow >= $dateFrom && $dateNow <= $dateTo);
            } else if ($dateFrom && !$dateTo) {
                return ($dateNow >= $dateFrom);
            } else if (!$dateFrom && $dateTo) {
                return ($dateNow <= $dateTo);
            }
        }

        return true;
    } // end _getAllRolesPrices

    private function _hasScheduleFiledForSalePrice($priceList, $role)
    {
        return array_key_exists('schedule', $priceList) &&
            array_key_exists($role, $priceList['schedule']);
    } // end getRoleSalePrice

    private function _getTimeSalePrice($priceList, $role, $dateName)
    {
        $date = 0;

        if (array_key_exists($dateName, $priceList['schedule'][$role])) {
            $date = strtotime($priceList['schedule'][$role][$dateName]);
        }

        return $date;
    } // end _hasSalePriceForUserRole

    private function _getWpmlMultiCurrencyRolePrices(
        $priceList,
        $roles,
        $idProduct,
        $salePrices = false
    )
    {
        $wpmlCurrencyManager = new WpmlCurrencyCompatibilityManager(
            $this->_ecommercePlugin
        );

        return $wpmlCurrencyManager->getPrices(
            $priceList,
            $roles,
            $idProduct,
            $salePrices
        );
    } // end _hasExistSalePriceForUserRole

    public function updateProductPrices($idPost, $prices)
    {
        $this->_ecommercePlugin->updateMetaOptions(
            $idPost,
            $prices,
            PRICE_BY_ROLE_PRICE_META_KEY
        );

        $values['id_post'] = $idPost;
        $search['id_post'] = $idPost;

        $tableName = self::PRICE_BY_ROLE_PRODUCT_PRICES_TABLE;

        $object = $this->object;

        $object->begin();

        foreach ($prices as $role => $value) {
            $userRole = $this->_ecommercePlugin->isUserRoleExist($role);

            if (!$userRole) {
                continue;
            }

            $values['user_role'] = $role;
            $values['price'] = $value;
            $values['sale_price'] = '';

            if ($this->_hasSalePriceInRolePricesList($prices)) {
                $values['sale_price'] = $prices['salePrice'][$role];
            }

            $search['user_role'] = $role;

            $rowData = $this->object->getRolePricesRowByPostID($tableName, $search);

            if ($rowData) {
                $object->update($tableName, $values, $search);
            } else {
                $object->insert($tableName, $values);
            }
        }

        $object->commit();
    } // end _hasScheduleForSalePriceRole

    private function _hasSalePriceInRolePricesList($priceList)
    {
        return array_key_exists('salePrice', $priceList);
    } // end _hasScheduleFiledForSalePrice

    public function getRolePricesVariableProductByPriceType($product, $type)
    {
        $plugin = $this->_ecommercePlugin;

        if (!$plugin->isVariableTypeProduct($product)) {
            return false;
        }

        $facade = EcommerceFactory::getInstance();

        $productsIDs = $facade->getVariationChildrenIDs($product);

        if (!$productsIDs) {
            return false;
        }

        $prices = array();

        foreach ($productsIDs as $id) {
            $productChild = $plugin->createProductInstance($id);
            if (!$plugin->hasProductID($productChild)) {
                continue;
            }

            if ($this->_isRolePriceTypeRegular($type)) {
                $price = $plugin->getPrice($productChild);
            } else {
                $price = $plugin->getSalePrice($productChild);
            }

            if (!$price) {
                continue;
            }

            if ($plugin->isIncludingTaxesToPrice()) {
                $price = $facade->doIncludeTaxesToPrice($product, $price);
            }

            $prices[] = $price;
        }

        return $prices;
    } // end _getTimeSalePrice

    private function _isRolePriceTypeRegular($type)
    {
        return $type == PRICE_BY_ROLE_TYPE_PRODUCT_REGULAR_PRICE;
    } // end updateProductPrices

    public function getPriceWithDiscountOrMarkUp(
        $product, $originalPrice, $isSalePrice = true
    )
    {
        $plugin = $this->_ecommercePlugin;

        $pluginClassName = get_class($plugin);

        $amount = $plugin->getAmountOfDiscountOrMarkUp();

        $idPost = $this->_ecommerceFacade->getProductID($product);

        if ($this->_ecommerceFacade->getVariationProductID($product)) {
            $idPost = $this->_ecommerceFacade->getVariationProductID($product);
        }

        if ($plugin->isIgnoreDiscountForProduct($idPost)) {
            $rolePrice = $this->getRolePrice($idPost);
            return $rolePrice ? $rolePrice : $originalPrice;
        }

        $isNotRoleDiscountType = false;
        $price = PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE;

        if ($plugin->isRolePriceDiscountTypeEnabled()) {
            $price = $plugin->getPrice($product);

            if (!$price) {
                $isNotRoleDiscountType = true;
            }
        }

        if (!$price) {
            $price = $plugin->products->getRegularPrice($product);

            if ($isSalePrice && $plugin->isAllowSalePrices($product)) {
                $price = $this->_ecommerceFacade->getSalePrice($product);
                $pluginClassName::$isSalePrices[$idPost] = true;
            }
        }

        if ($isNotRoleDiscountType) {
            return $price;
        }

        if ($plugin->isPercentDiscountType()) {
            $amount = $plugin->getAmountOfDiscountOrMarkUpInPercentage(
                $price,
                $amount
            );
        }

        $price = floatval($price);

        if ($plugin->isDiscountTypeEnabled()) {
            $minimalPrice = PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE;
            $newPrice = ($amount > $price) ? $minimalPrice : $price - $amount;
        } else {
            $newPrice = $price + $amount;
        }

        $numberOfDecimals = $this->_ecommerceFacade->getNumberOfDecimals();

        if (!$numberOfDecimals) {
            $newPrice = round($newPrice);
        }

        return $newPrice;
    } // end getRolePricesVariableProductByPriceType

    public function getRolePrice($idProduct, $idUser = false)
    {
        $roles = $this->_ecommercePlugin->getAllUserRoles($idUser);

        if (!$roles) {
            return false;
        }

        $priceList = $this->getProductPrices($idProduct);

        if (!$priceList) {
            return false;
        }

        $prices = $this->_getUserPrices($priceList, $roles, $idProduct);

        if (!$prices) {
            return false;
        }

        return min($prices);
    } // end _isRolePriceTypeRegular

    private function _getUserPrices($priceList, $roles, $id)
    {
        if ($this->_ecommercePlugin->isWpmlMultiCurrencyOptionOn()) {
            return $this->_getWpmlMultiCurrencyRolePrices(
                $priceList,
                $roles,
                $id
            );
        }

        return $this->_getAllRolesPrices($priceList, $roles);
    } // end getPriceWithDiscountOrMarkUp

    protected function _getAllRolesPrices($priceList, $roles)
    {
        $prices = array();

        foreach ($roles as $key => $role) {
            if (!$this->_ecommercePlugin->hasRolePriceInProductOptions($priceList, $role)) {
                continue;
            }

            $prices[] = $this->_ecommercePlugin->getPriceWithFixedFloat(
                $priceList[$role]
            );
        }

        return $prices;
    } // end _hasSalePriceInRolePricesList
}