<?php

define('FS_ROOT', __DIR__.DIRECTORY_SEPARATOR);

define('AUTH_DATA', 'dbadmin');
define('AUTH_TOKEN', 'zero');

$GLOBALS['config'] = array();

$GLOBALS['config']['http_base'] = '/';
$GLOBALS['config']['dashboard_http_base'] = '/%dashboard_base_http%';

$GLOBALS['config']['paths'] = array(
    'plugins'   => FS_ROOT.'plugins'.DIRECTORY_SEPARATOR,
    'logs'      => FS_ROOT.'logs'.DIRECTORY_SEPARATOR
);

$GLOBALS['config']['http_paths'] = array();

$GLOBALS['config']['db']['dsn'] = 'mysql:dbname=dbname;host=localhost;port=3306;';
$GLOBALS['config']['db']['name'] = 'dbname';
$GLOBALS['config']['db']['user'] = 'user';
$GLOBALS['config']['db']['pass'] = 'password';

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('%timezone%');

$localConfigPath = FS_ROOT.'local.php';
if (file_exists($localConfigPath)) {
    require $localConfigPath;
}

ini_set('error_log', $GLOBALS['config']['paths']['logs'].'php.log');

include_once FS_ROOT.'common.php';