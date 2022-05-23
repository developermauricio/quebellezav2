<?php

interface IUserRolePriceFacade
{
    public function updatePriceFilterQueryForProductsSearch(
        $wordPressQuery,
        $eCommerceQuery,
        $instance
    );

    public function getPriceByRolePriceFilter($price, $product, $engine);
}
