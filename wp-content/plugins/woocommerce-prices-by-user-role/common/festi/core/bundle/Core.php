<?php

require_once 'bundle/exception/SystemException.php';
require_once 'bundle/exception/FieldException.php';
require_once 'bundle/exception/PermissionsException.php';
require_once 'bundle/exception/NotFoundException.php';
require_once 'bundle/exception/ApiException.php';

require_once 'bundle/event/EventDispatcher.php';

require_once 'bundle/Entity.php';
require_once 'bundle/Display.php';
require_once 'bundle/Response.php';

require_once 'bundle/store/Store.php';

require_once 'bundle/plugin/ISystemPlugin.php';
require_once 'bundle/plugin/AbstractPlugin.php';
require_once 'bundle/plugin/IDataAccessObjectPlugin.php';
require_once 'bundle/plugin/ObjectPlugin.php';
require_once 'bundle/plugin/DisplayPlugin.php';

require_once 'bundle/DefaultUser.php';

require_once 'bundle/locale/LocaleModel.php';
require_once 'bundle/locale/MoDictionaryLocale.php';

require_once 'bundle/util/PluginWrapper.php';
require_once 'bundle/util/Permissions.php';
require_once 'bundle/util/FestiUtils.php';


/**
 * @package Festi
 *
 * @property DefaultUser $user
 * @property array $seo
 */
class Core extends Entity
{
    const EVENT_PLUGIN_INIT = "plugin_init";
    const EVENT_PLUGIN_CALL = "plugin_call";
    const EVENT_GET_URL     = "get_url";
    const EVENT_ON_RESPONSE = "response";
    const EVENT_ON_REQUEST  = "request";
    const EVENT_ON_REDIRECT = "redirect";
    
    const EVENT_THEME_TITLE = "theme_title";
    
    const EVENT_ON_CREATE_STORE = "create_store";
    
    const OPTION_PLUGINS_PATH      = "plugins_path";
    const OPTION_SSL_PUBLIC_KEY    = "ssl_public_key";
    const OPTION_SSL_PRIVATE_KEY   = "ssl_private_key";
    const OPTION_SESSION_TOKENS    = "tokens";
    const OPTION_LOCALE_PATH       = "locale_path";
    const OPTION_HTTP_BASE         = "http_base";
    const OPTION_THEME_NAME        = "theme_name";
    const OPTION_ENGINE_FOLDER     = "engine_folder";
    const OPTION_PLUGINS_FOLDER    = "plugins_folder";
    const OPTION_SESSION_DATA      = "session_data";
    const OPTION_SESSION_TOKEN_KEY = "session_token_key";
    const OPTION_THEME_URL         = "theme_url";
    const OPTION_PLUGINS_HTTP      = "plugins_http";
    const OPTION_CLASS_POSTFIX     = "classPostfix";
    const OPTION_KEY_DEFAULT       = "default";
    const OPTION_CONNECTION        = "connection";
    const OPTION_ENGINE_PATH       = "engine_path";

    const OPTION_GLOBAL_STORE_EXCEPTION_MODE = "storeExceptionMode";
    
    const SERVER_REQUEST_URI = "REQUEST_URI";
    
    const FOLDER_LOCALE = "locale/";
    const FOLDER_THEMES = "themes/";

    static private $_instance = null;
    static private $_plugins = null;
    static private $_template;

    private $_options;
    private $_store = array();
    private $_hooks = array();
    private $_systemPlugin;

    protected $properties = array();
    protected $locale;

    /**
     * @var ObjectAdapter
     */
    public $db;
    
    /**
     * @param array $options
     * @return Core
     * @throws SystemException
     */
    public static function &getInstance(array $options = array()): Core
    {
        if (self::$_instance == null) {
            self::$_instance = new self($options);
            self::$_instance->_onInit();
        }

        return self::$_instance;
    } // end getInstance
    
    /**
     * Core constructor.
     * @param array $options
     * @throws SystemException
     */
    public function __construct(array $options = array())
    {
        if (isset(self::$_instance)) {
            throw new SystemException(
                'Core already defined use Core::getInstance'
            );
        }

        $this->_verifySystem();

        parent::__construct();

        if (array_key_exists(static::OPTION_CONNECTION, $options)) {
            $this->db = $options[static::OPTION_CONNECTION];
            unset($options[static::OPTION_CONNECTION]);
        }

        $this->_options = $options;
        
        $this->_setDefaultOptions();

        $this->onInitLocale();
    } // end __construct
    
    private function _verifySystem()
    {
        $originalVersion = phpversion();
        $version = array_map('intval', explode('.', $originalVersion));

        $currentVersion = $version[0] * 10000 + $version[1] * 100 + $version[2];
        $minimalPhpVersion = 70100;

        if ($currentVersion < $minimalPhpVersion) {
            throw new SystemException("The PHP ".$originalVersion." version is unsupportable.");
        }
    } // end _verifySystem
    
    private function _onInit()
    {
        $this->_onInitPlugins();
    } // end _onInit
    
    /**
     * @return bool
     */
    private function _onInitPlugins(): bool
    {
        $pluginsPath = $this->getOption(static::OPTION_PLUGINS_PATH);
        
        $globPattern = $pluginsPath.'*/init.php';
        
        $initFiles = glob($globPattern);
        if (!$initFiles) {
            return true;
        }
        
        foreach ($initFiles as $path) {
            require_once $path;
        }
        
        return true;
    } // end _onInitPlugins
    
    public function onInitLocale()
    {
       $this->locale = new LocaleModel();

        // System locale
        $localePath = $this->getOption(static::OPTION_ENGINE_PATH);
        $moFilePath =  $localePath.static::FOLDER_LOCALE.$this->getOption('lang').".mo";

        if (file_exists($moFilePath)) {
            $systemDictionary = new MoDictionaryLocale($moFilePath);
            $this->locale->addDictionary($systemDictionary);
        }

        $path = $this->getOption(static::OPTION_LOCALE_PATH);
        $moFilePath = $path.$this->getOption('lang').".mo";
        if (file_exists($moFilePath)) {
            $systemDictionary = new MoDictionaryLocale($moFilePath);
            $this->locale->addDictionary($systemDictionary);
        }

    } // end onInitLocale
    
    private function _setDefaultOptions()
    {
        if (!isset($this->_options[static::OPTION_HTTP_BASE])) {
            $this->_options[static::OPTION_HTTP_BASE] = '/';
        }
        
        if (!isset($this->_options[static::OPTION_THEME_NAME])) {
            $this->_options[static::OPTION_THEME_NAME] = static::OPTION_KEY_DEFAULT;
        }
        $themeName = $this->_options[static::OPTION_THEME_NAME];
        
        if (!isset($this->_options[static::OPTION_ENGINE_FOLDER])) {
            $this->_options[static::OPTION_ENGINE_FOLDER] = 'core';
        }
        
        if (!isset($this->_options['core_path'])) {
            $this->_options['core_path'] = __DIR__.DIRECTORY_SEPARATOR;
        }

        if (!isset($this->_options[static::OPTION_PLUGINS_FOLDER])) {
            $this->_options[static::OPTION_PLUGINS_FOLDER] = 'plugins';
        }
        $engineFolder = $this->_options[static::OPTION_ENGINE_FOLDER];
        
        if (!isset($this->_options[static::OPTION_SESSION_DATA]) && session_id()) {
            $this->_options[static::OPTION_SESSION_DATA] = &$_SESSION['festi'];
        }
        
        if (empty($this->_options['path'])) {
            if (defined('ENGINE_BASE_PATH')) {
                $path = ENGINE_BASE_PATH;
            } else {
                $path = realpath(__DIR__.'/../../').DIRECTORY_SEPARATOR;
            }
            
            $this->_options['path'] = $path;
        } else {
            $path = $this->_options['path'];
        }
        
        $engineHttpPath = $this->_options[static::OPTION_HTTP_BASE].$engineFolder;
        
        $optionsFields = array(
            static::OPTION_SESSION_TOKEN_KEY => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => 'stoken'
            ),

            static::OPTION_ENGINE_PATH => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.$engineFolder.DIRECTORY_SEPARATOR
            ),
            
            'theme_path' => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.static::FOLDER_THEMES.$themeName.'/'
            ),
            
            'theme_template_path' => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.static::FOLDER_THEMES.$themeName.'/templates/'
            ),
            
            'filter_template_path' => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.$engineFolder.'/templates/'
            ),
            
            static::OPTION_THEME_URL => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $this->_options[static::OPTION_HTTP_BASE].static::FOLDER_THEMES.
                             $themeName.'/'
            ),
            
            'system_path'=> array(
                'type'    => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => realpath(dirname(__FILE__).'/../../').
                             DIRECTORY_SEPARATOR
            ),
            
            'engine_url'=> array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $engineHttpPath.'/public/'
            ),
            
            'imagemagic_path'=> array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => '/usr/bin/convert'
            ),
            
            'charset'=> array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => 'UTF-8'
            ),
            
            'lang'=> array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => 'en'
            ),
            
            static::OPTION_PLUGINS_PATH => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.$this->_options[static::OPTION_PLUGINS_FOLDER].
                             DIRECTORY_SEPARATOR
            ),
            
            static::OPTION_PLUGINS_HTTP=> array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => '/'.$this->_options[static::OPTION_PLUGINS_FOLDER].'/'
            ),
            
            'template_path' => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.'templates'.DIRECTORY_SEPARATOR
            ),
            
            'defs_path'=> array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.'tblDefs/'
            ),
            
            'objects_path'=> array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.'objects/'
            ),
            
            static::OPTION_LOCALE_PATH => array(
                'type' => self::FIELD_TYPE_STRING,
                static::OPTION_KEY_DEFAULT => $path.static::FOLDER_LOCALE
            ),
            
            'plugin_template_postfix' => self::FIELD_TYPE_STRING
        );

        $this->_options = $this->getExtendData($this->_options, $optionsFields);
        
        if (!defined('FESTI_PLUGINS_PATH')) {
            define(
                'FESTI_PLUGINS_PATH',
                $this->_options[static::OPTION_PLUGINS_PATH]
            );
        }
    } // end _setDefaultOptions

    public function __set($label, $object)
    {
        if (!isset($this->_store[$label])) {
            $this->_store[$label] = $object;
        }
    }

    public function __unset($label)
    {
        if (isset($this->_store[$label])) {
            unset($this->_store[$label]);
        }
    }

    public function __get($label)
    {
        if (isset($this->_store[$label])) {
            return $this->_store[$label];
        }
        return false;
    }

    public function __isset($label)
    {
        return isset($this->_store[$label]);
    }
    
    
    /**
     * @param bool $prefix
     * @return string|null
     */
    public function getCurrentURL(bool $prefix = false): ?string
    {
        if (!$prefix) {
            $prefix = $this->getOption(static::OPTION_HTTP_BASE);
        }
        
        $uri = '/';
        if (array_key_exists(static::SERVER_REQUEST_URI, $_SERVER)) {
            $uri = $_SERVER[static::SERVER_REQUEST_URI];
        }
        
        $path = parse_url($uri, PHP_URL_PATH);

        $url =  empty($path) ? '/' : $path;

        return preg_replace('#^'.$prefix.'#Umis', '/', $url);
    } // end getCurrentURL
    
    /**
     * @param string $plugin
     * @param string|null $classPostfix
     * @return bool
     */
    public function isPluginInstanceExists(string $plugin, ?string $classPostfix = null): bool
    {
        if (!$classPostfix) {
            $classPostfix = 'Plugin';
        }
        
        return isset(self::$_plugins[$classPostfix]) && 
               isset(self::$_plugins[$classPostfix][$plugin]);    
    } // end isPluginInstanceExists
    
    /**
     * @param string $plugin
     * @param array $options
     * @return AbstractPlugin|ISystemPlugin
     * @throws SystemException
     */
    public function &getPluginInstance(
        string $plugin, array $options = array()
    )
    {
        $classPostfix = 'Plugin';
        if (array_key_exists(static::OPTION_CLASS_POSTFIX, $options)) {
            $classPostfix = $options[static::OPTION_CLASS_POSTFIX];
        }
        
        if ($this->isPluginInstanceExists($plugin, $classPostfix)) {
            return self::$_plugins[$classPostfix][$plugin];
        }

        $className = $plugin.$classPostfix;

        if (isset($options['path'])) {
            $path = $options['path'];
        } else if (isset($options[static::OPTION_PLUGINS_PATH])) {
            $path = $options[static::OPTION_PLUGINS_PATH];
        } else {
            $path = $this->getOption(static::OPTION_PLUGINS_PATH);
        }
        
        $options[static::OPTION_PLUGINS_PATH]  = $path;

        if (isset($options['http_path'])) {
            $httpPath = $options['http_path'];
        } else if (!empty($options[static::OPTION_PLUGINS_HTTP])) {
            $httpPath = $options[static::OPTION_PLUGINS_HTTP];
        } else {
            $httpPath = $this->getOption(static::OPTION_PLUGINS_HTTP);
        }
        
        $options[static::OPTION_PLUGINS_PATH] = $path;

        if (is_dir($path.$plugin)) {
            $path .= $plugin.DIRECTORY_SEPARATOR;
        }

        $options['plugin_path']  = $path;
        $options[static::OPTION_CLASS_POSTFIX] = $classPostfix;
        $options['name']         = $plugin;
        
        $options['plugin_http'] = $httpPath.$plugin."/";

        if (!class_exists($className)) {
            $this->_includePlugin($className, $path);
        }

        $this->_setPluginLocale($plugin, $path);

        // FIXME:
        if (!isset($options['tpl'])) {
            $tpl = self::getTemplateInstance();
        } else {
            $tpl = $options['tpl'];
        }

        $pluginInstance = new $className($tpl);
        $pluginInstance->setOptions($options);
        
        //
        $target = array(
            'plugin' => &$pluginInstance
        );
        
        $event = new FestiEvent(self::EVENT_PLUGIN_INIT, $target);
        $this->dispatchEvent($event);

        $pluginInstance->onInit();
        
        self::$_plugins[$classPostfix][$plugin] = $pluginInstance;

        return self::$_plugins[$classPostfix][$plugin];
    } // end getPluginInstance

    private function _includePlugin(string $className, string $path): void
    {
        $classFile = $path.$className.'.php';

        if (!file_exists($classFile)) {
            $msg = __l(
                'File "%s" for plugin "%s" was not found.',
                $classFile,
                $className
            );

            throw new SystemException($msg);
        }

        require_once $classFile;
        if (!class_exists($className)) {
            $msg = __l(
                'Class "%s" was not found in file "%s".',
                $className,
                $classFile
            );

            throw new SystemException($msg);
        }
    } // end _includePlugin
    
    /**
     * @param string $pluginName
     * @param string $path
     */
    private function _setPluginLocale(string $pluginName, string $path)
    {
        $defaultLocalePath = $this->getOption(static::OPTION_LOCALE_PATH);
        $localePaths = array(
            $defaultLocalePath,
            $path.static::FOLDER_LOCALE
        );
    
        foreach ($localePaths as $localePath) {
            $moFilePath = $localePath.$pluginName."_".
                $this->getOption("lang").".mo";
            if (file_exists($moFilePath)) {
                $pluginDictionary = new MoDictionaryLocale($moFilePath);
                $this->locale->addDictionary($pluginDictionary);
            }
        }
    }
    
    public static function &getTemplateInstance()
    {
        if (!is_null(self::$_template)) {
            return self::$_template;
        }

        $path = self::getInstance()->getOption('template_path');
        self::$_template = new Display($path);
        
        return self::$_template;
    } // end getTemplateInstance
    

    public function call(
        $plugin, $method, $params = array(), $options = array()
    )
    {
        $obj = $this->getPluginInstance($plugin, $options);

        if (!is_callable(array($obj, $method))) {
            $msg = __l(
                'Method "%s" was not found in module "%s".', 
                $method, 
                $plugin
            );
            
            throw new SystemException($msg);
        }

        return call_user_func_array(array($obj, $method), $params);
    } // end call
    
    /**
     * Returns content from engine templates folder
     *
     * @param string $tplName
     * @param array $localVars
     * @return string
     * @throws SystemException
     */
    public function systemFetch(string $tplName, array $localVars = array()): string
    {
        $path = $this->getOption('theme_template_path');
        
        $tpl = new Display($path);
        return $tpl->fetch($tplName, $localVars);
    } // end systemFetch

    /**
     * @deprecated
     */
    public function getView(
        &$connection, $table, $params = array(), Response &$response = null
    )
    {
        if (is_null($response)) {
            $response = new Response();
        }

        $store = $this->createStoreInstanceByConnection(
            $connection, 
            $table, 
            $params
        );

        $store->onRequest($response);

        return $response;
    } // end getView
    
    /**
     * Create and returns Store instance by system database connection. 
     * For override model need use $params['model'].
     *
     * @param string $storeName
     * @param mixed[] $params Array for store options.
     * @throws SystemException if undefined database connection in core.
     * @return Store
     */
    public function createStoreInstance(string $storeName, array $params = array()): Store
    {
        if (!$this->db) {
            throw new SystemException("Undefined connection.");
        }
        
        return $this->createStoreInstanceByConnection(
            $this->db, 
            $storeName, 
            $params
        );
    } // end createStoreInstance
    
    /**
     * @param IDataAccessObject $connection
     * @param string $table
     * @param array $params
     * @return Store
     * @throws PermissionsException
     * @throws StoreException
     * @throws SystemException
     */
    public function createStoreInstanceByConnection(
        IDataAccessObject &$connection, string $table, $params = array()
    ): Store
    {
        require_once 'bundle/store/Store.php';
        $keyModel = 'model';
        
        if (!empty($params[$keyModel])) {
            $this->_options[$keyModel] = $params[$keyModel];
            unset($params[$keyModel]);
        }
        
        $this->_options['handler_options'] = $params;
        $this->_options['current_url'] = $this->getCurrentURL();

        $store = new Store($connection, $table, $this->_options);
        
        unset($this->_options[$keyModel]);
        
        $target = array(
            'store' => &$store
        );
        
        $this->fireEvent(static::EVENT_ON_CREATE_STORE, $target);
        
        return $store;
    } // end createStoreInstanceByConnection
    
    /**
     * @param string $url
     * @param bool $usePrefix
     */
    public function redirect(string $url = '', bool $usePrefix = true)
    {
        $url = $usePrefix ? $this->getOption(static::OPTION_HTTP_BASE).$url : $url;
        
        $target = array(
            'url' => &$url
        );
        
        $this->fireEvent(static::EVENT_ON_REDIRECT, $target);
        
        header('Location: '.$url);
        exit();
    } // end redirect
    

    /**
     * Add template property title
     *
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->addProperties('title', $title, true);
    } // end setTitle
    
    /**
     * @param string $path
     * @param bool $theme
     */
    public function includeJs(string $path, bool $theme = true): void
    {
        if ($theme) {
            $path = $this->getOption(static::OPTION_THEME_URL).$path;
        }
        
        $this->addProperties('js', $path);
    }
    
    /**
     * @param string $path
     * @param bool $theme
     */
    public function includeCss(string $path, bool $theme = true): void
    {
        if ($theme) {
            $path = $this->getOption(static::OPTION_THEME_URL).$path;
        }
        
        $this->addProperties('css', $path);
    }

    public function addProperties($name, $value, $is_scalar = false)
    {
        if ($is_scalar) {
            $this->properties[$name] = $value;
            return true;
        }

        if (!isset($this->properties[$name])) {
            $this->properties[$name] = array();
        }

        if (in_array($value, $this->properties[$name])) {
            return true;
        }

        $this->properties[$name][] = $value;

        return true;
    } // end addProperties

    public function getProperties($key = false)
    {
        if (!$key) {
            return $this->properties;
        }
        
        return $this->properties[$key] ?? null;
    }
    
    /**
     * @param string $url
     * @return string
     */
    public function getBaseUrl(string $url): string
    {
        $params = array();
        
        // XXX: Add session token to url, need for correct session work
        $key = $this->getOption(static::OPTION_SESSION_TOKEN_KEY);
        if ($this->_isTokenNeeded($url, $key)) {
            $params[$key] = $_REQUEST[$key];
        }

        if (func_num_args() > 1) {
            $args = func_get_args();
            $chunks = array_slice($args, 1);
            $lastChunk = array_pop($chunks);
            
            if (!is_array($lastChunk)) {
                $chunks[] = $lastChunk;
            } else {
                $params += $lastChunk;
            }
            
            if ($chunks) {
                $url = vsprintf($url, $chunks);
            }
        }
        
        if ($params) {
            $seperator = strpos($url, '?') === false ? '?' : '&';
            $url .= $seperator.http_build_query($params);
        }

        $eventTarget = array(
            'url' => &$url
        );
        
        $event = new FestiEvent(Core::EVENT_GET_URL, $eventTarget);
        $this->dispatchEvent($event);

        return $url;
    } // end getBaseUrl
    
    /**
     * @param string|null $url
     * @return string
     */
    public function getUrl(?string $url): string
    {
        $args = func_get_args();
        
        if (!preg_match('#^(www|http)#Umis', $args[0])) {
            $args[0] = preg_replace(
                '#^/#Umis', 
                $this->getOption(static::OPTION_HTTP_BASE),
                $args[0]
            );
        }

        return call_user_func_array(array($this, 'getBaseUrl'), $args);
    } // end getUrl
    
    /**
     * @param string $url
     * @param string $key
     * @return bool
     */
    private function _isTokenNeeded(string $url, string $key): bool
    {
        return !preg_match('#^(www|http)#Umis', $url) &&
            !empty($_REQUEST[$key]) && strpos($url, $key) === false;
    } // end _isTokenNeeded
    
    
    /**
     * Returns valid fields values. All errors are written to the $errors.
     * 
     * @param mixed $needles
     * @param array $errors
     * @param array|bool $request
     * @return array
     */
    public function getRequestFields(
        $needles, &$errors = array(), $request = false
    )
    {
        if (!$request) {
            $request = &$_REQUEST;
        }
        
        return $this->getPreparedData($request, $needles, $errors);
    } // end getRequestFields

    /**
     * Returns a reference to data in the session used by the jimbo
     *
     * @return array|null
     */
    public function &getSessionData(): ?array
    {
        return $this->_options[static::OPTION_SESSION_DATA];
    } // end getSessionData

    public function setSessionData(&$sessionData)
    {
        $this->_options[static::OPTION_SESSION_DATA] = &$sessionData ;
    }
    
    public function setLocaleModel(LocaleModel $locale)
    {
        $this->locale = $locale;
    } // end setLocaleModel

    /**
     * @return LocaleModel
     */
    public function getLocaleModel()
    {
        return $this->locale;
    } //end getLocaleModel

    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;
    }
    
    /**
     * @param $name
     * @return mixed|null
     */
    public function getOption($name)
    {
        return $this->_options[$name] ?? null;
    }
    
    public function &getOptions()
    {
        return $this->_options;
    }
    
    /**
     * @param $objectName
     * @param string|bool $pluginName
     * @param string|null $path
     * @return IDataAccessObject
     * @phan-return mixed
     * @throws DatabaseException
     * @throws SystemException
     */
    public function &getObject(
        string $objectName, $pluginName = false, string $path = null
    )
    {
        if ($objectName === false) {
            throw new SystemException("Undefined Object Name");
        }

        if (!isset($this->db)) {
            $msg = __l("Undefined store connection in core.");
            throw new SystemException($msg);
        }

        if (!$path) {
            if ($pluginName) {
                if (is_bool($pluginName)) {
                    $pluginName = $objectName;
                }
                $path = $this->getOption(static::OPTION_PLUGINS_PATH).$pluginName.
                        DIRECTORY_SEPARATOR;
            } else {
                $path = $this->getOption("objects_path");
            }
        }

        return DataAccessObject::getInstance(
            $objectName, 
            $this->db, 
            $path
        );
    } // end getObject

    public function exec($cmd, &$output = array())
    {
        $res = exec(escapeshellcmd($cmd), $output, $ret);
        if ($ret !== 0) {
            throw new SystemException("Can't exec: ".$cmd, $ret);
        }

        return $res;
    } // end exec
    
    public function getStoragePathPrefixByID($idEntity, $options = array())
    {
        $path = null;
        
        if (!empty($options['path'])) {
            $path = $options['path'];
        }
        
        $storageStep = empty($options['step']) ? 2 : $options['step'];
        $storagePathChunkCount = 2;
        if (!empty($options['chunks'])) {
            $storagePathChunkCount = $options['chunks'];
        }
        
        $minStrLength = $storageStep * $storagePathChunkCount;
    
        if (strlen($idEntity) < $minStrLength) {
            $pathString = str_pad($idEntity, $minStrLength, "0", STR_PAD_LEFT);
        } else {
            $startIndex = strlen($idEntity) - $minStrLength;
            $pathString = substr($idEntity, $startIndex);
        }
    
        $chunks = str_split($pathString, $storageStep);
        
        $pathPrefix = join(DIRECTORY_SEPARATOR, $chunks).DIRECTORY_SEPARATOR;
    
        // Hack: for folder permission from web and cron job
        if ($path) {
            foreach ($chunks as $prefix) {
                $path .= $prefix.DIRECTORY_SEPARATOR;
                if (!is_dir($path) && !mkdir($path, 0777)) {
                    throw new PermissionsException(__l("Permission error: %s", $path));
                }
            }
            
            $pathPrefix = $path;
        }
        
        return $pathPrefix;
    }

    ///////////////////////////////
    // Session
    //////////////////////////////
    
    public function isExistParam($key)
    {
        return isset($_SESSION[$key]);
    }

    public function addParam($key, $value, $isUseToken = false)
    {
        if ($isUseToken) {
            $tokenKey = $this->getOption(static::OPTION_SESSION_TOKEN_KEY);
            if (isset($_REQUEST[$tokenKey])) {
                $_SESSION[static::OPTION_SESSION_TOKENS][$_REQUEST[$tokenKey]][$key] = $value;
                return true;
            }
        } 
        
        $_SESSION[$key] = $value;
    } // end addParam

    public function getParam($key, $isUseToken = false)
    {
        if ($isUseToken) {
            $tokenKey = $this->getOption(static::OPTION_SESSION_TOKEN_KEY);
            if (isset($_REQUEST[$tokenKey])) {
                $token = $_REQUEST[$tokenKey];
                
                return $_SESSION[static::OPTION_SESSION_TOKENS][$token][$key] ?? false;
            }
        }
        
        return $_SESSION[$key] ?? false;
    } // end getParam

    public function popParam($key, $isUseToken = false)
    {
        if ($isUseToken) {
            $tokenKey = $this->getOption(static::OPTION_SESSION_TOKEN_KEY);
            if (isset($_REQUEST[$tokenKey])) {
                $token = $_REQUEST[$tokenKey];
                
                $value = $_SESSION[static::OPTION_SESSION_TOKENS][$token][$key] ?? false;
                unset($_SESSION[static::OPTION_SESSION_TOKENS][$token][$key]);
                return $value;
            }
        }
        
        $value = $this->getParam($key);
        $this->removeParam($key);

        return $value;
    } // end popParam

    public function getSessionToken()
    {
        $tokenKey = $this->getOption(static::OPTION_SESSION_TOKEN_KEY);
        if (empty($_REQUEST[$tokenKey])) {
            return false;
        }
        
        return $_REQUEST[$tokenKey];
    } // end getSessionToken

    public function removeParam($key)
    {
        if (!isset($_SESSION[$key])) {
            return false;
        }

        unset($_SESSION[$key]);
        return true;
    }
    
    public function startSessionToken()
    {
        $key = $this->getOption(static::OPTION_SESSION_TOKEN_KEY);
        
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        
        $token = bin2hex(random_bytes(32));
        
        if (!array_key_exists(static::SERVER_REQUEST_URI, $_SERVER)) {
            return false;
        }
        
        $url = $_SERVER[static::SERVER_REQUEST_URI];
        
        if (strpos($url, '?') !== false) {
            $url .= "&";
        } else {
            $url .= "?";
        }
        
        $url .= $key."=".$token;
        
        $this->redirect($url, false);
    } // end startSessionToken
    
    /**
     * Dispatch event
     *
     * @param mixed $eventType
     * @param mixed|null $data
     * @return array|bool
     */
    public function fireEvent($eventType, &$data = null)
    {
        $event = new FestiEvent($eventType, $data);
        return $this->dispatchEvent($event);
    } // end fireEvent
    
    public function isMobileRequest()
    {
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return false;
        }
        
        $regExp = '#Mobile|iP(hone|od|ad)|Android|BlackBerry|IEMobile|Kindle|'.
              'NetFront|Silk-Accelerated|(hpw|web)OS|Fennec|Minimo|'.
              'Opera M(obi|ini)|Blazer|Dolfin|Dolphin|Skyfire|Zune#Umis';
              
        
        return preg_match($regExp, $_SERVER['HTTP_USER_AGENT']);
    } // end isMobileRequest
    
    public function getConfigValue($key)
    {
        if (!isset($this->config)) {
            throw new SystemException("Undefined config in Core");
        }
        
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }
        
        return false;
    } // end getConfigValue
    
    public function setSystemPlugin(ISystemPlugin &$plugin)
    {
        $this->_systemPlugin = &$plugin;
    } // end setSystemPlugin

    /**
     * @return ISystemPlugin
     * @throws SystemException
     */
    public function &getSystemPlugin(): ISystemPlugin
    {
        if (!$this->_systemPlugin) {
            throw new SystemException("Undefined System Plugin.");
        }

        return $this->_systemPlugin;
    } // end getSystemPlugin
    
    public function isConsole()
    {
        return php_sapi_name() == 'cli';
    } // end isConsole
    
    public function setHooks(&$hooks)
    {
        $this->_hooks = $hooks;
    } // end setHooks
    
    public function addHookListener($name, $className)
    {
        if (array_key_exists($name, $this->_hooks)) {
            if (is_scalar($this->_hooks[$name])) {
                $this->_hooks[$name] = array($this->_hooks[$name]);
            }
            
            $this->_hooks[$name][] = $className;
        } else {
            $this->_hooks[$name] = $className;
        }
        
        return true;
    } // end addHookListener
    
    public function setHookListener($name, $className)
    {
        $this->_hooks[$name] = $className;
        
        return true;
    } // end setHookListener
    
    
    public function fireHook($name, &$data)
    {
        if (!array_key_exists($name, $this->_hooks)) {
            return false;
        }
        
        $listeners = $this->_hooks[$name];
        
        if (is_scalar($listeners) || is_callable($listeners)) {
            $listeners = array($listeners);
        }
        
        foreach ($listeners as $callback) {
            if (!is_callable($callback)) {
                $plugin = $this->getPluginInstance($callback);
                $callback = array(
                    &$plugin,
                    $name
                );
            }
            
            call_user_func_array($callback, array(&$data));
        }
    } // end fireHook
    
}

//FIXME:

if (!function_exists('__')) {
    function __()
    {
        $args = func_get_args();
        
        return call_user_func_array('__l', $args);
    }
}

// @codingStandardsIgnoreStart
function __l()
{
    $args = func_get_args();
    if (!isset($args[0])) {
        return null;
    }

    $locale = Core::getInstance()->getLocaleModel();
    $word = $locale->get($args[0]);
    if (!$word) {
        $word = $args[0];
    }

    $params = array_slice($args, 1);
    if ($params) {
        $word = vsprintf($word, $params);
    }

    return $word;
}
// @codingStandardsIgnoreEnd