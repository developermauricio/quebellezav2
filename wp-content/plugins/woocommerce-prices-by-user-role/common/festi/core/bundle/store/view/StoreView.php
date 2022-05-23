<?php

class StoreView extends Display
{
    /**
     * @var Store
     */
    protected $store;
    
    /**
     * StoreView constructor.
     * @param Store $store
     * @param string|null $path
     */
    public function __construct(Store &$store, string $path = null)
    {
        $this->store = &$store;
        
        if (!$path) {
            $path = $this->store->getOption('theme_template_path');
        }
        
        parent::__construct($path);
    } // end __construct
    
    /**
     * @return Store
     */
    public function &getStore(): Store
    {
        return $this->store;
    }
    
    /**
     * @param AbstractAction $action
     * @param Response $response
     * @param $vars
     * @return bool
     * @throws SystemException
     */
    public function onActionResponse(
        AbstractAction &$action, Response &$response, ?array &$vars
    )
    {
        $mode = $this->getStore()->getModel()->get('mode');
        // FIXME:
        if ($this->getStore()->isApiMode()) {
            $mode = StoreModel::MODE_API;
        }

        $className = get_class($action).ucfirst($mode).'View';

        if (!class_exists($className)) {
            $path = __DIR__.DIRECTORY_SEPARATOR.$mode.DIRECTORY_SEPARATOR;
            
            if (!is_dir($path)) {
                throw new SystemException(__("Undefined view mode %s", $mode));
            }
            
            $classPath = $path.$className.".php";

            if (!file_exists($classPath)) {
                throw new SystemException(
                    __("Undefined action view %s", $className)
                );
            }
            
            require_once $classPath;
        }
        
        $instance = new $className($this);

        if (!($instance instanceof IActionView)) {
            throw new SystemException(
                __("Unsupportable action view %s", $className)
            );
        }
        
        $instance->onResponse($action, $response, $vars);
        
        return true;
    } // end onActionResponse
    
    /**
     * @override
     * @throws SystemException
     */
    public function getTemplateFilePath(string $file): string
    {
        $path = parent::getTemplateFilePath($file);
        if (file_exists($path)) {
            return $path;
        }
        
        $plugin = $this->store->getPlugin();
        
        if ($plugin) {
            $path = $plugin->getPluginTemplatePath();
            $filePath = $path.$file;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        
        return $path;
    } // end getTemplatePath
}
