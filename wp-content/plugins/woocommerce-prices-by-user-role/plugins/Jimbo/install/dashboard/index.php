<?php
try {
    require_once __DIR__.'/config.php';

    $options = array(
       'convert_path'        => '/usr/bin/convert',
       'theme_name'          => 'default',
       'engine_folder'       => 'core',
       'plugins_folder'      => '%plugins_folder%',
       'theme_template_path' => FS_ROOT.'themes/default/templates/'
    );
    
    /**
     * @global Core $GLOBALS['core']
     * @name $core
     */
    $core = Core::getInstance($options);
    
    $core->config = $GLOBALS['config'];
    
    $core->db = DataAccessObject::factory($db);
    
    $core->user = new DefaultUser($GLOBALS['_sessionData']);

    $systemPlugin = $core->getPluginInstance('Jimbo');
    
    $core->setSystemPlugin($systemPlugin);

    $options = array(
        'onBind' => array($systemPlugin, 'onBind'),
        'area' => 'backend'
    );
    
    $systemPlugin->bindRequest($options);

} catch (Exception $exp) {
    echo $exp->getMessage();
}