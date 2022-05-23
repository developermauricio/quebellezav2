<?php

class WpmlCompatibleFestiPlugin extends FestiPlugin
{
    const ENABLED_MULTI_CURRENCY_OPTION_VALUE = 2;

    public function getOptions($optionName)
    {
        $options = parent::getOptions($optionName);

        /*
        $options = EngineFacade::getInstance()->dispatchFilter(
            FESTI_FILTER_GET_OPTIONS,
            $options,
            $this->_optionsPrefix.$optionName
        );
        */
        
        $options = apply_filters(
            FESTI_FILTER_GET_OPTIONS,
            $options,
            $this->optionsPrefix.$optionName
        );

        return $options;
    } // end getOptions

    public function updateOptions($optionName, $values = array())
    {
        $optionFullName = $this->optionsPrefix.$optionName;
        
        /*
        EngineFacade::getInstance()->dispatchAction(
            FESTI_ACTION_UPDATE_OPTIONS, 
            $values, 
            $optionFullName
        );
        */
        
        do_action(FESTI_ACTION_UPDATE_OPTIONS, $values, $optionFullName);
        
        return parent::updateOptions($optionName, $values);
    } // end updateOptions
    
    public function getCache($optionName)
    {
        if ($this->_isQtranslatePluginActive()) {
            return false;
        }

        $fileName = $this->getCacheFileName($optionName);

        if (!$fileName) {
            return false;
        }
        
        $file = $this->getPluginCachePath($fileName);
        
        if (!file_exists($file)) {
            return false;
        }
        ob_start();

        include($file);

        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    } // end getCache
    
    protected function getCacheFileName($folderName)
    {
        $fileFolderPath = $this->pluginCachePath.$folderName.'/';
        
        if (!file_exists($fileFolderPath)) {
            return false;
        }
        
        $fileName = $this->getCacheFileNameFromFolder($fileFolderPath);
        
        if (!$fileName) {
            return false;
        }
        
        return $folderName.'/'.$fileName;
    } // end getCacheFileName
    
    protected function getCacheFileNameFromFolder($folderPath)
    {
        $filesList = scandir($folderPath);
    
        $filesList = array_slice($filesList, 2);
    
        if (!$filesList) {
            return false;
        }
        
        $filename = str_replace('.php', '', $filesList[0]);
        
        return $filename;
    } // end getCacheFileNameFromFolder
    
    public function updateCacheFile($folderName, $values)
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
        
        $content = "<?php return '".$values."';";
        
        $fileFolderPath = $this->pluginCachePath.$folderName.'/';
        
        if (!file_exists($fileFolderPath)) {
            $this->fileSystem->mkdir($fileFolderPath, 0777);
        } else {
            $this->deleteAllFilesFromFolder($fileFolderPath);
        }
        
        $fileName = $folderName.'/'.time();
        
        $filePath = $this->getPluginCachePath($fileName);

        $this->fileSystem->put_contents($filePath, $content, 0777);
    } //end updateCacheFile
    
    protected function deleteAllFilesFromFolder($folderPath)
    {
        $filesList = scandir($folderPath);
    
        $filesList = array_slice($filesList, 2);
    
        if (!$filesList) {
            return false;
        }
        
        foreach ($filesList as $item) {
            if (is_file($folderPath.$item)) {
                unlink($folderPath.$item);
            }
        }
    } // end deleteAllFilesFromFolder
    
    private function _isQtranslatePluginActive()
    {
        return $this->isPluginActive('qtranslate-x/qtranslate.php');
    } // end _isQtranslatePluginActive

    protected function isWmplCurrenciesPluginActive()
    {
        $plugin = 'woocommerce-multilingual/wpml-woocommerce.php';

        return $this->isPluginActive($plugin);
    } // end isWmplCurrenciesPluginActive

    public function isWpmlMultiCurrencyOptionOn()
    {
        $facade = EngineFacade::getInstance();

        $options = $facade->getOption('_wcml_settings');

        $key = 'enable_multi_currency';

        if (!$this->_isOptionExist($options, $key)) {
            return false;
        }

        $option = $options[$key];

        return $option == self::ENABLED_MULTI_CURRENCY_OPTION_VALUE &&
               $this->isWmplCurrenciesPluginActive();
    } // end isWpmlMultiCurrencyOptionOn

    private function _isOptionExist($options, $key)
    {
        if (!is_array($options)) {
            return false;
        }

        return array_key_exists($key, $options);
    } // end _isOptionExist

    protected function isWmplTranslatePluginActive()
    {
        $plugin = 'wpml-translation-management/plugin.php';

        return $this->isPluginActive($plugin);
    } // end isWmplTranslatePluginActive
}