<?php

require_once 'bundle/store/field/ForeignKeyField.php';

class Many2manyField extends ForeignKeyField
{
    const SEPARATOR = '::';
    const DEFAULT_VALUE = 1;
    const OPTION_LINK_TABLE = "linkTable";
    const OPTION_LINK_FOREIGN_FIELD = "linkForeignField";
    const OPTION_VALUES_ORDER_BY = "valuesOrderBy";

    /**
     * @var array
     */
    public $valuesList = array();
    public $extended = false;

    /**
     * @var array
     */
    protected $list = array();

    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        if (!$scheme->hasOptions()) {
            return false;
        }
        
        $options = $scheme->getOptions();
        foreach ($options as $item) {
            $this->valuesList[$item['id']] = $item['value'];
        }

        $this->extended = true;
    } // end onInit

    /**
     * @override
     */
    protected function getAttributesFields(): array
    {
        $fields = parent::getAttributesFields();

        unset($fields['name']);
        
        $fields[static::OPTION_LINK_TABLE] = array(
            'type'     => static::FIELD_TYPE_STRING,
            'error'    => 'Undefined linkTable in field',
            'required' => true
        );
        
        $fields['linkField'] = array(
            'type'     => static::FIELD_TYPE_STRING,
            'error'    => 'Undefined linkField in field',
            'required' => true
        );
        
        $fields[static::OPTION_LINK_FOREIGN_FIELD] = array(
            'type'     => static::FIELD_TYPE_STRING,
            'error'    => 'Undefined linkForeignField in field',
            'required' => true
        );
        
        return $fields;
    } // end getAttributesFields
    
    /**
     * Returns true if display unique values.
     *
     * @return boolean
     */
    public function isUniqueValues()
    {
        return $this->get('unique');
    } // end isUniqueValues
            
    protected function onFilterFetch()
    {
        $this->filterValues = $this->store->getProxy()->loadForeignValues(
            $this->getAttributes()
        );
    } // end onFilterFetch

    /**
     * @override
     * @param string|null $value
     * @param mixed $inline
     * @return string|null
     * @throws SystemException
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $this->attributes['extendedValue'] = $this->extended;

        $options = $this->getAttributes();

        if ($this->isCustomLoadValues()) {
            $options['onlySelectedValues'] = true;
        }

        $list = $this->store->getProxy()->loadForeignAssigns(
            $this->store->getPrimaryKeyValueFromRequest(),
            $options
        );

        if ($value && isset($list[$value])) {
            $list[$value]['checked'] = true;
        }

        $this->list = $list;
        
        return $this->fetch('edit.phtml');
    } // end getEditInput

    /**
     * Returns values for Edit form.
     *
     * @see Many2manyField::getEditInput()
     * @return array
     */
    public function getValues(): array
    {
        return $this->list;
    } // end getValues

    /**
     * @override
     */
    public function displayRO($value)
    {
        if (empty($value)) {
            return $value;
        }

        $this->values = explode(static::SEPARATOR, $value);
        array_walk($this->values, 'nl2br');

        return $this->fetch('cell_value.phtml');
    } // end displayRO

    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        $this->values = null;

        if (!empty($value)) {
            $this->values = explode(static::SEPARATOR, $value);
        }

        return $this->fetch('cell_value.phtml');
    } // end displayValue
    
    public function getValue($requests = array())
    {
        if ($this->isRequired() && !$this->_hasValuesInRequest($requests)) {
            $this->lastErrorMessage = __l(
                '%s is required field',
                $this->getCaption()
            );
            return false;
        }

        if ($this->store->getAction() == Store::ACTION_INSERT) {
            $type = Store::EVENT_INSERT;
        } else if ($this->store->getAction() == Store::ACTION_EDIT) {
            $type = Store::EVENT_UPDATE;
        } else {
            throw new FieldException("Undefined action for getValue method");
        }
        
        $this->store->addEventListener($type, array(&$this, 'onSave'));

        return true;
    } // end getValue

    private function _hasValuesInRequest(array $request): bool
    {
        $key = $this->getKeyInRequest();

        return array_key_exists($key, $request) && !empty($request[$key]);
    }

    /**
     * Update values handler.
     *
     * @param StoreActionEvent $event
     */
    public function onSave(StoreActionEvent $event)
    {
        $proxy = &$this->store->getProxy();

        $key = $this->getKeyInRequest();

        $values = array();

        // XXX: Move all values to DGS array and use $this->store->getRequestParam
        if (array_key_exists($key, $_POST)) {
            // XXX: Select2 3.x.x specific
            if (is_string($_POST[$key])) {
                $_POST[$key] = array_filter(explode(",", $_POST[$key]));
            }

            $values = $_POST[$key];

            if ($this->isMultiple()) {
                $values = $this->_getPreparedValuesForMultipleSelect($values);
            }
        }

        $proxy->updateManyToManyValues($this, $event->getPrimaryKeyValue(), $values);
    } // end onSave
    
    public function isShow()
    {
        $action = $this->store->getAction();

        if ($action != Store::ACTION_LIST) {
            return true;
        }
        
        if ($action == Store::ACTION_LIST && !$this->get('hide')) {
            return true;
        }
        
        return false;
    } // end isShow
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return $this->getBaseTemplatePath()."many2many".DIRECTORY_SEPARATOR;
    } // end getTemplatePath

    /**
     * Returns true when the multiple option enabled.
     *
     * @return bool
     */
    public function isMultiple(): bool
    {
        return $this->get("multiple") || $this->hasAutocomplete();
    } // end isMultiple
    
    /**
     * @param array $values
     * @return array
     */
    private function _getPreparedValuesForMultipleSelect(array $values): array
    {
        return array_fill_keys($values, static::DEFAULT_VALUE);
    } // end getPreparedValuesForMultipleSelect

    /**
     * @override
     * @return string
     */
    public function getKeyInRequest(): string
    {
        return 'm2m_'.$this->get(static::OPTION_LINK_TABLE);
    }

    /**
     * Returns link table name.
     *
     * @return string
     */
    public function getLinkTable(): string
    {
        return $this->get(static::OPTION_LINK_TABLE);
    }

    /**
     * Returns link column name.
     *
     * @return string
     */
    public function getLinkField(): string
    {
        return $this->get('linkField');
    }

    /**
     * Returns link foreign field.
     *
     * @return string
     */
    public function getLinkForeignField(): string
    {
        return $this->get(static::OPTION_LINK_FOREIGN_FIELD);
    }

} // end class many2manyFormField
