<?php

class HiddenField extends AbstractField
{
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."hidden/";
    } // end getTemplatePath
}
