<?php

class ForeignKeyField extends AbstractField
{
    const OPTION_VALUES_WHERE = "valuesWhere";
    const OPTION_FOREIGN_KEY_FIELD = "foreignKeyField";
    const OPTION_FOREIGN_VALUES_FIELD = "foreignValueField";
    const OPTION_FOREIGN_TABLE = "foreignTable";

    // FIXME: Remove public from $keyData
    /**
     * @var array
     */
    public $keyData = array();

    /**
     * Set list of values.
     *
     * @param $values
     */
    public function setValuesList($values): void
    {
        $this->keyData = $values;
    } // end setValuesList

    /**
     * Returns list of values.
     *
     * @return array
     */
    public function getValuesList(): array
    {
        return $this->keyData;
    } // end getValuesList

    public function isForeignKey(): bool
    {
        return true;
    }

    /**
     * Returns true if we have to ignore a default logic to load values.
     *
     * @override
     * @return bool
     */
    public function isCustomLoadValues(): bool
    {
        return $this->hasAutocomplete();
    } // end isCustomLoadValues

    /**
     * @override
     */
    public function getEditInput(?string $value = '', $rowData = null): ?string
    {
        $this->foreignValue = $value;
        if ($rowData) {
            $this->foreignValue = $rowData['_foreign_'.$this->getName()];
        }

        if (!empty($this->attributes['readonly'])) {
            return $this->displayRO($value);
        }

        if ($this->get('ajaxChild') && $rowData) {
            $this->ajaxChildValues = $this->_getAjaxChildValues($rowData);
        }

        $this->value = $value;

        return $this->fetch('edit.phtml');
    } // end getEditInput

    /**
     * @param array $rowData
     * @return array
     */
    private function _getAjaxChildValues(array $rowData): array
    {
        $childFields = explode('|', $this->get('ajaxChild'));

        $ajaxChildValues = array();
        foreach ($childFields as $childField) {
            $ajaxChildValues[$childField] = $rowData['_foreign_'.$childField] ?? null;
        }

        return $ajaxChildValues;
    }

    /**
     * @param string $value
     * @return string
     */
    public function displayRO($value)
    {
        return $value;
    }

    /**
     * Returns foreign store name.
     *
     * @return string
     */
    public function getForeignStoreName(): string
    {
        $foreignTableName = $this->get(static::OPTION_FOREIGN_TABLE);

        if (preg_match("/\sas\s(.+)$/", $foreignTableName, $tmp)) {
            $foreignTableName = $tmp[1];
        }

        return $foreignTableName;
    } // end getForeignStoreName

    /**
     * Returns key for foreign field.
     *
     * @return string
     */
    public function getForeignFieldKey(): string
    {
        return $this->get(static::OPTION_FOREIGN_KEY_FIELD);
    } // end getForeignFieldKey

    /**
     * Returns value for foreign field.
     *
     * @return string
     */
    public function getForeignFieldValue(): string
    {
        return $this->get(static::OPTION_FOREIGN_VALUES_FIELD);
    } // end getForeignFieldValue
    
    public function getFormattedFilterValue($value)
    {
        $filterType  = $this->getFilterType();
     
        if ($this->_isMultipleFilter($filterType, $value)) {
            $value = $this->_getMultipleFilterValue($value);
        }
        
        if ($this->_isSelectFilter($filterType, $value)) {
            $value = $this->_getSelectFilterValue($value);
        }
        
        return parent::getFormattedFilterValue($value);
    } // end getFormattedFilterValue
    
    protected function onFilterFetch()
    {
        $values = array();
        
        $this->store->loadForeignKeys();
        $values = $this->keyData;

        $this->filterValues = $values;
    } // end onFilterFetch
    
    /**
     * @override
     */
    protected function getAttributesFields(): array
    {
        $fields = parent::getAttributesFields();

        $fields[static::OPTION_FOREIGN_TABLE] = array(
            'type'     => static::FIELD_TYPE_STRING,
            'error'    => 'Undefined foreignTable attribute in field',
            'required' => true
        );

        $fields[static::OPTION_FOREIGN_KEY_FIELD] = array(
            'type'     => static::FIELD_TYPE_STRING,
            'error'    => 'Undefined foreignKeyField attribute in field',
            'required' => true
        );

        $fields[static::OPTION_FOREIGN_VALUES_FIELD] = array(
            'type'     => static::FIELD_TYPE_STRING,
            'error'    => 'Undefined foreignValueField attribute in field',
            'required' => true
        );

        $fields[static::OPTION_VALUES_WHERE] = static::FIELD_TYPE_STRING_NULL;
        $fields['foreignOrderBy'] = static::FIELD_TYPE_STRING_NULL;
        $fields['foreignLimit'] = static::FIELD_TYPE_STRING_NULL;
        $fields['unique'] = static::FIELD_TYPE_BOOL;

        return $fields;
    } // end getAttributesFields
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."foreign_key/";
    } // end getTemplatePath

    private function _getMultipleFilterValue($value)
    {
        $filterValue = array();
    
        foreach ($value as $item) {
            if (!array_key_exists($item, $this->filterValues)) {
                continue;
            }
            $filterValue[] = $this->filterValues[$item];
        }
        
        if (!$filterValue) {
            $this->setErrorMessage("Can't find filter value.");
        }
        
        return $filterValue;
    } // end _getMultipleFilterValue
    
    private function _getSelectFilterValue($value)
    {
        if (!array_key_exists($value, $this->filterValues)) {
            $this->setErrorMessage("Can't find filter value.");
            return false;
        }
        
        return $this->filterValues[$value];
    } // end _getSelectFilterValue
    
    private function _isMultipleFilter($filterType, $value)
    {
        return $filterType == static::FILTER_TYPE_MULTIPLE &&
               is_array($value) &&
               $value;
    } // end _isMultipleFilter
    
    private function _isSelectFilter($filterType, $value)
    {
        return $filterType == static::FILTER_TYPE_SELECT &&
               is_scalar($value) &&
               $value;
    } // end _isSelectFilter
}
