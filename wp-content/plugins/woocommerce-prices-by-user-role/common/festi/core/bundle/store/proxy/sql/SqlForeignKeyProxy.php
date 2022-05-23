<?php

require_once 'bundle/store/field/ForeignKeyField.php';
require_once 'bundle/store/field/Many2manyField.php';

trait SqlForeignKeyProxy
{
    use ForeignKeyProxy;

    /**
     * @override
     * @param array $options
     * @return mixed
     */
    public function loadForeignValues(array $options): array
    {
        $query = new core\dao\Query($this->connection);

        $search = array();

        if (!empty($options['ajaxParentColumn'])) {
            $search[$options['ajaxParentColumn']] = $options['ajaxParentValue'];
        }

        if (!empty($options[ForeignKeyField::OPTION_VALUES_WHERE])) {
            $search[] = $options[ForeignKeyField::OPTION_VALUES_WHERE];
        }

        if (!empty($options['search'])) {
            $search = array_merge_recursive($search, $options['search']);
        }

        $keyFiledName = $options[ForeignKeyField::OPTION_FOREIGN_KEY_FIELD];
        $keyFiledNameAlias = null;
        $valueFieldName = $options[ForeignKeyField::OPTION_FOREIGN_VALUES_FIELD];

        if (!empty($options['unique'])) {
            $keyFiledNameAlias = $keyFiledName;
            $keyFiledName = $valueFieldName;
        }

        $query->column($keyFiledName, $keyFiledNameAlias)
              ->column($valueFieldName)
              ->from($options[ForeignKeyField::OPTION_FOREIGN_TABLE]);

        if ($search) {
            $this->prepareScopeConditionValues($search);
            $query->where($search);
        }

        if (!empty($options['unique'])) {
            $query->groupBy($valueFieldName);
        }

        if (empty($options['foreignOrderBy'])) {
            $query->orderBy($valueFieldName." ASC");
        } else {
            $query->orderBy($options['foreignOrderBy']);
        }

        if (!empty($options['foreignLimit'])) {
            $query->limit((int) $options['foreignLimit']);
        }

        $sql = $query->getQuery();

        return $this->connection->getAssoc($sql);
    } // end loadForeignValues

    private function _loadSelectedForeignAssigns(
        ?string $primaryValue,
        string $foreignTable,
        string $foreignKeyField,
        string $sqlForeignValueField,
        array $options
    ): array
    {
        if (empty($options['extendedValue'])) {
            $extendedValue = '0 as myvalue';
        } else {
            $extendedValue = $options[Many2manyField::OPTION_LINK_TABLE].
                             StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.'value as myvalue';
        }

        $columnName = $options[Many2manyField::OPTION_LINK_TABLE].
                      StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$options['linkField'];
        if (is_null($primaryValue)) {
            $where = $columnName." IS NULL";
        } else {
            $where = $columnName."=".$primaryValue;
        }

        $sql = "SELECT 
                    $foreignTable.$foreignKeyField, 
                    $sqlForeignValueField as value_field, $extendedValue
                FROM ".$options[Many2manyField::OPTION_LINK_TABLE].", ".$foreignTable."
                WHERE ".$where."
                AND ".$options[Many2manyField::OPTION_LINK_TABLE].StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.
                    $options[Many2manyField::OPTION_LINK_FOREIGN_FIELD]." = ".$foreignTable.
                    StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$options[Many2manyField::OPTION_FOREIGN_KEY_FIELD];

        $rows = $this->connection->getAll($sql);

        // XXX: PHP 7+ Fix DAO lib
        if (!$rows) {
            $rows = array();
        }

        $selectedValues = array();

        foreach ($rows as $row) {

            $value = true;
            if (!empty($options['extendedValue'])) {
                $value = $row['myvalue'];
            }

            $selectedValues[$row[$foreignKeyField]] = array(
                'value'   => $row['value_field'],
                'checked' => $value
            );
        }

        return $selectedValues;
    } // end _loadSelectedForeignAssigns

    /**
     * @param $primaryValue
     * @param array $options
     * @return array
     */
    public function loadForeignAssigns($primaryValue, array $options): array
    {
        $foreignTable = $this->connection->quoteTableName($options[Many2manyField::OPTION_FOREIGN_TABLE]);
        $foreignKeyField = $options[Many2manyField::OPTION_FOREIGN_KEY_FIELD];
        $foreignValueField = $options['foreignValueField'];

        if (StoreProxy::isExpression($foreignValueField)) {
            // XXX: Aggregation expression
            $sqlForeignValueField = $foreignValueField;
        } else {
            $sqlForeignValueField = $foreignTable.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$foreignValueField;
        }

        $selectedValues = $this->_loadSelectedForeignAssigns(
            $primaryValue,
            $foreignTable,
            $foreignKeyField,
            $sqlForeignValueField,
            $options
        );

        // XXX: Use when we use ajax load values
        $onlySelectedValuesOptionKey = 'onlySelectedValues';
        if (array_key_exists($onlySelectedValuesOptionKey, $options) && $options[$onlySelectedValuesOptionKey]) {
            return $selectedValues;
        }

        $values = array();

        $sql = "SELECT $foreignTable.$foreignKeyField, $sqlForeignValueField as value_field
                FROM ".$foreignTable;


        $sql .= " WHERE 1=1 ";

        if (!empty($options[Many2manyField::OPTION_VALUES_WHERE])) {
            $this->prepareScopeConditionValues($options[Many2manyField::OPTION_VALUES_WHERE]);
            $sql .= ' AND '.$options[Many2manyField::OPTION_VALUES_WHERE];
        }

        $sql .= " ORDER BY ";
        if (!empty($options[Many2manyField::OPTION_VALUES_ORDER_BY])) {
            $sql .= $options[Many2manyField::OPTION_VALUES_ORDER_BY];
        } else {
            $sql .= $sqlForeignValueField." ASC";
        }

        // SQL for all values
        $rows = $this->connection->getAll($sql);
        foreach ($rows as $row) {
            $values[$row[$foreignKeyField]] = array(
                'value' => $row['value_field']
            );
        }

        foreach ($selectedValues as $key => $currentRow) {
            $values[$key] = $currentRow;
        }

        return $values;
    } // end loadForeignAssigns

    /**
     * Load values form foreignKey fields. Result saved to $field->keyData.
     *
     * @param ForeignKeyField $field
     * @return bool
     */
    public function loadForeignKeyValues(ForeignKeyField &$field): bool
    {
        $keyField = $field->getForeignFieldKey();
        $valueField = $field->getForeignFieldValue();

        $joinType = $field->get('join');
        $joinSQL = !empty($joinType) && $joinType != 'true' ? $joinType : '';

        $joinType = $field->get('joinWhere');
        if ( !empty($joinType) && ($joinType != 'true')) {
            $joinSQL = $joinType;
        }

        $table = $field->get(ForeignKeyField::OPTION_FOREIGN_TABLE);

        $extendJoin = $field->get('extendJoin');
        if ($extendJoin) {
            $joinSQL = $extendJoin;
        }

        if (preg_match("/\sas\s(.+)$/", $table, $tmp)) {
            $table = $tmp[1];
        }

        $keyColumn = $this->_getColumnNameFromForeignKeyField($keyField, $table);
        $valueColumn = $this->_getColumnNameFromForeignKeyField($valueField, $table);

        $tableName = $field->get(ForeignKeyField::OPTION_FOREIGN_TABLE);
        $where = array();
        $orderBy = $valueField;

        if ($field->get(ForeignKeyField::OPTION_VALUES_WHERE)) {
            $where[] = $field->get(ForeignKeyField::OPTION_VALUES_WHERE);
        } elseif ($field->get(AbstractField::OPTION_WHERE)) {
            $where[] = $field->get(AbstractField::OPTION_WHERE);
        }

        if ($order = $field->get('orderBy')) {
            $orderBy = $order;
        }

        $sqlInfo = array(
            'columns' => array(
                'key'   => &$keyColumn,
                'value' => &$valueColumn
            ),
            'table' => &$tableName,
            'join'  => &$joinSQL,
            'where' => &$where,
            'order' => &$orderBy,
            'what'  => $this->store->getAction(),
            'field' => &$field,
            'values' => null
        );

        $this->firePrepareStoreForeignKeyFieldValuesCallback($this->store, $field, $sqlInfo);

        if (!is_null($sqlInfo['values'])) {
            $field->setValuesList($sqlInfo['values']);
            return true;
        }

        $tableName = $this->connection->quoteTableName($tableName);

        $sql = 'SELECT '.$keyColumn.', '.$valueColumn.' AS capt 
                FROM '.$tableName.' '.$joinSQL;

        if ($where) {
            $sql .= ' WHERE '.$this->getConcatCondition($where);
        }

        $sql .= " ORDER BY ".$orderBy;

        $result = $this->connection->getAssoc($sql);

        $field->setValuesList($result);

        return true;
    } // end loadForeignKeyValues

    /**
     * @param string $field
     * @param string $table
     * @param string|null $as
     * @return string
     */
    private function _getColumnNameFromForeignKeyField(
        string $field, string $table, string $as = null
    ): string
    {
        $value = null;

        $matches = array();
        if (StoreProxy::isComplexField($field, $matches)) {
            $value = $matches[1] ?? null;
        } else if (StoreProxy::isExpression($field)) {
            $value = $field;
        } else {
            $columnName = $field;
            $columnInfo = explode(StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR, $field);

            if (isset($columnInfo[1])) {
                $columnName = $columnInfo[1];
            }

            $value = $table.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$columnName;
        }

        if ($as) {
            $value .= ' as '.$as;
        }

        return $value;
    } // end _getColumnNameFromForeignKeyField

    /**
     * Fill value into condition value like S%id_param%, G%is_check%. S - Session, G - Global, C - DGS Search
     *
     * @param $value
     */
    protected function prepareScopeConditionValues(&$value): void
    {
        if (is_scalar($value)) {
            $values = array(&$value);
        } else {
            $values = &$value;
        }

        foreach ($values as &$row) {
            if (is_string($row)) {
                $this->_prepareScopeConditionValue($row);
            }
        }
    } // end prepareScopeConditionValues

    protected function getConcatCondition(array $condition): string
    {
        return join(' AND ', $condition);
    } // end getConcatCondition

    /**
     * @param string $condition
     */
    private function _prepareScopeConditionValue(string &$condition): void
    {
        $search = $this->store->getModel()->getSearch();

        preg_match_all("/(?<scope>S|G|C)%(?<key>.+)%/Umis", $condition, $matches);

        foreach ($matches['key'] as $index => $key) {
            $scope = $matches['scope'][$index];

            $value = null;
            if ($scope == "G") {
                $value = $GLOBALS[$key] ?? null;
            } else if ($scope == "S") {
                $value = $_SESSION[$key] ?? null;
            } else if ($scope == "C") {
                $value = $search[$key] ?? null;
            }

            if (is_null($value)) {
                $pattern = "=".$scope."%".$key."%";
                $value = " IS NULL";
            } else {
                $pattern = $scope."%".$key."%";
                $value = $this->connection->quote($value);
            }

            $condition = str_replace($pattern, $value, $condition);
        }
    } // end _prepareScopeConditionValue

}