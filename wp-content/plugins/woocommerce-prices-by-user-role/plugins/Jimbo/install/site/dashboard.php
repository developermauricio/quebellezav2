<?php

try {
    require_once __DIR__.DIRECTORY_SEPARATOR.'config.php';

    $options = array(
        'plugins_folder'      => '%plugins_folder%',
        'plugins_http'        => '/plugins/',
        'http_base'           => $GLOBALS['config']['dashboard_http_base'],
        'theme_name'          => 'dashboard',
        'theme_template_path' => FS_ROOT.'themes/dashboard/templates/'
    );

    $core = Core::getInstance($options);

    $core->config = $GLOBALS['config'];

    $core->user = new DefaultUser($GLOBALS['_sessionData']);

    $core->db = DataAccessObject::factory($db);

    $systemPlugin = $core->getPluginInstance('%system_plugin%');

    $core->setSystemPlugin($systemPlugin);
     
    $options = array(
        'area' => 'backend',
        'onBind' => array($systemPlugin, 'onBind'),
    );
    
    $isFoundUrl = $systemPlugin->bindRequest($options);
    
    if (!$isFoundUrl) {
        throw new NotFoundException();
    }

} catch(PermissionsException $premExp) {
    echo "<pre>";
    die($premExp->getMessage());
} catch(DatabaseException $dbExp) {
    echo "<pre>";
    die($dbExp->getMessage());
} catch(NotFoundException $exp) {
    echo "<pre>";
    die('not found');
} catch(Exception $exp) {
    echo "<pre>";
    die($exp->getMessage());
}