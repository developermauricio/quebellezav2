<?php

class AutocompleteField extends AbstractField
{
    /**
     * @override
     */
    protected function getAttributesFields(): array
    {
        $fields = parent::getAttributesFields();

        $fields['autocompleteUrl'] = array(
            'type'     => self::FIELD_TYPE_STRING,
            'error'    => 'Undefined url in field',
            'required' => true
        );

        $fields['minLength'] = array(
            'type'    => self::FIELD_TYPE_STRING,
            'default' => '2'
        );

        return $fields;
    } // end getAttributesFields

    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."autocomplete/";
    } // end getTemplatePath
}