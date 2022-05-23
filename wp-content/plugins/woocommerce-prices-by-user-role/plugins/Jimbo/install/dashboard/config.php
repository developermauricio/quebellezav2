<?php

/**
 * Root path to site directory
 */
define('FS_ROOT', __DIR__.DIRECTORY_SEPARATOR);
define('FS_LIBS', FS_ROOT.'libs'.DIRECTORY_SEPARATOR);

define('AUTH_DATA', 'dbadmin');
define('AUTH_TOKEN', 'zero');

/**
 * This is key in get params for defined redirect with restore data from session
 */
define('REDIRECT_POINT_KEY_IN_REQUEST', 'sh');

$storagePath = 'static'.DIRECTORY_SEPARATOR.'storage';

if (empty($GLOBALS['config']['storage_path'])) {
    $GLOBALS['config']['storage_path'] = FS_ROOT.$storagePath;
}

$GLOBALS['config']['storage_url'] = DIRECTORY_SEPARATOR.$storagePath;

$GLOBALS['config']['paths'] = array(
    'plugins' => FS_ROOT.'plugins'.DIRECTORY_SEPARATOR,
    'objects' => FS_ROOT.'objects'.DIRECTORY_SEPARATOR,
    'logs'    => FS_ROOT.'logs'.DIRECTORY_SEPARATOR,
);

$GLOBALS['config']['http_paths'] = array();

$GLOBALS['config']['db']['dsn'] = 'mysql:dbname=dbname;host=localhost;port=3306;';
$GLOBALS['config']['db']['name'] = 'dbname';
$GLOBALS['config']['db']['user'] = 'user';
$GLOBALS['config']['db']['pass'] = 'password';

$GLOBALS['pluginRules'] = array();

// Hooks
$GLOBALS['config']['hooks'] = array();

date_default_timezone_set('%timezone%');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$localConfigPath = FS_ROOT.'local.php';

if (file_exists($localConfigPath)) {
    require $localConfigPath;
}

ini_set('error_log', $GLOBALS['config']['paths']['logs'].'php.log');

include_once FS_ROOT.'common.php';