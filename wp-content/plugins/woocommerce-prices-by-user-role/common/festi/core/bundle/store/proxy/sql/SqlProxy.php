<?php

require_once 'bundle/store/proxy/sql/SqlColumnNameProxy.php';
require_once 'bundle/store/proxy/sql/SqlProxyRepository.php';
require_once 'bundle/store/proxy/sql/SqlForeignKeyProxy.php';
require_once 'bundle/store/proxy/sql/SqlJoinProxy.php';


/**
 * Class SqlProxy. Abstract class describe adapter between DGS and relational database.
 */
abstract class SqlProxy extends StoreProxy
{
    use SqlColumnNameProxy, SqlProxyRepository, SqlForeignKeyProxy, SqlJoinProxy;

    /**
     * @override
     * @param $primaryKeyValue
     * @return bool
     */
    public function removeAllManyToManyValuesByPrimaryKey($primaryKeyValue): bool
    {
        foreach ($this->model->getFields() as $field) {
            if ($field->getType() != 'many2many') {
                continue;
            }

            assert($field instanceof Many2manyField);

            $sql = "DELETE FROM ".$field->getLinkTable()." WHERE ".$field->getLinkField()." = ".
                   $this->connection->quote($primaryKeyValue);
                   
            $this->connection->query($sql);
        }
        
        return true;
    } // end removeAllManyToManyValuesByPrimaryKey

    /**
     * @return string|null
     */
    protected function getQueryGroupBy(): ?string
    {
        $groupBy = $this->model->get('groupBy');
        if (!$groupBy) {
            return null;
        }

        // XXX: Column 'X' is invalid in the select list because it is not contained in either an aggregate function
        // or the GROUP BY clause.
        $orderByFieldName = $this->store->getOrderByFieldName();
        if ($orderByFieldName) {
            if (strpos($groupBy, $orderByFieldName) === false) {
                $groupBy .= ", ".$orderByFieldName;
            }
        }

        return " GROUP BY ".$groupBy;
    } // end getQueryGroupBy
    
    /**
     * @return string
     */
    protected function getQueryLimit()
    {
        $rowsPerPage = $this->store->getRowsPerPageCount();
        
        $pageIndex = $this->store->getCurrentPageIndex();
        
        $startLimit = ($pageIndex - 1) * $rowsPerPage;

        return $startLimit.", ".$rowsPerPage;
    } // end getQueryLimit
    
    /**
     * @return array
     * @throws SystemException
     */
    public function getQueryWhere(): array
    {
        $search = $this->model->getSearch();
        $where = $this->connection->getSqlCondition($search);
        
        foreach ($this->model->getFields() as $field) {
            $condition = $field->getQueryWhere();
            if ($condition) {
                $where[] = $condition;
            }
            
            $filterCondition = $this->getFilterConditionsByField($field);
            if ($filterCondition) {
                $filterCondition = $this->connection->getSqlCondition($filterCondition);
                $where = array_merge($where, $filterCondition);
            }
        }

        $this->_prepareWhereByFilters($where);
        
        $additional = $this->model->get('additionalWhere');
        if ($additional) {
            $where[] = $additional;
        }
        
        //
        $parentCondition = $this->_getQueryParentWhere();
        if ($parentCondition) {
            $where[] = $parentCondition;
        }
        
        return $where;
    } // end _getQueryWhere
    
    /**
     * Returns array of conditions to HAVING.
     * 
     * @return array
     */
    protected function getQueryHaving(): array
    {
        $having = array();
        foreach ($this->model->getFields() as $field) {
            $fieldHaving = $this->getQueryHavingByField($field);
            if ($fieldHaving) {
                $having = array_merge_recursive($having, $fieldHaving);
            }
        }
        
        if ($having) {
            $having = $this->connection->getSqlCondition($having);
        }
        
        return $having;
    } // end getQueryHaving

    protected function getQueryHavingByField(AbstractField $field): ?array
    {
        $fieldName = $field->getName();
        $hasExpression = ((bool) $field->get(AbstractField::OPTION_EXPRESSION)) || $field->isVirtualField();

        if (!$hasExpression && $fieldName) {
            return null;
        }

        $filterValue = $this->getFieldFilterValueInSession($field);

        $having = null;
        if ($filterValue) {
            $having = $this->getQueryHavingByFieldFilter($field, $filterValue, $hasExpression);
        }

        return $having;
    } // end getQueryHavingByField

    protected function getQueryHavingByFieldFilter(AbstractField $field, $filterValue, bool $hasExpression): array
    {
        $fieldName = $field->getName();

        $having = array();
        if ($hasExpression) {
            if (is_array($filterValue)) {
                $having[$fieldName.'&BETWEEN'] = $filterValue;
            } else {
                $having[$fieldName] = $filterValue;
            }
        } else {
            $having['__'.$field->getIndex().'&IS NOT'] = 'NULL';
        }

        return $having;
    } // end getQueryHavingByFieldFilter

    /**
     * Prepare sql condition based on tag filters in xml
     *
     * @param array $where
     * @return boolean
     * @throws StoreException
     */
    private function _prepareWhereByFilters(array &$where): bool
    {
        $filters = $this->model->getFilters();
        if (empty($filters)) {
            return false;
        }
        
        $tblName = $this->store->getName();

        foreach ($filters as $field => $value) {

            if (preg_match("/^S%(?<key>.+)%$/Umis", $value, $match)) {
                if (array_key_exists($match['key'], $this->session)) {
                    $value = $this->session[$match['key']];
                } else {
                    $value = 'NULL';
                }
            }

            if (!$value) {
                throw new StoreException("Undefined ".$field." filter value");
            }

            if ($value == 'NULL') {
                $where[] = $tblName.static::STORE_NAME_AND_COLUMN_SEPARATOR.$field." IS NULL";
                continue;
            }

            $inValues = array_filter(explode(",", $value));
            foreach ($inValues as &$item) {
                $item = $this->connection->quote(trim($item));
            }
            unset($item);
            $value = join(", ", $inValues);

            $where[] = $tblName.static::STORE_NAME_AND_COLUMN_SEPARATOR.$field." IN (".$value.")";
        }

        return true;
    } //end _prepareWhereByFilters
    
    /**
     * @return string|null
     */
    private function _getQueryParentWhere(): ?string
    {
        $parentAction = $this->model->getAction("parent");
        if (!$parentAction) {
            return null;
        }
        
        $relations =  $this->model->getRelation('parent');
        $relation = $relations[$parentAction['relation']];
        
        $parentValue = $this->store->getParentValue();

        $tableName = $this->store->getName();
        
        $parentColumnName = $tableName.static::STORE_NAME_AND_COLUMN_SEPARATOR.$relation['field'];
        
        if ($parentValue) {
            return $parentColumnName." = ".$this->connection->quote($parentValue);
        }
        
        return $parentColumnName." IS NULL";
    } // end _getQueryParentWhere

    /**
     * Returns join conditions.
     *
     * @param array $columns
     * @return array
     */
    public function getQueryJoins(array $columns): array
    {
        $stores = array();
        $joins = array();
        
        $fromTable = $this->getOriginalStoreName();
        
        $stores[$fromTable] = $fromTable;
        
        $joins[] = $fromTable;
        
        $generalJoin = $this->model->get('join');
        if ($generalJoin) {
            $joins[] = $generalJoin;
        }
        
        foreach ($this->model->getFields() as $field) {
            $this->_prepareQueryJoinsByField($field, $stores, $joins);
        }
        
        $routers = $this->model->getRouters();
        if ($routers) {
            $this->_prepareQueryJoinsByRouters($fromTable, $routers, $columns, $stores, $joins);
        }
        
        return $joins;
    } // end _getQueryJoins

    private function _prepareQueryJoinsByField(AbstractField $field, array &$stores, array &$joins): void
    {
        $fieldType = $field->getType();

        $methodName = 'get'.ucfirst($fieldType).'Join';
        $method = array(&$this, $methodName);

        if (is_callable($method)) {
            $joinData = call_user_func_array($method, array($field));
            list($storeName, $join) = $joinData;
            if (is_array($storeName)) {
                $stores += $storeName;
            } else {
                $stores[$storeName] = $storeName;
            }
        } else {
            $join = $field->get('join');
        }

        if (is_array($join)) {
            $joins = array_merge($joins, $join);
        } else if ($join) {
            $joins[] = $join;
        }

    } // end _prepareQueryJoinsByField

    private function _prepareQueryJoinsByRouters(
        string $fromTable, array $routers, array $columns, array &$stores, array &$joins
    ): void
    {

        foreach ($columns as $columnName) {

            if (static::isExpression($columnName)) {
                continue;
            }

            list($storeName) = explode(static::STORE_NAME_AND_COLUMN_SEPARATOR, $columnName);

            if (array_key_exists($storeName, $stores)) {
                continue;
            }

            $stores[$storeName] = $storeName;

            $join = $this->doSqlJoin($fromTable, $storeName, $routers);

            if (!is_null($join)) {
                $joins += $join;
            }
        }

    } // end _prepareQueryJoinsByRouters

    /**
     * @override
     * @param bool $isAllColumns
     * @return array
     * @throws StoreException
     */
    public function getQueryColumns(bool $isAllColumns = false): array
    {
        $storeName = $this->store->getName();
        $primaryKey = $this->store->getPrimaryKey();

        $fields = array();

        $fields[] = $storeName.static::STORE_NAME_AND_COLUMN_SEPARATOR.$primaryKey;

        $columns = $this->_getQueryColumnsFromFields($isAllColumns);
        $fields = array_merge($fields, $columns);
        $parentColumnName = $this->store->getParentFieldName();
        if ($parentColumnName) {
            $fields[] = $storeName.static::STORE_NAME_AND_COLUMN_SEPARATOR.$parentColumnName;
        }

        return $fields;
    } // end _getQueryColumns

    /**
     * @param bool $isAllColumns
     * @return array
     * @throws StoreException
     */
    private function _getQueryColumnsFromFields(bool $isAllColumns = false): array
    {
        $fields = array();
        foreach ($this->model->getFields() as $field) {

            if ($this->isHiddenColumnInList($field, $isAllColumns)) {
                continue;
            }

            $columnName = $this->getColumnNameByField($field, 'ColumnName');

            if (is_array($columnName)) {
                $fields = array_merge($fields, $columnName);
            } else if ($columnName) {
                $fields[] = $columnName;
            }
        }

        return $fields;
    } // end _getQueryColumnsFromFields

    /**
     * @override
     * @param Many2manyField $item
     * @param int $id
     * @param array $values
     * @return bool
     */
    public function updateManyToManyValues(Many2manyField $item, int $id, array $values): bool
    {
        $sql = sprintf(
            "DELETE FROM %s WHERE %s = %s", 
            $item->getLinkTable(),
            $item->getLinkField(),
            $this->connection->quote($id)
        );

        $this->connection->query($sql);


        foreach ($values as $key => $value) {
            $itemValues = array(
                $item->getLinkField() => $id,
                $item->getLinkForeignField() => $key
            );

            if (is_array($value)) {
                $itemValues['value'] = array_sum($value);
            }

            $sql = sprintf(
                "INSERT INTO %s (%s) VALUES ('%s')",
                $item->getLinkTable(),
                implode(', ', array_keys($itemValues)),
                implode("', '", $itemValues)
            );

            $this->connection->query($sql);
        }

        return true;
    } // end updateManyToManyValues
    
    /**
     * @return array
     * @throws StoreException|SystemException
     */
    public function loadAggregations(): array
    {
        $aggregations = $this->model->getAggregations();

        $columns = array();
        foreach ($aggregations as $data) {
            if ($data['type'] == 'custom') {
                continue;
            }

            $fieldName = $data['field'];

            $field = $this->model->getField($fieldName);

            $columnName = $this->getColumnNameByField($field, 'ColumnName');

            $aliasName = $this->connection->quoteColumnName($fieldName);
            $column = "";
            switch ($data['type']) {
                case 'sum':
                    $column = $this->getAggregateExpression("SUM", $columnName, $aliasName);
                    break;
                case 'avg':
                    $column = $this->getAggregateExpression("AVG", $columnName, $aliasName);
                    break;
                default:
                    throw new StoreException("Undefined aggregation type");
            }

            $columns[] = $column;
        }

        if (empty($columns)) {
            return $columns;
        }

        $joins = $this->getQueryJoins($this->getQueryColumns());
        $where = $this->getQueryWhere();
        $from  = join(' ', $joins);

        $sql = "SELECT ".join(", ", $columns)." FROM ".$from;

        if ($where) {
            $sql .= " WHERE ".$this->getConcatCondition($where);
        }

        return $this->connection->getRow($sql);
    } // end loadAggregations

    protected function getAggregateExpression(string $functionName, string $expression, string $aliasName): string
    {
        return $functionName."(".$expression.") as ".$aliasName;
    } // end getAggregateExpression
    
    abstract public function createAuditTable($auditTableName, $originalTableName): bool;
}
