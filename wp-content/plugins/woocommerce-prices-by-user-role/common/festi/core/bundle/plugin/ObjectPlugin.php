<?php

use \core\plugin\IDataAccessObjectPlugin;

class ObjectPlugin extends AbstractPlugin implements IDataAccessObjectPlugin
{
    /**
     * @var IDataAccessObject
     * @phan-var mixed
     */
    protected $object;
    
    public function __construct()
    {
        parent::__construct();
        
        if (!class_exists('DataAccessObject')) {
            throw new SystemException('DataAccessObject class does not found.');
        }
    } // end __construct

    /**
     * @override
     */
    public function onInit()
    {
        $this->object = $this->getObject();

        parent::onInit();
    } // end onInit

    /**
     * Returns reference to data access object instance. Returns FALSE if a data access object does not exist.
     *
     * @param string|null $name
     * @param string|null $pluginName
     * @return IDataAccessObject|null
     * @phan-return mixed
     * @throws DatabaseException
     * @throws SystemException
     */
    public function getObject(
        string $name = null, string $pluginName = null
    ): ?IDataAccessObject
    {
        if (is_null($name)) {
            $name = $this->__getName();
        }

        if (is_null($pluginName)) {
            $pluginName = $this->__getName();
        }
        
        $pluginPath = $this->getOption('plugins_path');
        $path = $pluginPath.$pluginName.DIRECTORY_SEPARATOR;

        if (!is_dir($path)) {
            $path = false;
        }

        // FIXME: Refectory to use Dependency Injection
        $className = DataAccessObject::getClassName($name);
        if (!class_exists($className)) {
            $classPath = DataAccessObject::getClassPath($className, $path);
            if (!$classPath) {
                return null;
            }

            require_once $classPath;
        }

        return Core::getInstance()->getObject($name, $pluginName, $path);
    } // end getObject
    
    public function getSystemObject(string $name = 'System')
    {
        $path = $this->core->getOption('core_path').'object/';
        
        return $this->core->getObject($name, false, $path);
    } // end getSystemObject
    
    public function hasUserPermissionToSection($sectionName, $user = null)
    {
        return $this->core->getSystemPlugin()->hasUserPermissionToSection(
            $sectionName,
            $user
        );
    } // end hasUserPermissionToSection
}
