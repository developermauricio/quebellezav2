<?php

class TextareaField extends AbstractField
{
    /**
     * @override
     */
    protected function getAttributesFields(): array
    {
        $fields = parent::getAttributesFields();
        
        $fields['maxChars'] = self::FIELD_TYPE_INT;

        return $fields;
    } // end getAttributesFields
    
    /**
     * @override
     */
    public function isValidValue($value)
    {
        $res = parent::isValidValue($value);
        if (!$res) {
            return false;
        }
        
        $maxChars = $this->get('maxChars');
        if ($maxChars > 0 && mb_strlen($value) > $maxChars) {
            $this->lastErrorMessage = __l(
                '%s cannot be longer than %s characters.', 
                $this->attributes['caption'], 
                $maxChars
            );
            
            return false;
        }

        return true;
    } // end isValidValue
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $this->value = $value;
        
        return $this->fetch('edit.phtml');
    } // end getEditInput
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."textarea/";
    } // end getTemplatePath
}
