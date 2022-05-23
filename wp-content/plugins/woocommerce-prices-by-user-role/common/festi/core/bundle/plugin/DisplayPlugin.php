<?php

require_once 'bundle/ui/Hui.php';

/**
 * Class DisplayPlugin
 *
 * @property Hui $ui
 */
class DisplayPlugin extends ObjectPlugin
{

    public function __construct()
    {
        parent::__construct();

        $this->ui = new Hui($this);
    } // end __construct
    
    /**
     * @return string|null
     */
    public function getPluginTemplatePath()
    {
        $pluginPath = $this->getOption('plugin_path');
        if (!$pluginPath) {
            return null;
        }

        $postfix = $this->core->getOption('plugin_template_postfix');

        $path = $pluginPath."templates".$postfix.DIRECTORY_SEPARATOR;
        if (is_dir($path)) {
            return $path;
        }

        return $pluginPath."templates".DIRECTORY_SEPARATOR;
    }
    
    /**
     * @param $key
     * @param null $value
     */
    public function assign($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $item) {
                $this->$name = $item;
            }
        } else {
            $this->$key = $value;
        }
    } // end assign
    
    /**
     * @param string $file
     * @param string|null $path
     * @return string
     * @throws SystemException
     */
    public function fetchFrom(string $file, string $path = null): string
    {
        $templatePath = $path.$file;

        if (!file_exists($templatePath)) {
            throw new SystemException('Template file not found:'.$templatePath);
        }

        $vars = $this->getArrayCopy();
        
        extract($vars);
        
        ob_start();

        include($templatePath);

        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    } // end fetchFrom
    
    /**
     * @param string $file
     * @param string|null $tpl
     * @return string
     * @throws SystemException
     */
    public function fetch(string $file, string $tpl = null): string
    {
        $pluginPath = $this->getPluginTemplatePath();

        $path = $this->core->getOption('template_path');

        if (file_exists($pluginPath.$file)) {
            $path = $pluginPath;
        } else if (!file_exists($path.$file)) {
            $path = $this->core->getOption('theme_template_path');
        }

        $result = $this->fetchFrom($file, $path);

        if (!is_null($tpl)) {
            $this->content = $result;
            $result = $this->fetch($tpl);
        }

        return $result;
    } // end fetch

    protected function onDisplay()
    {
    }

    protected function onBreadcrumb()
    {
    }
    
    /**
     * @param array $seo
     */
    protected function onMeta(&$seo)
    {
        if (isset($this->core->seo)) {
            $seo = $this->core->seo;
        }

        $metaFields = array(
            'title'       => Entity::FIELD_TYPE_STRING_NULL,
            'description' => Entity::FIELD_TYPE_STRING_NULL,
            'keywords'    => Entity::FIELD_TYPE_STRING_NULL,
            'image_src'   => Entity::FIELD_TYPE_STRING_NULL
        );

        $seo = $this->getExtendData($seo, $metaFields);
    }

    public function display($content, $template = 'main.phtml')
    {
        //
        $this->content = $content;

        $info = array(
            'basehttp'       => $this->core->getOption("http_base"),
            'engine_url'     => $this->core->getOption("engine_url"),
            'theme_url'      => $this->core->getOption("theme_url"),
            'base_http_icon' => $this->core->getOption("http_base_icon"),
            'charset'        => $this->core->getOption("charset"),
        );

        $this->onDisplay();

        $info += $this->core->getProperties();

        //
        $seo = array();
        $this->onMeta($seo);

        $seoFields = array(
            'title'       => Entity::FIELD_TYPE_STRING_NULL,
            'description' => Entity::FIELD_TYPE_STRING_NULL,
            'keywords'    => Entity::FIELD_TYPE_STRING_NULL
        );

        $info['seo'] = $this->getExtendData($seo, $seoFields);

        $this->info = $info;

        // XXX: Injection for PHPUnit tests
        if (defined('PHPUnit')) {
            $GLOBALS['outputDisplay'] = $content;
            return $content;
        }

        echo $this->fetch($template);
        //exit();
    } // end display
    
    /**
     * @param string $relativePath
     * @throws SystemException
     */
    public function includeStatic(string $relativePath)
    {
        $url = $this->getStaticUrl($relativePath);
        $pathUrl = parse_url($url, PHP_URL_PATH);
        $ext = pathinfo($pathUrl, PATHINFO_EXTENSION);
        
        if ($ext == "js") {
            $this->core->includeJs($url, false);
        } else if ($ext == "css") {
            $this->core->includeCss($url, false);
        } else {
            throw new SystemException("Undefined resource type: ".$url);
        }
    } // end includeStatic
    
    /**
     * @param $relativePath
     * @return string
     */
    public function getStaticUrl(string $relativePath): string
    {
        return $this->getOption('plugin_http')."static/".$relativePath;
    } // end getStaticUrl
    
    /**
     * @param $url
     * @return mixed
     */
    public function getUrl(string $url): string
    {
        $method = array(&$this->core, 'getUrl');
        return call_user_func_array($method, func_get_args());
    } // end getUrl

    public function __getMenu()
    {
        return false;
    } // end __getMenu
    
    public function doThemeEvent($themeEventName)
    {
        $results = $this->core->fireEvent($themeEventName);
        if (!$results) {
            return false;
        }
        
        return $results;
    } // end doThemeEvent

}