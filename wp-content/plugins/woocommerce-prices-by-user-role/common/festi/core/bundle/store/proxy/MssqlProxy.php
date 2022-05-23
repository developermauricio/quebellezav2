<?php

require_once 'bundle/store/proxy/sql/SqlProxy.php';

class MssqlProxy extends SqlProxy
{
    /**
     * @param bool $isAllColumns
     * @return array
     * @throws Exception
     */
    public function loadListValues(bool $isAllColumns = false): array
    {
        $columns = $this->getQueryColumns($isAllColumns);
        $columns = array_unique($columns);
        
        $joins   = $this->getQueryJoins($columns);
        $where   = $this->getQueryWhere();
        $having  = $this->getQueryHaving();
        $groupBy = $this->getQueryGroupBy();
        
        $columnsSection = join(', ', $columns);
        $fromSection    = join(' ', $joins);

        $orderBy = $this->getQueryOrderDirection();

        $sql = "SELECT ".$columnsSection.", ".
                        "ROW_NUMBER() OVER (ORDER BY ".$orderBy.") AS __row_num ".
                "FROM ".
                    $fromSection;
                    
        if ($where) {
            $sql .= " WHERE ".join(' AND ', $where);
        }
        
        if ($groupBy) {
            $sql .= $groupBy;
        }

        $limit = $this->getQueryLimit();
        $limit = explode(", ", $limit);
        $endLimit = $limit[0] + $limit[1];

        $having['t.__row_num&BETWEEN'] = array($limit[0], $endLimit);
        $havingStatements = $this->connection->getSqlCondition($having);
        
        $sqlWrapper = "SELECT ".
                            "t.* ".
                        "FROM ".
                            "(".$sql.") AS t ".
                        "WHERE 
                            ".join(' AND ', $havingStatements).
                        " ORDER BY ".
                            "t.__row_num";
        $rows = $this->connection->getAll($sqlWrapper);

        $sql = "SELECT COUNT(*) OVER () AS TotalRecords FROM ".$fromSection;
        
        if ($where) {
            $sql .= " WHERE ".join(' AND ', $where);
        }
        
        if ($groupBy) {
            $sql .= $groupBy;
        }

        $total = (int) $this->connection->getOne($sql);
        $this->setCount($total);
        
        return $rows;
    } // end loadListValues

    protected function getQueryOrderDirection(): ?string
    {
        $fieldName = $this->store->getOrderByFieldName();

        if (!$fieldName) {
            return $this->store->getName().static::STORE_NAME_AND_COLUMN_SEPARATOR.$this->store->getPrimaryKey();
        }

        $orderByDirection = $this->store->getOrderByDirection();

        $field = $this->model->getFieldByName($fieldName);
        if ($field) {
            $fieldName = $this->getColumnNameByOrderField($field);
        }

        if (!static::hasStoreName($fieldName)) {
            $fieldName = $this->store->getName().static::STORE_NAME_AND_COLUMN_SEPARATOR.$fieldName;
        }

        return $fieldName." ".$orderByDirection;
    }

    /**
     * @param AbstractField $field
     * @return string
     * @throws SystemException
     */
    protected function getMany2manyColumnName(AbstractField $field): string
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
            $where = ' AND '.$condition;
        }

        $foreignValueField = $field->get(ForeignKeyField::OPTION_FOREIGN_VALUES_FIELD);
        if (static::isExpression($foreignValueField)) {
            $valuesColumn = $foreignValueField;
        } else {
            $valuesColumn = "mt".static::STORE_NAME_AND_COLUMN_SEPARATOR.$foreignValueField;
        }
        
        $sql = "STUFF((SELECT ".
                    "'::' + (".$valuesColumn.")".
                " FROM ".
                    $field->get('foreignTable')." mt ".
                    "JOIN ".$field->get('linkTable')." lt ".
                    "on (mt.".$field->get('foreignKeyField').
                    " = lt.".$field->get('linkForeignField').
                ") WHERE ".
                    "lt.".$field->get('linkField')." = ".
                    $this->store->getName().".".$this->store->getPrimaryKey().
                    $where." FOR XML PATH(''), TYPE).value('.', 'NVARCHAR(MAX)'), 1, 2, '')";
        
        $fieldName = $field->getName();
        
        if (!$fieldName) {
            $fieldName = "__".$field->getIndex();
        }
        
        return "(".$sql.") as ".$fieldName;
    } // end getMany2manyColumnName

    protected function getQueryHavingByFieldFilter(AbstractField $field, $filterValue, bool $hasExpression): array
    {
        $fieldName = $field->getName();

        $having = array();
        if ($hasExpression) {
            if (is_array($filterValue)) {
                $having['t.'.$fieldName.'&BETWEEN'] = $filterValue;
            } else {
                $having['t.'.$fieldName] = $filterValue;
            }
        } else {
            $having['t.__'.$field->getIndex().'&IS NOT'] = 'NULL';
        }

        return $having;
    } // end getQueryHavingByFieldFilter

    public function createAuditTable($auditTableName, $originalTableName): bool
    {
        try {
            $sql = "SELECT COUNT(*) FROM ".$auditTableName;
            $this->connection->getRow($sql);
        } catch (\Exception $exp) {
            $sql = "SELECT * INTO ".$auditTableName." FROM ".$originalTableName." WHERE 1 = 2";

            $this->connection->query($sql);

            $sql = "ALTER TABLE ".$auditTableName." DROP COLUMN id;";
            $this->connection->query($sql);

            $queries = array(
                "ALTER TABLE ".$auditTableName." ADD __id int identity",
                "ALTER TABLE ".$auditTableName." ADD __action varchar(100) NULL",
                "ALTER TABLE ".$auditTableName." ADD __id_author int NULL",
                "ALTER TABLE ".$auditTableName." ADD __action_date datetime",
                "ALTER TABLE ".$auditTableName." ADD id int"
            );

            foreach ($queries as $query) {
                $this->connection->query($query);
            }
        }

        return true;
    } // end createAuditTable
}
