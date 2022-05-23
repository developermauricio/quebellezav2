<?php

require_once 'bundle/store/field/SelectField.php';

class TextcomboboxField extends SelectField
{
    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        $core = Core::getInstance();

        // TODO: Added ThemeKit Wrapper
        $engineUrl = $core->getOption('theme_url');
        $core->includeJs($engineUrl.'assets/js/jquery.combobox.js', false);
    }
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $this->combobox = true;
        
        if (!array_key_exists($value, $this->valuesList)) {
            $this->valuesList[$value] = $this->getFormattedValue($value);
        }
        
        return parent::getEditInput($value);
    }
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = null): ?string
    {
        if (!is_null($value) && array_key_exists($value, $this->valuesList)) {
            
            return $this->valuesList[$value];
        }

        return $value;
    } // end displayValue
}