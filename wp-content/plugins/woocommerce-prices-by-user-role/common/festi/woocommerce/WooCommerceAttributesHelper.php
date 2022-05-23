<?php

class WooCommerceAttributesHelper extends FestiObject
{
    private $_fields;
    private $_facade;
    
    private $_names;
    
    public function __construct()
    {
        $this->_fields = $this->_getFields();
        $this->_facade = WooCommerceFacade::getInstance();
    } // end __construct
    
    private function _getFields()
    {
        return array(
            'name' => array(
                'type'     => static::FIELD_TYPE_SECURITY_STRING,
                'required' => true,
                'error'    => 'Undefined name in atributte' 
            ),
            'ident'        => static::FIELD_TYPE_SECURITY_STRING,
            'is_visible'   => static::FIELD_TYPE_INT,
            'is_variation' => static::FIELD_TYPE_INT,
            'index'        => static::FIELD_TYPE_INT
        );
    } // end _getFields

    public function sync($attributes)
    {
        $attributes = $this->_getPreparedAttributes($attributes);
        
        $existsAttributes = $this->_getExistsAttributes($attributes);
        
        $newAttributes = array_diff($this->_names, $existsAttributes);
        
        foreach ($newAttributes as $attributeName) {
            $item = $attributes[$attributeName];
            
            $values = array(
                'attribute_name'    => $item['ident'],
                'attribute_label'   => $item['name'],
                'attribute_type'    => 'text',       // think
                'attribute_orderby' => 'menu_order', // check
                'attribute_public'  => 0
            );
            
            $this->_facade->addAttribute($values);
        }
        
        return true;
    } // end sync
    
    private function _getExistsAttributes($attributes)
    {
        $this->_names = array();
        foreach ($attributes as $attribute) {
            $this->_names[$attribute['name']] = $attribute['name'];
        }
        
        $search = array(
            'attribute_label&IN' => $this->_names
        );
        
        $attributes = $this->_facade->getAttributes($search);
        $existsAttributes = array();
        foreach ($attributes as $attribute) {
            $label = $attribute['attribute_label'];
            $existsAttributes[$label] = $label;
        }
        
        return $existsAttributes;
    } // end _getExistsAttributes

    private function _getPreparedAttributes($attributes)
    {
        foreach ($attributes as &$attribute) {
            $errors = array();
            $attribute = $this->getPreparedData(
                $attribute, 
                $this->_fields, 
                $errors
            );

            if ($this->_hasNameInAttribute($attribute)) {
                $attribute['name'] = $this->_doDecodeHtmlEntitiesInAttribute(
                    $attribute['name']
                );
            }

            if ($errors) {
                $error = each($errors);
                throw new Exception($error['value']);
            }
        }
        unset($attribute);
        
        return $attributes;
    } // end _getPreparedAttributes

    private function _doDecodeHtmlEntitiesInAttribute($attributeName)
    {
        return html_entity_decode($attributeName, ENT_QUOTES);
    } // end _doDecodeHtmlEntitiesInAttribute

    private function _hasNameInAttribute($attribute)
    {
        return array_key_exists('name', $attribute) &&
               $attribute['name'];
    } // end _hasNameInAttribute
}