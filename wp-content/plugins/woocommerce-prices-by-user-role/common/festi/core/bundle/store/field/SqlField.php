<?php

class SqlField  extends AbstractField
{
    /**
     * @override
     */
    protected function getAttributesFields(): array
    {
        $fields = parent::getAttributesFields();
    
        $fields['query'] = array(
            'type'     => static::FIELD_TYPE_STRING_NULL,
            'error'    => __l('Attribute "query" is required'),
            'required' => true
        );
    
        return $fields;
    } // end getAttributesFields
    
    /**
     * @override
     */
    public function isVirtualField($actionName = false)
    {
        return true;
    } // end isVirtualField
}