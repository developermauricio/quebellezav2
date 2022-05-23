<?php

try {
    require_once __DIR__.DIRECTORY_SEPARATOR.'config.php';

    $options = array(
        'plugins_folder'      => '%plugins_folder%',
        'plugins_http'        => '/plugins/',
        'http_base'           => $GLOBALS['config']['http_base'],
        'theme_name'          => 'default',
        'theme_template_path' => FS_ROOT.'themes/default/templates/'
    );

    $core = Core::getInstance($options);

    $core->config = $GLOBALS['config'];

    $core->user = new DefaultUser($GLOBALS['_sessionData']);

    $core->db = DataAccessObject::factory($db);

    $systemPlugin = $core->getPluginInstance('%system_plugin%');

    $core->setSystemPlugin($systemPlugin);
     
    $options = array(
        'area' => 'default'
    );

    $isFoundUrl = $systemPlugin->bindRequest($options);
    
    if (!$isFoundUrl) {
        header("HTTP/1.1 404 Not Found");
        throw new NotFoundException();
    }

} catch (PermissionsException $premExp) {
    header("HTTP/1.1 403 Forbidden");

    $contactsPlugin = $core->getPluginInstance('Contents');
    $content = $contactsPlugin->fetch('403.phtml');
    $contactsPlugin->display($content);

    exit(1);
} catch (NotFoundException $notFoundExp) {
    header("HTTP/1.1 404 Not Found");

    $contactsPlugin = $core->getPluginInstance('Contents');
    $content = $contactsPlugin->fetch('500.phtml');
    $contactsPlugin->display($content);

    exit(1);

} catch (Exception $exp) {
    header("HTTP/1.1 500 Internal Server Error");

    if (is_null($core)) {
        echo "Error: ".$exp->getMessage();
        exit(1);
    }

    $contactsPlugin = $core->getPluginInstance('Contents');
    $content = $contactsPlugin->fetch('500.phtml');
    $contactsPlugin->display($content);
    exit(1);
}
