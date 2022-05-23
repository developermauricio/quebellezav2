<?php

$festiSdkPath = __DIR__.DIRECTORY_SEPARATOR;
$corePath = $festiSdkPath.'core'.DIRECTORY_SEPARATOR;

$woocommerceDir = $festiSdkPath.'woocommerce'.DIRECTORY_SEPARATOR;
$ecommerceDir = $festiSdkPath.'ecommerce'.DIRECTORY_SEPARATOR;
$envatoDir = $festiSdkPath.'envato'.DIRECTORY_SEPARATOR;

if (!class_exists('WooCommerceCacheHelper')) {
    require_once $woocommerceDir.'WooCommerceCacheHelper.php';
}

if (!class_exists('HttpClient')) {
    require_once $festiSdkPath.'httpclient/HttpClient.php';
}

if (!class_exists('FestiObject')) {
    require_once $festiSdkPath.'FestiObject.php';
}

if (!class_exists('EngineFacade')) {
    require_once $festiSdkPath.'engine/EngineFacade.php';
}

if (!class_exists('FestiPlugin')) {
    require_once $festiSdkPath.'FestiPlugin.php';
}

if (!class_exists("DataAccessObject")) {
    require_once $corePath.'bundle/database/DataAccessObject.php';
}

if (!class_exists('WooCommerceProductValuesObject')) {
    require_once $woocommerceDir.'WooCommerceProductValuesObject.php';
}

if (!class_exists('EcommerceFactory')) {
    require_once $ecommerceDir.'EcommerceFactory.php';
}

if (!interface_exists('IEcommerceFacade')) {
    require_once $ecommerceDir.'IEcommerceFacade.php';
}

if (!class_exists('EcommerceFacade')) {
    require_once $ecommerceDir.'EcommerceFacade.php';
}

if (!class_exists('UnsupportableFacadeMethod')) {
    require_once $festiSdkPath.'exceptions/UnsupportableFacadeMethod.php';
}
if (!class_exists('FacadeException')) {
    require_once $festiSdkPath.'exceptions/FacadeException.php';
}

if (!class_exists("WooCommerceFacade")) {
    require_once  $woocommerceDir.'WooCommerceFacade.php';
}

if (!class_exists('EnvatoUtil')) {
    require_once $envatoDir.'EnvatoUtil.php';
}

if (!interface_exists('IConnectionUrlFacade')) {
    require_once $envatoDir.'IConnectionUrlFacade.php';
}

if (!class_exists('AbstractConnectionUrl')) {
    require_once $envatoDir.'AbstractConnectionUrl.php';
}

if (!class_exists('ConnectionUrlFacade')) {
    require_once $envatoDir.'ConnectionUrlFacade.php';
}

if (!class_exists('EnvatoApiServerNotFound')) {
    require_once $festiSdkPath.'exceptions/envato/EnvatoApiServerNotFound.php';
}

if (!class_exists('EnvatoApiServerNotFound')) {
    require_once $festiSdkPath.'exceptions/envato/EnvatoException.php';
}

if (!class_exists('ConnectionLibraryNotFound')) {
    require_once $festiSdkPath.'exceptions/envato/'.
                 'ConnectionLibraryNotFound.php';
}

if (!class_exists("WordpressFacade")) {
    require_once $festiSdkPath.'wordpress/WordpressFacade.php';
}