<?php

require_once 'bundle/store/proxy/sql/SqlProxy.php';

class PgsqlProxy extends SqlProxy
{
    const TOTAL_FIELD = 'total';
    
    private $_groupBy;

    /**
     * @param bool $isAllColumns
     * @return array
     * @throws Exception
     */
    public function loadListValues(bool $isAllColumns = false): array
    {
        $columns = $this->getQueryColumns($isAllColumns);
        $joins   = $this->getQueryJoins($columns);
        $where   = $this->getQueryWhere();
        $having  = $this->getQueryHaving();
        $groupby = $this->getQueryGroupBy();
        
        array_push($columns, 'COUNT(*) OVER() AS '.static::TOTAL_FIELD);
        
        $columnsSection = join(', ', $columns);
        $fromSection    = join(' ', $joins);
    
        $sql = "SELECT ".$columnsSection.
                " FROM ".$fromSection;
                
        if ($where) {
            $sql .= " WHERE ".join(' AND ', $where);
        }
        
        if ($groupby) {
            $sql .= $groupby;
        }
        
        if ($having) {
            $sql .= " HAVING ".join(' AND ', $having);
        }
        
        $orderBy = $this->getQueryOrderDirection();
        if ($orderBy) {
            $sql .= " ORDER BY ".$orderBy;
        }
        
        $limit = $this->getQueryLimit();
        list($offset, $limit) = explode(", ", $limit);
        
        if ($this->isUseLimit()) {
            $sql .= " LIMIT ".$limit." OFFSET ".$offset;
        }
        
        $rows = $this->connection->getAll($sql);
    
        $total = 0;
        
        if ($rows) {
            $row   = current($rows);
            $total = $row[static::TOTAL_FIELD];
        }
        
        $this->setCount($total);
        
        return $rows;
    } // end loadListValues
    
    /**
     * @param AbstractField $field
     * @return string
     */
    protected function getMany2manyColumnName(AbstractField $field): string
    {
        $fieldName = $field->getName();
        if (!$fieldName) {
            $fieldName = "__".$field->getIndex();
        }

        $sql = $this->_getManyToManyQuery($field);

        return "(".$sql.") AS ".$fieldName;
    } // end getMany2manyColumnName

    private function _getManyToManyQuery(AbstractField $field): string
    {
        $filterValue = $this->getFieldFilterValueInSession($field);

        $where = "";
        if ($filterValue) {

            $filterColumnName = $field->get('foreignKeyField');

            if ($field->isUniqueValues()) {
                $filterColumnName = $field->get('foreignValueField');
            }

            $search = array(
                "mt.".$filterColumnName."&IN" => $filterValue
            );

            $where = $this->connection->getSqlCondition($search);
            $where = " AND ".join(" ", $where);
        }

        $condition = $field->get('condition');
        if ($condition) {
            $where = " AND ".$condition;
        }

        $foreignTable = $this->connection->quoteTableName(
            $field->get('foreignTable')
        );

        $foreignValueField = $field->get(ForeignKeyField::OPTION_FOREIGN_VALUES_FIELD);
        if (static::isExpression($foreignValueField)) {
            $valuesColumn = $foreignValueField;
        } else {
            $valuesColumn = "mt".static::STORE_NAME_AND_COLUMN_SEPARATOR.$foreignValueField;
        }

        if ($field->isUniqueValues()) {
            $valuesColumn = "DISTINCT ".$valuesColumn;
        }

        $sql = "
            SELECT
                string_agg(".$valuesColumn.",'::')
            FROM ".$foreignTable." AS mt
            JOIN ".$field->get('linkTable')." AS lt
                ON (mt.".$field->get('foreignKeyField')." = lt.".$field->get('linkForeignField').")
            WHERE
                lt.".$field->get('linkField')." = ".$this->store->getName().".".$this->store->getPrimaryKey().
            $where;

        return $sql;
    } // end _getManyToManyQuery

    protected function getQueryHavingByFieldFilter(AbstractField $field, $filterValue, bool $hasExpression): array
    {
        $having = array();

        if ($hasExpression) {
            $expression = $field->get('expression');
            if (is_array($filterValue)) {
                $having['sql_and'] = array(
                    $expression.' BETWEEN '.$this->connection->quote($filterValue[0]).' AND '.
                    $this->connection->quote($filterValue[1])
                );
            } else {
                $having[$expression] = $filterValue;
            }
        } else {
            if ($field instanceof Many2manyField) {
                $sql = $this->_getManyToManyQuery($field);
                $having['('.$sql.')&IS NOT'] = 'NULL';
                // XXX: PostgreSQL specific
                $this->_groupBy = $this->store->getPrimaryKey();
            } else {
                $having['__'.$field->getIndex().'&IS NOT'] = 'NULL';
            }
        }

        return $having;
    } // end getQueryHavingByFieldFilter

    protected function getAggregateExpression(string $functionName, string $expression, string $aliasName): string
    {
        return $functionName."(".$expression."::float) as ".$aliasName;
    } // end getAggregateExpression

    public function createAuditTable($auditTableName, $originalTableName): bool
    {
        $savePointName = "__audit".$auditTableName;
        $this->connection->query("SAVEPOINT ".$savePointName);

        try {
            $sql = "SELECT 1 FROM ".$auditTableName." LIMIT 1";
            $this->connection->getRow($sql);
        } catch (\Exception $exp) {

            $this->connection->query("ROLLBACK TO ".$savePointName);

            $this->_createTable($auditTableName, $originalTableName);
        }

        return true;
    } // end createAuditTable

    private function _createTable(string $auditTableName, string $originalTableName): void
    {
        $sql = "SELECT                                          
                  'CREATE TABLE ".$auditTableName."' || E'\n(\n' ||
                  array_to_string(
                    array_agg(
                      '    ' || column_name || ' ' ||  type || ' '|| not_null
                    )
                    , E',\n'
                  ) || E'\n);\n'
                from
                (
                  SELECT 
                    c.relname, a.attname AS column_name,
                    pg_catalog.format_type(a.atttypid, a.atttypmod) as type,
                    case 
                      when a.attnotnull
                    then 'NOT NULL' 
                    else 'NULL' 
                    END as not_null 
                  FROM pg_class c,
                   pg_attribute a,
                   pg_type t
                   WHERE c.relname = ".$this->connection->quote($originalTableName)."
                   AND a.attnum > 0
                   AND a.attrelid = c.oid
                   AND a.atttypid = t.oid
                 ORDER BY a.attnum
                ) as tabledefinition
                group by relname";


        $createStatement = $this->connection->getOne($sql);

        $this->connection->query($createStatement);

        $sql = "ALTER TABLE ".$auditTableName."
                    ADD __id SERIAL,
                    ADD __action varchar(100) DEFAULT NULL,
                    ADD __id_author integer DEFAULT NULL,
                    ADD __action_date TIMESTAMP,
                    ADD PRIMARY KEY (__id);";

        $this->connection->query($sql);
    } // end _createTable

    /*
    protected function getCondition(
        string $columnName, string $operation, string $value, AbstractField $field
    ): string
    {
        if ($operation == "LIKE") {
            $columnName .= "::text";
        }

        return $columnName.' '.$operation.' '.$value;
    } // end getPreparedColumnName
    */

    /**
     * @return string|null
     */
    protected function getQueryGroupBy(): ?string
    {
        $groupBy = $this->model->get('groupBy');
        if (!$groupBy && !$this->_groupBy) {
            return null;
        }

        return " GROUP BY ".($groupBy ? $groupBy : $this->_groupBy);
    } // end getQueryGroupby
}
