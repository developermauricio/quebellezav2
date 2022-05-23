<?php

/**
 * Trait FiltersProxy. General logic for a filter data into DGS Proxy.
 */
trait FiltersProxy
{
    /**
     * Returns conditions by field.
     *
     * @param AbstractField $field
     * @return array
     * @throws StoreException
     * @throws SystemException
     */
    protected function getFilterConditionsByField(AbstractField $field): ?array
    {
        $filterType = $field->get(AbstractField::OPTION_FILTER);
        $filterValue = $this->getFieldFilterValueInSession($field);

        if (!$filterType || is_null($filterValue) || !$field->getName()) {
            return null;
        }

        if ($field instanceof DatetimeField) {
            $filterValue = $this->_getDateTimeFilterValue($filterValue, $filterType);
        }

        if ($field instanceof CompositeField) {
            return $this->getFilterConditionsByCompositeField($filterValue, $field);
        }

        $columnName = $this->getColumnNameByFilter($field);

        return !$columnName ? null : $this->_getBaseFilterConditions($columnName, $filterValue, $field);
    } // end getFilterConditionsByField

    /**
     * Returns conditions by Composite field.
     *
     * @param $value
     * @param CompositeField $field
     * @return array
     * @throws SystemException
     */
    protected function getFilterConditionsByCompositeField($value, CompositeField $field): array
    {
        $options = $field->getOptions();

        $search = array(
            'sql_or' => array()
        );

        $value = "%".$value."%";

        foreach ($options as $column) {

            if (!empty($column[AbstractField::OPTION_STORE])) {
                $tableName = $column[AbstractField::OPTION_STORE];
            } else {
                $tableName = $this->store->getName();
            }

            $columnName = $tableName.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$column['value'];

            $search['sql_or'][][$columnName.'&LIKE'] = $value;
        }

        return $search;
    } // end getFilterConditionsByCompositeField

    /**
     * Returns column name by foreign key filter.
     *
     * @param AbstractField $field
     * @return string
     */
    protected function getForeignKeyFilterColumnName(AbstractField $field): string
    {
        $filterType = $field->get(AbstractField::OPTION_FILTER);
        $foreignTable = $field->get('foreignTable');

        $aliasTableName = $field->get('alias');

        if ($aliasTableName) {
            $foreignTable = $aliasTableName;
        }

        if (in_array($filterType, array('select', 'exact', 'multiple'))) {
            $foreignKeyField = $field->get('foreignKeyField');
            return $foreignTable.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$foreignKeyField;
        }

        return $foreignTable.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$field->get('foreignValueField');
    } // end getForeignKeyFilterColumnName

    /**
     * Returns filed filter value from session. If value not fount return NULL.
     *
     * @param AbstractField $field
     * @return mixed
     */
    protected function getFieldFilterValueInSession(AbstractField $field)
    {
        $filterName = $field->getFilterKey();
        $tableName = $this->store->getIdent();

        if (!$this->_hasFieldFilterCondition($tableName, $filterName)) {
            return null;
        }

        $this->session = &$this->store->getSession();

        $filtersStorage = &$this->session[Store::FILTERS_KEY_IN_SESSION];
        return $filtersStorage[$tableName][$filterName];
    } // end getFilterValueInSession

    /**
     * Returns formatted value of datetime filter.
     *
     * @param $filterValue
     * @param string $filterType
     * @return array|false|string
     */
    private function _getDateTimeFilterValue($filterValue, string $filterType)
    {
        if ($filterType == AbstractField::FILTER_TYPE_RANGE && is_array($filterValue)) {
            if (!empty($filterValue[0])) {
                $filterValue[0] = $this->connection->getDateTimeValue($filterValue[0]);
            }

            if (!empty($filterValue[1])) {
                $filterValue[1] = $this->connection->getDateTimeValue($filterValue[1]);
            }

        } else {
            $filterValue = $this->connection->getDateTimeValue($filterValue);
        }

        return $filterValue;
    } // end _getDateTimeFilterValue

    /**
     * Returns sql condition for filter by field
     *
     * @param string $columnName
     * @param string|array $value
     * @param AbstractField $field
     * @return array
     */
    private function _getBaseFilterConditions(string $columnName, $value, AbstractField $field): array
    {
        $filterType = $field->get(AbstractField::OPTION_FILTER);

        if (in_array($filterType, array(AbstractField::FILTER_TYPE_SELECT, AbstractField::FILTER_TYPE_EXACT))) {

            $search = null;
            if ($field instanceof CheckboxField && !$value) {
                $search = array($columnName.'&IS' => 'NULL');
            } else {
                $search = array($columnName => $value);
            }

            return $search;
        }

        if ($filterType == AbstractField::FILTER_TYPE_MULTIPLE && $value) {
            return array($columnName.'&IN' => $value);
        }

        $operation = '&LIKE';
        $currentValue = null;

        if (is_array($value)) {
            if (empty($value[0])) {
                $operation = '&<=';
                $currentValue = $value[1];
            } elseif (empty($value[1])) {
                $operation = '&>=';
                $currentValue = $value[0];
            } else {
                $operation = '&BETWEEN';
                $currentValue = $value;
            }
        } else if ($field instanceof DatetimeField) {
            $operation = '';
            $currentValue = $value;
        }

        if (!$currentValue) {
            $currentValue = "%".$value."%";
        }

        return array($columnName.$operation => $currentValue);
    } // end _getBaseFilterConditions

    /**
     * @param string $tableName
     * @param string $filterName
     * @return bool
     */
    private function _hasFieldFilterCondition(string $tableName, string $filterName): bool
    {
        $this->session = &$this->store->getSession();

        $filtersStorage = &$this->session[Store::FILTERS_KEY_IN_SESSION];
        return isset($filtersStorage[$tableName][$filterName])
            && $filtersStorage[$tableName][$filterName] !== "";
    } // end _hasFieldFilterCondition

    /**
     * Returns column name to use into conditions (WHERE).
     *
     * @param AbstractField $field
     * @return string
     * @throws StoreException
     */
    protected function getColumnNameByFilter(AbstractField $field): ?string
    {
        $fieldType = $field->getType();
        $methodName = 'get'.ucfirst($fieldType).'FilterColumnName';

        $method = array(&$this, $methodName);

        if (is_callable($method)) {
            $columnName = call_user_func_array($method, array($field));
        } else {
            $columnName = $this->getDefaultColumnNameByFilter($field);
        }

        return $columnName;
    } // end getColumnNameByFilter

    /**
     * Returns column name for filter.
     *
     * @param AbstractField $field
     * @return string|null
     */
    protected function getDefaultColumnNameByFilter(AbstractField $field): ?string
    {
        $columnName = $field->getName();

        $tableName = $field->get(AbstractField::OPTION_STORE);
        if (!$tableName) {
            $tableName = $this->store->getName();
        }

        // XXX: SqlField and Custom Fields
        if ($field->get(AbstractField::OPTION_EXPRESSION) || $field->isVirtualField()) {
            return null;
        }

        return $tableName.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$columnName;
    } // end _getDefaultColumnNameByFilter

    /**
     * Returns column name for text filter.
     *
     * @see getColumnNameByFilter
     * @param AbstractField $field
     * @return string
     */
    protected function getTextFilterColumnName(AbstractField $field): string
    {
        $columnName = $field->getName();

        $tableName = $field->get(AbstractField::OPTION_STORE);
        if (!$tableName) {
            $tableName = $this->store->getName();
        }

        $expression = $field->get(AbstractField::OPTION_EXPRESSION);
        if ($expression) {
            return $field->getName();
        }

        return $tableName.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$columnName;
    } // end getTextFilterColumnName
}