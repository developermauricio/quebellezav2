<?php

class SelectField extends AbstractField
{
    public $valuesList = array();

    /**
     * @override
     */
    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        $this->onInitOptions($scheme);
    } // end onInit

    /**
     * @override
     */
    protected function onInitOptions($scheme)
    {
        if (!$scheme->hasOptions()) {
            return false;
        }

        $options = $scheme->getOptions();
        
        foreach ($options as $item) {
            $this->valuesList[$item['id']] = $item['value'];
        }
        
    } // end onInitOptions


    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        if (!empty($this->attributes['readonly'])) {
            return $this->displayRO($value);
        }
        
        $this->value = $value;
        
        return $this->fetch('edit.phtml');
    }

    /**
     * @override
     */
    public function displayRO($value)
    {
        // FIXME:
        if (empty($value)) {
            $attrValue = $this->get('value');
            
            if ($attrValue !== false) {
                $value = $attrValue;
            }
        }
        
        if (empty($value)) {
            return "";
        }

        $value = $this->displayValue($value);
        
        return $value;
    }

    /**
     * @override
     */
    public function displayValue(?string $value, array $row = null): ?string
    {
        if (is_null($value)) {
            $value = 'NULL';
        }

        if (array_key_exists($value, $this->valuesList)) {
            $value = $this->valuesList[$value];
        } else {
            $value = "";
        }
        
        return $value;
    } // end displayValue
    
    protected function onFilterFetch()
    {
        $this->filterValues = $this->valuesList;
    } // end onFilterFetch
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."select/";
    } // end getTemplatePath
    
}