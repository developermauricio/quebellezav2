<?php

class FestiWpmlManager
{
    protected $mainFile;
    protected $config;
    
    public function __construct($pluginNamePrefix, $mainFile)
    {
        $this->mainFile = $mainFile;
        $this->config = $this->factory($pluginNamePrefix);
        
        $this->onInit();
    } // end __construct
    
    protected function factory($pluginNamePrefix)
    {
        $className = $pluginNamePrefix.'WpmlConfig';
        
        $classFile = dirname($this->mainFile).'/'.$className.'.php';
                          
        if (!file_exists($classFile)) {
            $message = 'File "%s" for "%s" was not found.';
            $message = sprintf($message, $classFile, $className);
            throw new Exception($message);
        }
            
        require_once $classFile;
        
        if (!class_exists($className)) {
            $message = 'Class "%s" was not found in file "%s".';
            $message = sprintf($message, $classFile, $className);
            throw new Exception($message);
        }
        
        return new $className();
    } // end factory
    
    protected function onInit()
    {
        $facade = EngineFacade::getInstance();

        $facade->addActionListener(
            FESTI_FILTER_GET_STATIC_STRING,
            array($this, 'onGetStaticStringFilter'),
            10,
            2
        );

        $facade->addActionListener(
            FESTI_FILTER_GET_OPTIONS,
            array($this, 'onGetWpmlOptionsFilter'),
            10,
            2
        );

        $facade->addFilterListener(
            FESTI_ACTION_REGISTER_STATIC_STRING,
            array($this, 'onRegisterStaticStringsAction'),
            10,
            2
        );

        $facade->addFilterListener(
            FESTI_ACTION_UPDATE_OPTIONS,
            array($this, 'onRegisterDinamicOptionStringsAction'),
            10,
            2
        );
    } // end onInit
    
    public function onGetStaticStringFilter($string, $key)
    {
        if (!$this->_isExistsWpmlTranslateFunction()) {
            return $string;
        }

        return $this->getWpmlValueByStringKey($string, $key);
    } // end onGetStaticStringFilter
    
    protected function getWpmlValueByStringKey($string, $key)
    {
        $wpmlKey = $this->getWpmlKey();
        
        return icl_translate($wpmlKey, $key, $string);
    } // end getWpmlValueByStringKey
    
    private function _isExistsWpmlTranslateFunction()
    {
        return function_exists('icl_translate');
    } // end _isExistsWpmlTranslateFunction
    
    public function onRegisterStaticStringsAction($string, $key)
    {
        if (!$this->_isExistsWpmlRegisterStringFunction()) {
            return false;
        }

        $this->doRegisterWpmlString($string, $key);
    } // end onRegisterStaticStringsAction
    
    private function _isExistsWpmlRegisterStringFunction()
    {
        return function_exists('icl_register_string');
    } // end _isExistsWpmlRegisterStringFunction
    
    protected function doRegisterWpmlString($value, $stringKey)
    {
        $wpmlKey = $this->getWpmlKey();

        icl_register_string($wpmlKey, $stringKey, $value);
    } // end doRegisterWpmlString
    
    public function onGetWpmlOptionsFilter($value, $optionName)
    {
        if (!$this->_isPossibleToTranslationValue($optionName)) {
            return $value;
        }

        $stringKey = $this->getTranslateOptionKeys($optionName);
        
        if ($this->_isStringValue($optionName, $stringKey)) {
            return $this->getWpmlValueByStringKey($value, $stringKey);
        }
        
        if (!is_array($value)) {
            return $value;
        }
        
        $value = $this->_getValueWithTranslatedStrings($value, $optionName);
        
        return $value;
    } // end onGetWpmlOptionsFilter
    
    protected function _getValueWithTranslatedStrings($options, $optionName)
    {
        foreach ($options as $key => $value) {
            $reult = is_string($value);
                
            if (!$reult || !$this->_hasKeyInTranslateList($key, $optionName)) {
                continue;
            }
            
            $stringKey = $key.' ('.$optionName.')';
            $value = $options[$key];
            
            $options[$key] = $this->getWpmlValueByStringKey($value, $stringKey);
        }
        
        return $options;
    } // end _getValueWithTranslatedStrings
    
    private function _isStringValue($optionValue, $translateKeys)
    {
        return is_string($optionValue) && is_string($translateKeys);
    } // end _isStringValue
    
    protected function getTranslateOptionKeys($optionName)
    {
        $translateList = $this->getTranslateList();
        
        return $translateList[$optionName];
    } // end getTranslateOptionKeys
    
    private function _isPossibleToTranslationValue($optionName)
    {
        $facade = EngineFacade::getInstance();

        $list = $this->getTranslateList();
        
        return !$facade->isAdminPanel() &&
               $this->_isExistsWpmlTranslateFunction() &&
               $this->_hasOptionNameInTranslateList($optionName, $list);
    } // end _isPossibleToTranslationValue
    
    private function _isPossibleToRegistrationString($optionName)
    {
        $list = $this->getTranslateList();
        
        return $this->_isExistsWpmlRegisterStringFunction() &&
               $this->_hasOptionNameInTranslateList($optionName, $list);
    } // end _isPossibleToRegistrationString
    
    private function _hasOptionNameInTranslateList($optionName, $translateList)
    {
        return array_key_exists($optionName, $translateList);
    } // end _hasOptionNameInTranslateList

    public function onRegisterDinamicOptionStringsAction($value, $optionName)
    {
        if (!$this->_isPossibleToRegistrationString($optionName)) {
            return false;
        }

        $stringKey = $this->getTranslateOptionKeys($optionName);
        
        if ($this->_isStringValue($optionName, $stringKey)) {
            $this->doRegisterWpmlString($value, $stringKey);
            return true;
        }
        
        if (!is_array($value)) {
            return $value;
        }
        
        $this->doRegisterAllStringsInOptionValue($value, $optionName);
    } // end onRegisterDinamicOptionStringsAction
    
    protected function doRegisterAllStringsInOptionValue($options, $optionName)
    {
        foreach ($options as $key => $value) {
            $result = is_string($value);
            
            if (!$result || !$this->_hasKeyInTranslateList($key, $optionName)) {
                return false;
            }
            
            $this->doRegisterWpmlString($value, $key.' ('.$optionName.')');  
        }
    } // end doRegisterAllStringsInOptionValue
    
    public function getWpmlKey()
    {
        return $this->config->getWpmlKey();
    } // end getWpmlKey
    
    public function getTranslateList()
    {
        return $this->config->getTranslateList();
    } // end getTranslateList
    
    private function _hasKeyInTranslateList($key, $optionName)
    {
        $stringList = $this->getTranslateList();
        
        $list = $stringList[$optionName];
        
        return in_array($key, $list);
    } // end _hasKeyInTranslateList
}