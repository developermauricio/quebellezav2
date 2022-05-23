<?php

class MultiAutocompleteField extends AbstractField
{
    /**
     * @override
     */
    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        $core = Core::getInstance();

        // TODO: Added ThemeKit Wrapper
        $url = $core->getOption('theme_url').'assets/js/tags/';
        
        $core->includeCss($url.'jquery.tagsinput.css', false);
        $core->includeJs($url.'jquery.tagsinput.min.js', false);
    } // end onInit
    
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
        
        $fields['defaultText'] = array(
            'type'    => self::FIELD_TYPE_STRING,
            'default' => ''
        );

        return $fields;
    } // end getAttributesFields
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."multiautocomplete/";
    } // end getTemplatePath
}