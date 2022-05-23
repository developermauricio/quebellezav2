<?php
require_once 'bundle/store/field/FileField.php';

class AudioField extends FileField
{
    /**
     * @override
     */
    protected function getAllowedMimeTypes(): array
    { 
        return array('audio/mpeg');
    } // end getAllowedMimeTypes
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath() . 'audio' . DIRECTORY_SEPARATOR;
    } // end getTemplatePath
    
}
