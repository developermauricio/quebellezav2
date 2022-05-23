<?php
    
class WooUserRoleHideProductModule extends AbstractWooUserRoleModule
{
    private static $_categoryIDs = array();

    private $_productCategoryKey;

    public function onHideSubcategoryTerms($terms, $taxonomies)
    {
        if (!$this->_hasProductCategoryInTaxonomies($terms, $taxonomies)) {
            return $terms;
        }

        foreach ($terms as $key => $term) {
            if (!$this->_isCategoryVisible($term->term_id)) {
                unset($terms[$key]);
            }
        }

        return $terms;
    } // end onHideSubcategoryTerms

    public function onHideProductByUserRole($query)
    {
        $ecommerceFacade = EcommerceFactory::getInstance();

        $engineFacade = EngineFacade::getInstance();

        $frontend = $this->frontend;
        $className = get_class($ecommerceFacade);
        $this->_productCategoryKey = $className::PRODUCT_CATEGORY_KEY;

        if (!$ecommerceFacade->isProductPost($query) &&
            !$engineFacade->hasSearchParamsInQuery($query)
        ) {
            return false;
        }

        $userRole = $this->userRole;

        if ($frontend->hasHideAllProductsOptionInSettings()) {
            $this->_setPrepareQueryForSearchVisibleProducts(
                $userRole,
                $query
            );
        } else {
            $this->_setPrepareQueryWithoutHiddenAllProductsOption(
                $frontend,
                $userRole,
                $query
            );
        }
    } // end onHideProductByUserRole
    
    private function _hasHideProductByUserRole($userRole, $hideProducts)
    {
        $facade = EngineFacade::getInstance();

        return !$facade->isAdminPanel() &&
                $userRole && 
                array_key_exists($userRole, $hideProducts) &&
                $hideProducts[$userRole];
    } // end _hasHideProductByUserRole
    
    private function _hasHideProductByGuestUser($userRole, $hideProducts)
    {
        $facade = EngineFacade::getInstance();

        return !$facade->isAdminPanel() &&
               !$userRole &&
               $hideProducts;
    } // end _hasHideProductByGuestUser

    private function _getProductIDsForGuestUser($hideProducts)
    {
        $productIDs = array();

        foreach ($hideProducts as $key => $value) {

            if (empty($value)) {
                continue;
            }

            $productIDs = array_merge($value, $productIDs);
        }
        
        return array_unique($productIDs);
    } // end _getProductIDsForGuestUser

    private function _setPrepareQueryForSearchVisibleProducts(
        $userRole,
        $query
    )
    {
        $categories = $this->frontend->getOptions(
            PRICE_BY_ROLE_CATEGORY_VISIBILITY_OPTIONS
        );

        if ($this->_hasUserRoleInCategories($userRole, $categories)) {
            static::$_categoryIDs = $categories[$userRole];
        }

        if (!$userRole) {
            $userRole = 'guestUser';
        }

        $userRole = base64_encode($userRole);

        $metaKey = PRICE_BY_ROLE_IGNORE_HIDE_ALL_PRODUCT_OPTION_META_KEY;

        $query->set('meta_key', $metaKey);

        $facade = EngineFacade::getInstance();
        $engineClassName = get_class($facade);

        $query->set(
            $engineClassName::META_QUERY_KEY,
            array(
                'key'     => $metaKey,
                'value'   => $userRole,
                'compare' => 'LIKE'
            )
        );
    } // end _setPrepareQueryForSearchVisibleProducts

    private function _setPrepareQueryWithoutHiddenAllProductsOption(
        $frontend,
        $userRole,
        $query
    )
    {
        $hideProducts = $frontend->getOptions(
            PRICE_BY_ROLE_HIDDEN_PRODUCT_OPTIONS
        );

        if (!$hideProducts) {
            $hideProducts = array();
        }

        $productIDs = array();

        if ($this->_hasHideProductByGuestUser($userRole, $hideProducts)) {
            $productIDs = $this->_getProductIDsForGuestUser($hideProducts);
        }

        if ($this->_hasHideProductByUserRole($userRole, $hideProducts)) {
            $productIDs = $hideProducts[$userRole];
        }

        if ($productIDs) {
            $this->_setPrepareProductCategories($productIDs);
            $query->set('post__not_in', $productIDs);
        }
    } // end _setPrepareQueryWithoutHiddenAllProductsOption

    private function _hasProductCategoryInTaxonomies($terms, $taxonomies)
    {
        $facade = EngineFacade::getInstance();

        return !$facade->isAdminPanel() &&
               is_array($taxonomies) &&
               in_array($this->_productCategoryKey, $taxonomies) &&
               is_object(current($terms));
    } // end _hasProductCategoryInTaxonomies

    private function _setPrepareProductCategories($productIDs)
    {
        $facade = EngineFacade::getInstance();

        $allCategories = array();
        $categoryIDs = array();

        foreach ($productIDs as $idProduct) {
            $productCategories = $facade->getPostTermsByPostID(
                $idProduct,
                $this->_productCategoryKey
            );

            foreach ($productCategories as $category) {

                $idCategory = $category->term_id;

                if (isset($allCategories[$idCategory])) {
                    $category = $allCategories[$idCategory];
                }

                $category->count -= 1;

                if ($category->count == 0) {
                    $categoryIDs[] = $idCategory;
                }

                $allCategories[$idCategory] = $category;
            }
        }

        static::$_categoryIDs = $categoryIDs;
    } // end _setPrepareProductCategories

    private function _isCategoryVisible($idTerm)
    {
        if ($this->frontend->hasHideAllProductsOptionInSettings()) {
            return in_array($idTerm, static::$_categoryIDs);
        }

        return !in_array($idTerm, static::$_categoryIDs);
    } // end _isCategoryVisible

    private function _hasUserRoleInCategories($userRole, $categories)
    {
        return $userRole &&
               is_array($categories) &&
               array_key_exists($userRole, $categories);
    } // end _hasUserRoleInCategories
}