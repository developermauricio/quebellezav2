<?php

class SerializeField extends AbstractField
{
    protected $defaultValues = array();

    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        if (!$scheme->hasOptions()) {
            return false;
        }
        
        $options = $scheme->getOptions();
        foreach ($options as $item) {
            $this->defaultValues[$item['id']] = $item['value'];
        }
    } // end onInit
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $this->values = $this->_decodeValue($value);
        $this->values = array_merge($this->defaultValues, $this->values);
        
        return $this->fetch('edit.phtml');
    }
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        $this->values = $this->_decodeValue($value);
        
        return $this->fetch('cell_value.phtml');
    }

    /**
     * @param string $value
     * @param array $row
     * @return string
     */
    public function getInfoValue($value, $row = null)
    {
        return $this->displayValue($value, $row);
    }

    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);

        if (!$value && $this->get('isnull')) {
            $value = null;
            return $value;
        }
    
        if ($this->_isRequired($value)) {
            $this->lastErrorMessage = __l(
                '%s is required field',
                $this->attributes['caption']
            );
            return false;
        }
        
        if (!$value) {
            return  "";
        }
        
        $value = array_combine($value['key'], $value['value']);
        $value = array_filter($value);

        return json_encode($value);
    } // end getValue
    
    private function _decodeValue($value)
    {
        return !is_null($value) ? json_decode($value, true) : array();
    } // end _decodeValue
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."serialize/";
    } // end getTemplatePath

    protected function onPrepareValue(&$value)
    {
        $keys      = array_keys($value);
        $keyValues = array_shift($value);
        $values    = array_shift($value);
        
        $this->_onPrepareValues($keyValues);
        $this->_onPrepareValues($values);

        $value = array(
            array_shift($keys) => $keyValues,
            array_shift($keys) => $values,
        );
        
        return true;
    } // end onPrepareValue
    
    private function _onPrepareValues(&$values)
    {
        if (!is_array($values)) {
            return true;
        }
        
        foreach ($values as &$value) {
            $value = filter_var($value, FILTER_SANITIZE_STRING);
        }

        return true;
    } // end _onPreparedValue
    
    private function _isRequired($value)
    {
        $isRequired = $this->get('required');
        
        return $isRequired && 
               is_array($value) && 
               array_key_exists('key', $value) &&
               !current($value['key']);
    }
}