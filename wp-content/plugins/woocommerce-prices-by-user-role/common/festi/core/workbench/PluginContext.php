<?php

class PluginContext
{
    private $_name;
    private $_path;
    private $_basePath;
    
    private $_isSystemPlugin = false;
    private $_instance;
    private $_currentVersion = -1;
    
    public function __construct($name, $path)
    {
        $this->_name     = $name;
        $this->_basePath = $path;
        $this->_path     = $this->_basePath.$this->_name.'Plugin.php';
        
        if (!file_exists($this->_path)) {
            throw new SystemException(
                "Not found plugin file [".$this->_path."]."
            );
        }
    } // end __construct
    
    public function getBasePath()
    {
        return $this->_basePath;
    } // end getBasePath
    
    public function getName()
    {
        return $this->_name;
    } // end getName
    
    public function setSystemFlag($flag)
    {
        $this->_isSystemPlugin = $flag;
    } // end setSystemFlag
    
    public function isSystem()
    {
        return $this->_isSystemPlugin;
    }
    
    public function &getInstance()
    {
        if ($this->_instance) {
            return $this->_instance;
        }
        
        $pluginInstance = &Core::getInstance()->getPluginInstance($this->_name);
        $this->_instance = $pluginInstance;
        
        return $this->_instance;
    } // end getInstance
    
    public function setVersion($version)
    {
        $this->_currentVersion = $version;
    } // end setVersion
    
    public function getVersion()
    {
        return $this->_currentVersion;
    } // end getVersion
    
}
