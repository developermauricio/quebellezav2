<?php
require_once 'bundle/store/field/FileField.php';

class ImageField extends FileField
{
    /**
     * @override
     */
    protected function getAllowedMimeTypes(): array
    { 
        return array('image/jpeg', 'image/gif', 'image/png');
    } // end getAllowedMimeTypes
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath() . 'image' . DIRECTORY_SEPARATOR;
    } // end getTemplatePath
    
}
