<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'IFestiEngine.php';

/**
 * @package FestiEcommercePlugin
 * @version 2.3
 */
abstract class FestiPlugin extends EngineFacade implements IFestiEngine
{
    protected $engineUrl;
    protected $enginePluginsUrl;

    protected $pluginDirName;
    protected $pluginMainFile;

    protected $pluginPath;
    protected $pluginUrl;

    protected $pluginCachePath;
    protected $pluginCacheUrl;

    protected $pluginStaticPath;
    protected $pluginStaticUrl;

    protected $pluginCssPath;
    protected $pluginCssUrl;

    protected $pluginImagesPath;
    protected $pluginImagesUrl;

    protected $pluginJsPath;
    protected $pluginJsUrl;

    protected $pluginTemplatePath;
    protected $pluginTemplateUrl;

    protected $pluginLanguagesPath;
    protected $pluginLanguagesUrl;

    protected $languageDomain = '';
    protected $optionsPrefix = '';

    protected $fileSystem;

    /**
     * The instance of current CMS or eCommerce platform.
     *
     * @var $engineFacade
     */
    protected $engineFacade = null;

    public function __construct($pluginMainFile)
    {
        $facade = static::getInstance();

        $this->engineFacade = $facade;

        $this->engineUrl = $facade->getSiteUrl();
        $this->engineUrl = $this->makeUniversalLink($this->engineUrl);

        $this->enginePluginsUrl = $facade->getPluginsUrl('/');

        $this->enginePluginsUrl = $this->makeUniversalLink($this->enginePluginsUrl);

        $this->pluginDirName = $facade->getPluginBaseName(dirname($pluginMainFile)) . '/';

        $this->pluginMainFile = $pluginMainFile;

        $this->pluginPath = $facade->getPluginBasePath($pluginMainFile);

        $this->pluginUrl = $facade->getPluginsUrl('/', $pluginMainFile);
        $this->pluginUrl = $this->makeUniversalLink($this->pluginUrl);

        $this->pluginCachePath = $this->pluginPath . 'cache/';
        $this->pluginCacheUrl = $this->pluginUrl . 'cache/';

        $this->pluginStaticPath = $this->pluginPath . 'static/';
        $this->pluginStaticUrl = $this->pluginUrl . 'static/';

        $this->pluginCssPath = $this->pluginStaticPath . 'styles/';
        $this->pluginCssUrl = $this->pluginStaticUrl . 'styles/';

        $this->pluginImagesPath = $this->pluginStaticPath . 'images/';
        $this->pluginImagesUrl = $this->pluginStaticUrl . 'images/';

        $this->pluginJsPath = $this->pluginStaticPath . 'js/';
        $this->pluginJsUrl = $this->pluginStaticUrl . 'js/';

        $this->pluginTemplatePath = $this->pluginPath . 'templates/';
        $this->pluginTemplateUrl = $this->pluginUrl . 'templates/';

        $this->pluginLanguagesPath = $this->pluginDirName . 'languages/';

        $this->onInit();
    } // end __construct

    public function makeUniversalLink($url = '')
    {
        $protocols = array(
            'http:',
            'https:'
        );

        foreach ($protocols as $protocol) {
            $url = str_replace($protocol, '', $url);
        }

        return $url;
    } // end makeUniversalLink

    protected function onInit()
    {
        $facade = static::getInstance();

        $facade->registerActivationHook(
            array(&$this, 'onInstall'),
            $this->pluginMainFile
        );

        $facade->registerDeactivationHook(
            array(&$this, 'onUninstall'),
            $this->pluginMainFile
        );

        if ($this->_isBackend()) {
            $this->onBackendInit();
        } else {
            $this->onFrontendInit();
        }
    } // end _isAjaxRequestFromFrontend

    private function _isBackend()
    {
        $facade = static::getInstance();

        return $facade->isAdminPanel() ||
            $facade->isTestEnvironmentDefined() ||
            $facade->isAjax() &&
            !$this->_isAjaxRequestFromFrontend() ||
            $this->_isRestApiRequest();
    } // end _isBackend

    private function _isAjaxRequestFromFrontend()
    {
        $scriptFileName = '';
        if (!empty($_SERVER['SCRIPT_FILENAME'])) {
            $scriptFileName = $_SERVER['SCRIPT_FILENAME'];
        }

        $url = $this->_getReferredUrl();

        $facade = static::getInstance();

        $adminUrl = $facade->getAdminUrl();

        if ($url == '/wp-admin/admin-ajax.php') {
            $adminUrl = '/wp-admin/';
        }

        return strpos($url, $adminUrl) === false &&
            basename($scriptFileName) === 'admin-ajax.php';
    } // end onInit

    private function _getReferredUrl()
    {
        $url = '';
        if (!empty($_REQUEST['_wp_http_referer'])) {
            $url = wp_unslash($_REQUEST['_wp_http_referer']);
        } else if (!empty($_SERVER['HTTP_REFERER'])) {
            $url = wp_unslash($_SERVER['HTTP_REFERER']);
        }

        $url = parse_url($url);

        if (!empty($url['scheme'])) {
            $url['scheme'] .= '://';
        }

        $url = $url['scheme'] . $url['host'] . $url['path'];

        return $url;
    } // end onBackendInit

    private function _isRestApiRequest()
    {
        $facade = static::getInstance();

        return $facade->isRestApiRequest();
    } // end onFrontendInit

    protected function onBackendInit()
    {
    } // end onInstall

    protected function onFrontendInit()
    {
    } // end onUninstall

    public function onInstall()
    {
    } // end getLanguageDomain

    public function onUninstall()
    {
    } // end getLang

    /**
     * Use for correct support multilanguages. Example:
     *
     * <code>
     * $this->getLang('Hello');
     * $this->getLang('Hello, %s', $userName);
     * </code>
     *
     * @param ...$args
     * @return boolean|string
     */
    public function getLang()
    {
        $args = func_get_args();
        if (!isset($args[0])) {
            return false;
        }

        $word = __($args[0], $this->getLanguageDomain());
        if (!$word) {
            $word = $args[0];
        }

        $params = array_slice($args, 1);
        if ($params) {
            $word = vsprintf($word, $params);
        }

        return $word;
    } // end getPluginPath

    public function getLanguageDomain()
    {
        return $this->languageDomain;
    } // end getPluginCachePath

    public function getPluginPath()
    {
        return $this->pluginPath;
    } // end pluginStaticPath

    public function getPluginStaticPath($fileName)
    {
        return $this->pluginStaticPath . $fileName;
    } // end pluginCssPath

    public function getPluginCssPath($fileName)
    {
        return $this->pluginCssPath . $fileName;
    } // end pluginImagesPath

    public function getPluginImagesPath($fileName)
    {
        return $this->pluginImagesPath . $fileName;
    } // end pluginJsPath

    public function getPluginJsPath($fileName)
    {
        return $this->pluginJsPath . $fileName;
    } // end getPluginTemplatePath

    public function getPluginLanguagesPath()
    {
        return $this->pluginLanguagesPath;
    } // end getPluginLanguagesPath

    public function getPluginUrl()
    {
        return $this->pluginUrl;
    } // end getPluginUrl

    public function getPluginCacheUrl()
    {
        return $this->pluginCacheUrl;
    } // end getPluginCacheUrl

    public function getPluginStaticUrl()
    {
        return $this->pluginStaticUrl;
    } // end getPluginStaticUrl

    public function getPluginImagesUrl($fileName)
    {
        return $this->pluginImagesUrl . $fileName;
    } // end getPluginCssUrl

    public function getPluginTemplateUrl($fileName)
    {
        return $this->pluginTemplateUrl . $fileName;
    } // end getPluginImagesUrl

    public function isPluginActive($pluginMainFilePath)
    {
        $result = false;

        $facade = static::getInstance();

        if ($facade->isMultiSiteOptionOn()) {
            $activePlugins = $facade->getMainSiteOption('active_sitewide_plugins');
            $result = array_key_exists($pluginMainFilePath, $activePlugins);
        }

        if ($result) {
            return true;
        }

        $activePlugins = $facade->getOption('active_plugins');

        return in_array($pluginMainFilePath, $activePlugins);
    } // end getPluginJsUrl

    public function addActionListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        if (!is_array($method)) {
            $method = array(&$this, $method);
        }

        $facade = static::getInstance();

        $facade->addActionListener(
            $hook,
            $method,
            $priority,
            $acceptedArgs
        );
    } // end getPluginTemplateUrl

    public function addFilterListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        if (!is_array($method)) {
            $method = array(&$this, $method);
        }

        $facade = static::getInstance();

        $facade->addFilterListener(
            $hook,
            $method,
            $priority,
            $acceptedArgs
        );
    } // end isPluginActive

    public function addShortCodeListener($tag, $method)
    {
        $this->engineFacade->addShortCode(
            $tag,
            array(&$this, $method)
        );
    } // end addActionListener

    public function getOptions($optionName)
    {
        $options = $this->getCache($optionName);

        if (!$options) {
            $facade = static::getInstance();

            $optionName = $this->optionsPrefix . $optionName;

            $options = $facade->getOption($optionName);
        }

        $options = json_decode($options, true);

        return $options;
    } // end addFilterListener

    public function getCache($fileName)
    {
        $file = $this->getPluginCachePath($fileName);

        if (!file_exists($file)) {
            return false;
        }

        ob_start();

        include($file);

        $content = ob_get_contents();

        ob_end_clean();

        return $content;
    } // end addShortCodeListener

    public function getPluginCachePath($fileName)
    {
        return $this->pluginCachePath . $fileName . '.php';
    } // end getOptions

    public function updateOptions($optionName, $values = array())
    {
        $values = $this->_doChangeSingleQuotesToDouble($values);

        $value = json_encode($values);

        $facade = static::getInstance();

        $facade->updateOption($this->optionsPrefix . $optionName, $value);

        return $this->updateCacheFile($optionName, $value);
    } //end getCache

    private function _doChangeSingleQuotesToDouble($options = array())
    {
        foreach ($options as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            $result = str_replace("'", '"', $value);

            if ($this->isPathOption($key)) {
                $options[$key] = addslashes(realpath($result));
            } else {
                $options[$key] = stripslashes($result);
            }
        }

        return $options;
    } // end updateOptions

    public function isPathOption($key)
    {
        $options = array(
            'filePath',
            'uploadFolderPath'
        );

        return in_array($key, $options);
    } // end _doChangeSingleQuotesToDouble

    public function updateCacheFile($fileName, $values)
    {
        if (!$this->fileSystem) {
            $this->fileSystem = $this->getFileSystemInstance();
        }

        if (!$this->fileSystem) {
            return false;
        }

        if (!$this->fileSystem->is_writable($this->pluginCachePath)) {
            return false;
        }

        $content = "<?php return '" . $values . "';";

        $filePath = $this->getPluginCachePath($fileName);

        $this->fileSystem->put_contents($filePath, $content, 0777);
    }

    public function getFileSystemInstance($method = false)
    {
        $fileSystem = false;

        $facade = static::getInstance();

        if (!$method) {
            $method = $facade::DEFAULT_FILE_SYSTEM_METHOD;
        }

        if ($this->_hasWordpressFileSystemObjectInGlobals()) {
            $fileSystem = $GLOBALS['wp_filesystem'];
        }

        if (!$fileSystem) {
            $this->defineFileSystemMethod($method);
            $facade->initFileSystem();
            $fileSystem = $GLOBALS['wp_filesystem'];
        }

        if ($this->_isFtpFileSystem($facade)) {
            $fileSystem = $facade->getDefaultFileSystemInstance();
        }

        return $fileSystem;
    } //end updateCacheFile

    private function _hasWordpressFileSystemObjectInGlobals()
    {
        return array_key_exists('wp_filesystem', $GLOBALS);
    } // end getFileSystemInstance

    protected function defineFileSystemMethod($method)
    {
        if (!defined('FS_METHOD')) {
            define('FS_METHOD', $method);
        }
    } // end defineFileSystemMethod

    private function _isFtpFileSystem($facade)
    {
        return defined('FS_METHOD') &&
            FS_METHOD == $facade::FTP_FILE_SYSTEM_METHOD;
    } // end _hasWordpressFileSystemObjectInGlobals

    public function onEnqueueJsFileAction(
        $handle,
        $file = '',
        $deps = '',
        $version = false,
        $inFooter = false,
        $customUrl = false
    )
    {
        $src = '';

        if ($file) {
            $src = $this->getPluginJsUrl($file, $customUrl);
        }

        if ($deps) {
            $deps = array($deps);
        }

        $this->engineFacade->doEnqueueScript(
            $handle,
            $src,
            $deps,
            $version,
            $inFooter
        );
    } // end  onEnqueueJsFileAction

    public function getPluginJsUrl($fileName, $customUrl = false)
    {
        if ($customUrl) {
            return $customUrl . $fileName;
        }

        return $this->pluginJsUrl . $fileName;
    } // end  onEnqueueCssFileAction

    public function onEnqueueCssFileAction(
        $handle,
        $file = '',
        $deps = array(),
        $version = false,
        $media = 'all',
        $customUrl = false
    )
    {
        $src = '';

        if ($file) {
            $src = $this->getPluginCssUrl($file, $customUrl);
        }

        if ($deps) {
            $deps = array($deps);
        }

        $this->engineFacade->doEnqueueStyle(
            $handle,
            $src,
            $deps,
            $version,
            $media
        );
    } // end fetch

    public function getPluginCssUrl($fileName, $customUrl = false)
    {
        if ($customUrl) {
            return $customUrl . $fileName;
        }

        return $this->pluginCssUrl . $fileName;
    } // end getUrl

    public function getUrl()
    {
        $url = $_SERVER['REQUEST_URI'];

        $args = func_get_args();
        if (!$args) {
            return $url;
        }

        if (!is_array($args[0])) {
            $url = $args[0];
            $args = array_slice($args, 1);
        }

        if (isset($args[0]) && is_array($args[0])) {

            $data = parse_url($url);

            if (array_key_exists('query', $data)) {
                $url = $data['path'];
                parse_str($data['query'], $params);

                foreach ($args[0] as $key => $value) {
                    if ($value != '') {
                        continue;
                    }

                    unset($args[0][$key]);

                    if (array_key_exists($key, $params)) {
                        unset($params[$key]);
                    }
                }

                $args[0] = array_merge($params, $args[0]);
            }

            $seperator = preg_match("#\?#Umis", $url) ? '&' : '?';
            $url .= $seperator . http_build_query($args[0]);
        }

        return $url;
    } // end displayError

    public function displayError($error)
    {
        $this->displayMessage($error, 'error');
    } // end displayUpdate

    public function displayMessage($text, $type)
    {
        $message = __(
            $text,
            $this->languageDomain
        );

        $template = 'message.phtml';

        $vars = array(
            'type' => $type,
            'message' => $message
        );

        echo $this->fetch($template, $vars);
    }// end displayMessage

    public function fetch($template, $vars = array())
    {
        if ($vars) {
            extract($vars);
        }

        ob_start();

        include $this->getPluginTemplatePath($template);

        return ob_get_clean();
    } // end _getReferredUrl

    public function getPluginTemplatePath($fileName)
    {
        return $this->pluginTemplatePath . $fileName;
    } // end getIdent

    public function displayUpdate($text)
    {
        $this->displayMessage($text, 'updated');
    } // getAttachmentsByPostID

    public function getAttachmentsByPostID($postParent, $fileType)
    {
    } // end getAbsolutePath

    public function getAbsolutePath($url)
    {
    } // end getPluginData

    public function getPluginData($pluginPath)
    {
    } // end getIdent

    public function dispatchAction($actionName)
    {
    } // dispatchFilter

    public function dispatchFilter($filterName, &$value)
    {
    } // end _isFtpFileSystem

    protected function getIdent()
    {
    } // end _isRestApiRequest
}