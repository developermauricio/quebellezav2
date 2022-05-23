<?php

if (!class_exists("WooCommerceCartFacade")) {
    $path = '/festi/woocommerce/WooCommerceCartFacade.php';
    require_once __DIR__.$path;
}

if (!class_exists("WooUserRolePricesFrontendHookManager")) {
    $path = '/frontend/WooUserRolePricesFrontendHookManager.php';
    require_once __DIR__ . $path;
}

if (!class_exists("WooUserRoleModule")) {
    require_once __DIR__.'/frontend/modules/WooUserRoleModule.php';
}
