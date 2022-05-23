<?php

require_once 'workbench/PluginContext.php';
require_once 'workbench/PluginAnnotations.php';

class Workbench extends Entity
{
    private static $_plugins;
    private static $_systemPluginContext;
    private static $_skipAnnotations = false;
    private static $_skipValidationSystemPlugin = false;
    private static $_dumpType = false;

    public static function install($options = array())
    {
        if (!empty($options['skip_annotations'])) {
            self::$_skipAnnotations = true;
        }
        
        // TODO: Fix me into wordpress 
        if (!empty($options['skip_validation_system_plugin'])) {
            self::$_skipValidationSystemPlugin = true;
        }
        
        if (!empty($options['dump_type'])) {
            self::$_dumpType = $options['dump_type'];
        } else {
            self::$_dumpType = Core::getInstance()->db->getDatabaseType();
        }
        
        self::doScanPlugins();
        
        $res = self::$_systemPluginContext->getInstance()->__isInstalled();
        
        self::_doInstallSystemPlugin();
        
        foreach (self::$_plugins as $plugin) {
            if ($plugin->isSystem()) {
                continue;
            }
            
            self::doInstallPlugin($plugin);
        }
        
    } // end install

    private static function _doInstallSystemPlugin()
    {
        if (!self::$_systemPluginContext) {
            throw new SystemException("Undefined system plugin context.");
        }

        $context = &self::$_systemPluginContext;
        
        $object = self::_getSystemObject();
        
        if (!self::$_skipValidationSystemPlugin) {
            try {
                $pluginData = $object->getPlugin($context->getName());
            
                $context->setVersion((int) $pluginData['version']);
            } catch (DatabaseException $exp) {
                // XXX: Initialization of database
            }
        }
        
        self::doPluginDump($context);
        
        $pluginData = self::_getPluginData($context);
        
        $context->setSystemFlag(true);
        
        $values = array(
            'version' => $context->getVersion()
        );
        
        $object->changePlugin($values, $context->getName());
        
        $pluginData['version'] = $context->getVersion();
        
        if (!self::$_skipAnnotations) {
            self::doProcessingPluginAnnotations($context);
        }
        
        return $pluginData;
    } // end _doInstallSystemPlugin

    public static function doInstallPlugin(PluginContext $context)
    {
        $pluginData = self::_getPluginData($context);
        
        $context->setVersion($pluginData['version']);
        
        self::doPluginDump($context);
        
        $object = self::_getSystemObject();
        
        $values = array(
            'version' => $context->getVersion()
        );
        
        $object->changePlugin($values, $context->getName());
        
        if (!self::$_skipAnnotations) {
            self::doProcessingPluginAnnotations($context);
        }
        
        return true;
    } // end doInstallPlugin
    
    private static function _getPluginData(PluginContext $context)
    {
        $object = self::_getSystemObject();
        
        $pluginData = $object->getPlugin($context->getName());
        
        if (!$pluginData) {
            $pluginData = array(
                'ident'   => $context->getName(),
                'version' => $context->getVersion()
            );
            
            $object->addPlugin($pluginData);
        }
        
        return $pluginData;
    } // end _getPluginData
    
    
    private static function _getSystemObject()
    {
        $systemPluginContext = self::getSystemPluginContext();
        
        return $systemPluginContext->getInstance()->getSystemObject();
    } // end _getSystemObject
    
    /**
     * Returns system plugin context.
     * 
     * @throws WorkbenchException
     * @retrun PluginContext
     */
    public static function &getSystemPluginContext()
    {
        if (self::$_systemPluginContext) {
            return self::$_systemPluginContext;
        }
        
        $core =  Core::getInstance();
        $path = $core->getOption('plugins_path');
        
        $systemPlugin = $core->getSystemPlugin();
        if ($systemPlugin) {
            $name = $systemPlugin->__getName();
        } else {
            $name = "Jimbo";
        }
        
        $pluginPath = $path.$name.DIRECTORY_SEPARATOR;
        
        if (!is_dir($pluginPath)) {
            throw new WorkbenchException("Not found system plugin.");
        }
        
        self::$_systemPluginContext = new PluginContext($name, $pluginPath);
        
        return self::$_systemPluginContext;
    } // end getSystemPluginContext
    
    
    public static function doProcessingPluginAnnotations(
        PluginContext $context
    )
    {
        $annotations = new PluginAnnotations($context);
        $annotations->parse();
        
        $object = self::_getSystemObject();
        
        $annotations->sync($object);
        
        return true;
    } // end doProcessingPluginAnnotations
    
    /**
     * Apply database update for the plugin.
     * 
     * @param PluginContext $context
     * @return boolean
     */
    public static function doPluginDump(PluginContext &$context)
    {
        $basePath = $context->getBasePath();
        
        $path = $basePath."install".DIRECTORY_SEPARATOR;
        if (!is_dir($path)) {
            return false;
        }
        
        $object = self::_getSystemObject();
        if (!$object->inTransaction()) {
            throw new WorkbenchException("Undefined db transaction.");
        }
        
        $currentVersion = $context->getVersion();
        if (is_null($currentVersion)) {
            $currentVersion = -1;
        }
        
        
        $dumps = self::_getDump($path, $currentVersion);
        
        if (!$dumps) {
            return false;
        }
        
        foreach ($dumps as $query) {
            if (!$query) {
                continue;
            }
            
            $object->query($query);
        }
        
        $context->setVersion($currentVersion);
        
        return true;
    } // end doPluginDump
    
    private static function _getDump($path, &$currentVersion)
    {
        $type = self::$_dumpType;
        
        $handle = opendir($path);
        if ($handle === false) {
            throw new WorkbenchException("Forbidden ".$path);
        }
        
        $installFileName = "install.".$type.".sql";
        $demoFileName = "demo.".$type.".sql";
        
        $files = array(
            $path.$installFileName
        );
        
        $skippedFiles = array('.', '..', $installFileName, $demoFileName);
        while (false !== ($entry = readdir($handle))) {
            if (in_array($entry, $skippedFiles)) {
                continue;
            }
        
            if (strpos($entry, $type.".sql") === false) {
                continue;
            }
        
            $regExp = "#updates([0-9]+)\.#Umis";
            if (!preg_match($regExp, $entry, $matches)) {
                throw new WorkbenchException(
                    "Wrong file name ".$entry." expected updates[0-9]+.TYPE.sql"
                );
            }
        
            $files[intval($matches[1])] = $path.$entry;
        }
        
        ksort($files, SORT_NUMERIC);
        
        $dumps = array();
        $newVersion = false;
        foreach ($files as $version => $dumpPath) {
            if ($version > $currentVersion) {
                
                $newVersion = $version;
                $dump = file_get_contents($dumpPath);
                $dump = array_filter(array_map('trim', explode(";", $dump)));
                if ($dump) {
                    $dumps = array_merge($dumps, $dump);
                }
            }
        }
        
        $currentVersion = $newVersion;
        
        return $dumps;
    } // end _getDump
    
    public static function getPluginsContexts()
    {
        if (!self::$_plugins) {
            throw new SystemException("Undefined plugins contexts.");
        }
        
        return self::$_plugins;
    } // end getPluginsContexts
    
    public static function doScanPlugins()
    {
        $core = Core::getInstance();
        
        $systemPlugin = $core->getSystemPlugin();
        if (!$systemPlugin) {
            throw new SystemException("Undefined System Plugin.");
        }
        
        $pluginsPath = $core->getOption('plugins_path');
        
        $handle = opendir($pluginsPath);
        if ($handle === false) {
            throw new SystemException("Forbidden ".$pluginsPath);
        }
        
        $systemPluginName = $systemPlugin->__getName();
        
        $plugins = array();
        while (false !== ($entry = readdir($handle))) {
            if ($entry == '.' || $entry == '..') {
                continue;
            } 
            
            $path = $pluginsPath;
            if (is_dir($pluginsPath.$entry)) {
                $path .= $entry.DIRECTORY_SEPARATOR; 
                $pluginName = $entry;
            } else {
                $pluginName = str_replace("Plugin.php", "", $entry);
            }
            
            $context = new PluginContext($pluginName, $path);
            
            if ($systemPluginName == $pluginName) {
                $context->setSystemFlag(true);
                self::$_systemPluginContext = $context;
            }
            
            self::$_plugins[] = $context;
        }
        
        return self::$_plugins;
    } // end doScanPlugins
    
    /*
     private function _doInstallPluginMenu($pluginInstance)
    {
        $pluginName = $pluginInstance->getOption("name");
        
        $menu = $pluginInstance->__getMenu();
        
        if (!$menu) {
            return false;
        }
        
        $this->_doInstallMenuItems($menu);
    } // end _doInstallPluginMenu
    
    private function _doInstallMenuItems($menu, $idParent = null)
    {
        $orderIndex = 0;
        foreach ($menu as $caption => $childs) {
            
            $search = array(
                'caption'   => $caption,
            );
            
            if ($idParent) {
                $search['id_parent'] = $idParent;
            } else {
                $search['id_parent&IS'] = 'NULL'; 
            }
            
            $info = $this->object->getMenuItem($search);
            if (!$info) {
                $values = array(
                    'caption'   => $caption,
                    'url'       => is_array($childs) ? null : $childs,
                    'id_parent' => $idParent,
                    'order_n'   => $orderIndex
                );
                
                $idMenuItem = $this->object->addMenuItem($values);
            } else {
                $idMenuItem = $info['id'];
            }
            
            if (is_array($childs)) {
                $this->_doInstallMenuItems($childs, $idMenuItem);
            }
            
            $orderIndex++;
        }
        
    } // end _doInstallMenuItems
     */
    
}

class WorkbenchException extends Exception
{

}
