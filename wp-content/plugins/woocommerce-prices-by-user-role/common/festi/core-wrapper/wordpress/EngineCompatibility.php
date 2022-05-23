<?php

require_once __DIR__.'/WpUserBridge.php';

class EngineCompatibility implements IEngineCompatibility
{
    private $_isBindMode = false;

    public function __construct()
    {
        $this->_initCore();

        $facade = WordpressFacade::getInstance();

        $facade->addActionListener(
            'wp_logout',
            array($this, 'onSessionComplete')
        );
        $facade->addActionListener('wp_login', array($this, 'onSessionStart'));
        $facade->addActionListener('wp_loaded', array($this, 'onWpLoaded'));
    } // end __construct

    public function onWpLoaded()
    {
        $core = Core::getInstance();

        $core->user = new WpUserBridge();

        if ($this->_isModulesSettingsPage()) {
            $this->_doIncludeStaticFiles($core);
        }
    } // end onWpLoaded
    
    private function _initCore()
    {
        $coreRelativePath = plugin_basename(FESTI_CORE_PATH);
        $url = trailingslashit(WP_PLUGIN_URL.DIRECTORY_SEPARATOR);
        $themeRelativePath = '/tools/compatibility/wordpress/themes/default/';
        $coreUrl = $url.$coreRelativePath;
        $themeUrl = $coreUrl.$themeRelativePath;
        $themePath = FESTI_CORE_PATH.$themeRelativePath.'templates/';

        $options = array(
            'engine_path'          => FESTI_CORE_PATH.DIRECTORY_SEPARATOR,
            'engine_url'           => $coreUrl.'/public/',
            'theme_url'            => $themeUrl,
            'theme_template_path'  => $themePath,
            'filter_template_path' => FESTI_CORE_PATH.'/templates/',
        );

        $core = Core::getInstance($options);
        $core->db = DataAccessObject::factory($GLOBALS['wpdb']);

        $core->addEventListener(
            Store::EVENT_STORE_INIT,
            array(&$this, 'onInitStore')
        );

        $core->addEventListener(
            Core::EVENT_GET_URL,
            array(&$this, 'onGetUrl')
        );

    } // end _initCore

    private function _doIncludeStaticFiles(&$core)
    {
        $engineUrl = $core->getOption('engine_url');
        $themeUrl = $core->getOption('theme_url');

        $facade = WordPressFacade::getInstance();

        $facade->doEnqueueScript('jquery-ui', $engineUrl.'js/ui/jquery-ui.min.js', array('jquery'));
        $facade->doEnqueueScript('jimbo', $engineUrl.'js/jimbo.js', array('jquery'));
        $facade->doEnqueueScript('bootstrap', $engineUrl.'bootstrap/js/bootstrap.min.js');
        $facade->doEnqueueScript('jimbo-wp', $themeUrl.'../../js/jimbo-wp.js', array('jimbo'));
        $facade->doEnqueueScript(
            'jimbo-notification',
            $engineUrl.'js/notification/SmartNotification.js',
            array('jquery')
        );
        $facade->doEnqueueScript('festi-inputmask', $engineUrl.'js/jquery.inputmask.bundle.js');

        $facade->doEnqueueStyle('jimbo', $themeUrl.'../../css/style.css');
        $facade->doEnqueueStyle('jimbo-db', $themeUrl.'css/db.css');
        $facade->doEnqueueStyle('bootstrap', $themeUrl.'css/bootstrap.css');
        $facade->doEnqueueStyle('jquery-ui', $engineUrl.'js/ui/css/smoothness/jquery-ui.css');
        $facade->doEnqueueStyle('jimbo-notification', $engineUrl.'js/notification/notifications.css');
        $facade->doEnqueueStyle('font-awesome', $themeUrl.'css/font-awesome.min.css');
    } // end _doIncludeStaticFiles
    
    public function onInitStore(&$event)
    {
        $store = &$event->target;

        $name = $store->getName();
        if ($name == "festi_plugins") {
            $this->_preparePluginsStore($store);
        }

        $url = $_SERVER['REQUEST_URI'];
    
        if ($this->_isBindMode && !empty($_REQUEST['url'])) {
            $url = $_REQUEST['url'];
        }
        
        $store->setOption('current_url', $url);
        
        $store->addEventListener(
            Store::EVENT_ON_RESPONSE, 
            function (&$event) use ($store) {
        
                $response = &$event->target['response'];
            
                $url = $response->url;
            
                $path = parse_url($url, PHP_URL_PATH);
                $query = parse_url($url, PHP_URL_QUERY);
                parse_str($query, $request);
            
                $ident = $store->getIdent();
                unset($request[$ident], $request['popup']);
            
                $url = $path.'?'.http_build_query($request);
                $response->url = $url;
            }
        );
        
        $core = Core::getInstance();
        
        $core->addEventListener(
            Core::EVENT_ON_REDIRECT,
            function (&$event) use ($store) {
                $url = $event->target['url'];
                
                $path = parse_url($url, PHP_URL_PATH);
                $query = parse_url($url, PHP_URL_QUERY);
                parse_str($query, $request);
            
                $ident = $store->getIdent();
                unset($request[$ident]);
                
                $url = $path.'?'.http_build_query($request);
                echo "<script>window.location = '".$url."';</script>";
                exit();
            }
        );
    } // end onInitStore

    private function _preparePluginsStore(&$store)
    {
        $search = &$store->getModel()->getSearch();

        $search['ident&NOT IN'] = array(
            'Jimbo'
        );
    } // end _preparePluginStore

    public function onSessionStart()
    {
    } // end onSessionStart

    public function onSessionComplete()
    {
        session_destroy();
    } // end onSessionComplete
    
    public function bind($options = array())
    {
        $this->_isBindMode = true;
        
        $pluginName = "Jimbo";
        if (!empty($options['system_plugin'])) {
            $pluginName = $options['system_plugin'];
        }
        
        $core = Core::getInstance();
        $systemPlugin = $core->getPluginInstance($pluginName);
        
        $core->setSystemPlugin($systemPlugin->getInstance());
        
        $url = '/';
        if (isset($_GET['url'])) {
            $url = $_GET['url'];
        }
        
        $options = array(
            'url' => $url,
            'area' => 'backend'
        );

        $systemPlugin->bindRequest($options);
    } // end bind 
    
    public function onGetUrl(&$event)
    {
        $baseUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        parse_str($query, $request);
        
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $baseUrl = parse_url(get_admin_url(), PHP_URL_PATH);
        }
        
        $newUrl = $event->target['url'];
        $baseNewUrl = parse_url($newUrl, PHP_URL_PATH);
        $newQuery = parse_url($newUrl, PHP_URL_QUERY);
        parse_str($newQuery, $newRequest);
        
        $request += $newRequest;
        $request['url'] = $baseNewUrl;
        
        $url = $baseUrl.'?'.http_build_query($request);
        
        $event->target['url'] = $url;
    } // end onGetUrl
    
    public function install($options = array())
    {
        $core = Core::getInstance();
        
        $core->db = DataAccessObject::factory($GLOBALS['wpdb']);
        
        try {
            $core->getSystemPlugin();
        } catch (SystemException $exp) {
            $pluginName = "Jimbo";
            if (!empty($options['system_plugin'])) {
                $pluginName = $options['system_plugin'];
            }
            
            $systemPlugin = $core->getPluginInstance($pluginName);
            $core->setSystemPlugin($systemPlugin);
        }
        
        require_once 'workbench/Workbench.php';
        
        $core->db->begin();
        
        $options['skip_annotations'] = true;
        $options['dump_type'] = 'wordpress';
        
        Workbench::install($options);
        
        $core->db->commit();
        
        return true;
    } // end install

    private function _isModulesSettingsPage()
    {
        return array_key_exists('tab', $_REQUEST) &&
               $_REQUEST['tab'] == 'modules';
    } // end _isModulesSettingsPage
}