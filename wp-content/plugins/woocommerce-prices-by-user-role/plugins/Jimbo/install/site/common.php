<?php
ini_set(
    'include_path', 
    '.'.PATH_SEPARATOR.
    FS_ROOT.PATH_SEPARATOR.
    FS_ROOT.'libs'.PATH_SEPARATOR.
    FS_ROOT.'core'.PATH_SEPARATOR.
    FS_ROOT.'core/bundle'.PATH_SEPARATOR
);

require_once FS_ROOT.'core/bundle/database/DataAccessObject.php';
require_once FS_ROOT.'core/bundle/Core.php';
require_once FS_ROOT.'core/bundle/util/ValuesObject.php';

$db = new PDO(
    $GLOBALS['config']['db']['dsn'],
    $GLOBALS['config']['db']['user'],
    $GLOBALS['config']['db']['pass']
);

if (!empty($_REQUEST['ssids'])) {
    session_id($_REQUEST['ssids']);
}

if (php_sapi_name() != 'cli') {
    session_start();
    $GLOBALS['_sessionData'] = &$_SESSION[AUTH_DATA][AUTH_TOKEN];
}