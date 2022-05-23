<?php
define('PRICE_BY_ROLE_VERSION', '5.0.2');

define('ENGINE_PATH', __DIR__.DIRECTORY_SEPARATOR);

/* price range type */
define('PRICE_BY_ROLE_MAX_PRICE_RANGE_TYPE', 'max');
define('PRICE_BY_ROLE_MIN_PRICE_RANGE_TYPE', 'min');

/* Variation Price Hash Generator */
define('PRICE_BY_ROLE_HASH_GENERATOR_KEY', 'price-by-role');

define(
    'PRICE_BY_ROLE_HASH_GENERATOR_VALUE_FOR_UNREGISTRED_USER',
    'not_registered_user'
);

/* options names */
define('PRICE_BY_ROLE_VARIATION_RICE_KEY', 'festiVariableUserRolePrices');
define('PRICE_BY_ROLE_HIDDEN_RICE_META_KEY', 'festiUserRoleHidenPrices');
define('PRICE_BY_ROLE_PRICE_META_KEY', 'festiUserRolePrices');
define('PRICE_BY_ROLE_IGNORE_DISCOUNT_META_KEY', 'festiUserRoleIgnoreDiscount');
define(
    'PRICE_BY_ROLE_IGNORE_HIDE_ALL_PRODUCT_OPTION_META_KEY',
    'festiUserRoleIgnoreHiddenAllProducts'
);

define('PRICE_BY_ROLE_TAX_DISPLAY_OPTIONS', 'festiUserRoleTaxDisplayOptions');

/* translate */
define('PRICE_BY_ROLE_WPML_KEY', 'WooUserRolePrices');
define('PRICE_BY_ROLE_LANGUAGE_DOMAIN', 'festi_user_role_prices');

/* Options */
define('PRICE_BY_ROLE_OPTIONS_PREFIX', 'festi_user_role_prices_');

/* Actions */
define('FESTI_ACTION_REGISTER_STATIC_STRING', 'festi_plugin_register_string');

if (!defined('FESTI_ACTION_UPDATE_OPTIONS')) {
    define('FESTI_ACTION_UPDATE_OPTIONS', 'festi_plugin_update_options');
}

/* Filters */
define('FESTI_FILTER_GET_STATIC_STRING', 'festi_plugin_get_static_string');

if (!defined('FESTI_FILTER_GET_OPTIONS')) {
    define('FESTI_FILTER_GET_OPTIONS', 'festi_plugin_get_options');
}

/* products */
define('PRICE_BY_ROLE_PRODUCT_MINIMAL_PRICE', 0);
define('PRICE_BY_ROLE_PERCENT_DISCOUNT_TYPE', 0);
define('PRICE_BY_ROLE_MONEY_DISCOUNT_TYPE', 1);

define('PRICE_BY_ROLE_DISCOUNT_TYPE_ROLE_PRICE', 'role');

/* Settings Page */
define('PRICE_BY_ROLE_SETTINGS_PAGE_SLUG', 'generalTab');
define('PRICE_BY_ROLE_WOOCOMMERCE_SETTINGS_PAGE_SLUG', 'woocommerce');

define('PRICE_BY_ROLE_EXCEPTION_EMPTY_VALUE', 6000);
define('PRICE_BY_ROLE_EXCEPTION_INVALID_VALUE', 6001);

define('PRICE_BY_ROLE_PLUGIN_DIR', __DIR__);
define(
    'PRICE_BY_ROLE_EXTENSIONS_DIR',
    PRICE_BY_ROLE_PLUGIN_DIR.DIRECTORY_SEPARATOR.
    'plugins'.DIRECTORY_SEPARATOR
);

define('PRICE_BY_ROLE_MIN_PHP_VERSION', '5.3.0');
define('PRICE_BY_ROLE_EXCEPTION_INVALID_PHP_VERSION', 6002);
define('PRICE_BY_ROLE_EXCEPTION_MESSAGE', 'festiExceptionMessage');

define('PRICE_BY_ROLE_HIDDEN_PRODUCT_META_KEY', 'festiHideProductForUserRoles');
define('PRICE_BY_ROLE_HIDDEN_PRODUCT_OPTIONS', 'hideProductForUserRoles');
define('PRICE_BY_ROLE_CATEGORY_VISIBILITY_OPTIONS', 'hideCategoryForUserRoles');

define('PRICE_BY_ROLE_EXCEPTION_WMPL_CURRENCY', 6003);

define('PRICE_BY_ROLE_TAXONOMY_CUSTOM_FIELD', 'custom-field-attribute');

define('PRICE_BY_ROLE_PLUGIN_ID', 3);

define('PRICE_BY_ROLE_TYPE_PRODUCT_REGULAR_PRICE', 'regular');
define('PRICE_BY_ROLE_TYPE_PRODUCT_SALE_PRICE', 'sale');

define('FESTI_TEAM_API_URL', 'https://api.festi.team');

define('PRICE_BY_ROLE_PLUGIN_NAME', 'Prices by User Role');

// Festi Framework
define('FESTI_CORE_PATH', $festiSdkPath.'core');
define('ENGINE_BASE_PATH', __DIR__.DIRECTORY_SEPARATOR);

define('FESTI_IMPORT_PLUGIN_PATH', 'plugins/WooImportProducts');