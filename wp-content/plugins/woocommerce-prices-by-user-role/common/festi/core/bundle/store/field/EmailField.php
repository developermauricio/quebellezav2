<?php

class EmailField extends AbstractField
{
    public function isValidValue($value)
    {
        if (!parent::isValidValue($value)) {
            return false;
        }
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->lastErrorMessage = __l(
                '%s in invalid format', 
                $this->attributes['caption']
            );
            return false;
        }
        
        return true;
    } // end isValidValue
    
    public function getInputType()
    {
        return 'email';
    }
}