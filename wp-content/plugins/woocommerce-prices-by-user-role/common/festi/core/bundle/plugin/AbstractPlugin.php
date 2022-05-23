<?php

abstract class AbstractPlugin extends Entity
{
    /**
     * @var Core
     */
    protected $core;

    /**
     * @var core\util\PluginWrapper|PHPStormPluginWrapper
     */
    protected $plugin;

    private $__options;

    public function __construct()
    {
        parent::__construct();

        $this->core = &Core::getInstance();

        $this->plugin = core\util\PluginWrapper::getInstance();
    }
    
    public function getSetting($key)
    {
        return $this->core->getSystemPlugin()->getSetting($key);
    }
    
    public function hasSetting($key)
    {
        return $this->core->getSystemPlugin()->hasSetting($key);
    }

    public function setOptions($options)
    {
        $this->__options = $options;
    }

    public function getOptions()
    {
        return $this->__options;
    }

    public function getOption($key)
    {
        return $this->__options[$key] ?? false;
    }

    public function setOption($key, $value)
    {
        $this->__options[$key] = $value;
    }

    public function onInit()
    {
    }
    
    /**
     * @param string $storeName
     * @param array $params
     * @return Store
     * @throws SystemException
     */
    public function createStoreInstance(string $storeName, array $params = array()) : Store
    {
        $this->_doPrepareForCreateStore($params);
        
        return $this->core->createStoreInstance($storeName, $params);
    } // end createStoreInstance
    
    /**
     * @param IDataAccessObject $connection
     * @param string $storeName
     * @param array $params
     * @return Store
     * @throws PermissionsException
     * @throws StoreException
     * @throws SystemException
     */
    public function createStoreInstanceByConnection(
        IDataAccessObject $connection, string $storeName, array $params = array()
    ): Store
    {
        $this->_doPrepareForCreateStore($params);
        
        return $this->core->createStoreInstanceByConnection(
            $connection, 
            $storeName, 
            $params
        );
    } // end createStoreInstanceByConnection
    
    /**
     * @param array $params
     * @return bool
     */
    private function _doPrepareForCreateStore(array &$params): bool
    {
        $pluginPath = $this->getOption('plugins_path');
        $pluginName = $this->getOption('name');
        $params['plugin'] = $pluginName;
        
        $path = $pluginPath.$pluginName.'/tblDefs/';
        $this->core->setOption('defs_path', $path);

        return true;
    } // end _doPrepareForCreateStore

    public function __getName()
    {
        return $this->getOption('name');
    } // end __getName
    
    public function __isInstalled()
    {
        return true;
    } // end __isInstalled
    
    public function __getSettings()
    {
        return false;
    } // end __getSettings

    public function hasUserPermissionToSection($sectionName, $user = null)
    {
        throw new SystemException("Unsupporteble method.");
    }

    public function getPluginTemplatePath()
    {
        throw new SystemException("Unsupporteble method.");
    } // end getPluginTemplatePath
}
