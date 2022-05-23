<?php
// @codingStandardsIgnoreStart
/**
 * Plugin Name: WooCommerce Prices By User Role
 * Plugin URI: https://plugiton.com/plugins/woocommerce-prices-by-user-role/
 * Description:  With this plugin  for WooCommerce  Products can be offered different prices for each customer group. Also you can do only product catalog without prices and show custom notification instead price.
 * Version: 5.0.4
 * Author: Plugiton
 * Author URI: https://plugiton.com/
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: festi_user_role_prices
 * Domain Path: /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 4.2
 * Copyright 2014  Plugiton  https://plugiton.com/
 */
// @codingStandardsIgnoreEnd

try {
    $pluginPath = __DIR__.DIRECTORY_SEPARATOR;
    $commonPath = $pluginPath.'common'.DIRECTORY_SEPARATOR;
    $festiSdkPath = $commonPath.'festi'.DIRECTORY_SEPARATOR;
    
    require_once $pluginPath.'config.php';
    require_once $pluginPath.'autoload.php';

    if (!class_exists('WooUserRolePricesUtils')) {
        require_once $pluginPath.'WooUserRolePricesUtils.php';
    }
    
    WooUserRolePricesUtils::doCheckPhpVersion(
        PRICE_BY_ROLE_MIN_PHP_VERSION
    );
    
    require_once $festiSdkPath.'autoload.php';
    
    if (!class_exists('WordpressDispatchFacade')) {
        require_once $commonPath.'WordpressDispatchFacade.php';
    }
    
    require_once $festiSdkPath.'core-wrapper/FestiCoreStandalone.php';

    if (!class_exists('IUserRolePriceFacade')) {
        $path = 'facade/IUserRolePriceFacade.php';
        require_once $commonPath.$path;
    }
    
    if (!class_exists('UserRolePriceFacade')) {
        $path = 'facade/UserRolePriceFacade.php';
        require_once $commonPath.$path;
    }
    
    if (!class_exists('WpmlCompatibleFestiPlugin')) {
        $path = 'wpml/WpmlCompatibleFestiPlugin.php';
        require_once $commonPath.$path;
    }
    
    if (!class_exists('FestiWpmlManager')) {
        require_once $commonPath.'wpml/FestiWpmlManager.php';
    }
    
    if (!class_exists('StringManagerWooUserRolePrices')) {
        require_once $pluginPath.'StringManagerWooUserRolePrices.php';
    }

    if (!class_exists("FestiWooCommerceProduct")) {
        $path = '/common/festi/woocommerce/product/FestiWooCommerceProduct.php';
        require_once __DIR__.$path;
    }

    if (!class_exists('WooUserRolePricesFestiPlugin')) {
        require_once $pluginPath.'WooUserRolePricesFestiPlugin.php';
    }

    require_once $pluginPath.'functions.php';
    require_once $commonPath.'WooUserRolePricesApiFacade.php';

    $className = 'wooUserRolePricesFestiPlugin';
    $GLOBALS[$className] = new WooUserRolePricesFestiPlugin(__FILE__);

    FestiCoreStandalone::init();
} catch (Exception $e) {
    if (FestiCoreStandalone::isInit()) {
        WooUserRolePricesUtils::displayPluginError($e->getMessage());
    } else {
        throw $e;
    }
}