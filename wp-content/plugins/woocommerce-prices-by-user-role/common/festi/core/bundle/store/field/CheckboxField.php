<?php

class CheckboxField extends AbstractField
{
    const BOOL_VALUE_TRUE  = '1';
    const BOOL_VALUE_FALSE = '0';
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $readonly = $this->get('readonly');
        if ($readonly) {
            return $this->displayValue($value);
        }
        
        if (is_null($value) && $this->get('value')) {
            $value = $this->get('value');
        }
        
        if (is_numeric($value) || is_bool($value)) {
            $this->checked = ($value) ? 'checked' : '';
        } else {
            $this->checked = (strtoupper(substr($value, 0, 1)) == 'Y') ? 'checked' : '';
        }
        
        $this->value = htmlspecialchars($value);
        
        return $this->fetch('edit.phtml');
    }
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = null): ?string
    {
        $this->value = $value;
        
        return $this->fetch('cell_value.phtml');
    }
    
    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);
        
        $isBool = $this->get('isbool');
        
        if ($isBool && !is_null($value)) {
            $value = $this->_getBoolValue($value);
        }
        
        return $value;
    } // end getValue
    
    function displayRO($value)
    {
        return $this->displayValue($value);
    }
    
    protected function getFilterTemplateName()
    {
        return 'checkbox.phtml';
    } // end getFilterTemplateName
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."checkbox/";
    } // end getTemplatePath    
    
    private function _getBoolValue($value)
    {
        if ($value) {
            return static::BOOL_VALUE_TRUE;
        }
        
        return static::BOOL_VALUE_FALSE;
    } // end _getBoolValue
}