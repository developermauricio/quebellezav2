<?php

class Md5Field extends AbstractField
{
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $this->value = htmlspecialchars($value);
        
        return $this->fetch('edit.phtml');
    }

    /**
     * @override
     */
    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);

        if (!$value && $this->get('isnull')) {
            return $value;
        }

        if (!$value) {
            return false;
        }

        $value = strlen($value) != 32 ? md5($value) : $value;

        return $value;
    } // end getValue
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."md5".DIRECTORY_SEPARATOR;
    } // end getTemplatePath

}
