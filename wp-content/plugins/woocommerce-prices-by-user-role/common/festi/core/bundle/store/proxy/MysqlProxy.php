<?php

require_once 'bundle/store/proxy/sql/SqlProxy.php';

class MysqlProxy extends SqlProxy
{
    /**
     * @param bool $isAllColumns
     * @return array
     * @throws StoreException
     * @throws SystemException
     */
    public function loadListValues(bool $isAllColumns = false): array
    {
        $columns = $this->getQueryColumns($isAllColumns);
        $joins   = $this->getQueryJoins($columns);
        $where   = $this->getQueryWhere();
        $having  = $this->getQueryHaving();
        $groupby = $this->getQueryGroupBy();
        
        $columnsSection = join(', ', $columns);
        $fromSection    = join(' ', $joins);
    
        $sql = "SELECT SQL_CALC_FOUND_ROWS ".$columns[0].", ".$columnsSection.
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
        
        if ($this->isUseLimit()) {
            $limit = $this->getQueryLimit();
            $sql .= " LIMIT ".$limit;
        }

        $rows = $this->connection->getAll($sql);

        $total = (int) $this->connection->getOne('SELECT FOUND_ROWS()');
        $this->setCount($total);

        return $rows;
    } // end loadListValues
    
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
                'mt.'.$filterColumnName.'&IN' => $filterValue
            );
            
            $where = $this->connection->getSqlCondition($search);
            $where = ' AND '.join(' ', $where);
        }

        $condition = $field->get('condition');
        if ($condition) {
            $where = ' AND '.$condition;
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
            $valuesColumn = 'DISTINCT '.$valuesColumn;
        }
        
        $sql = "SELECT ".
                    "GROUP_CONCAT(".$valuesColumn." SEPARATOR '::')".
                " FROM ".
                    $foreignTable." mt ".
                    "JOIN ".$field->get('linkTable')." lt ".
                    "on (mt.".$field->get('foreignKeyField').
                    " = lt.".$field->get('linkForeignField').
                ") WHERE ".
                    "lt.".$field->get('linkField')." = ".
                    $this->store->getName().".".$this->store->getPrimaryKey().
                    $where;
        

        $fieldName = $field->getName();
        
        if (!$fieldName) {
            $fieldName = "__".$field->getIndex();
        }
        
        return "(".$sql.") as ".$fieldName;
    } // end getMany2manyColumnName
    
     public function removeByPrimaryKey($primaryKeyValue): int
     {
        $sql = 'DELETE FROM '.$this->store->getName().
               ' WHERE '.$this->store->getPrimaryKey(). ' = '.
               $this->connection->quote($primaryKeyValue).' LIMIT 1';
               
        return $this->connection->query($sql);
     } // end removeByPrimaryKey
    
    public function createAuditTable($auditTableName, $originalTableName): bool
    {
        try {
            $sql = "SELECT 1 FROM ".$auditTableName." LIMIT 1";
            $this->connection->getRow($sql);
        } catch (\Exception $exp) {
            $sql = "CREATE TABLE ".$auditTableName." LIKE ".$originalTableName;

            $this->connection->query($sql);

            $sql = "ALTER TABLE ".$auditTableName." DROP COLUMN id;";

            $this->connection->query($sql);

            $sql = "ALTER TABLE ".$auditTableName."
                    ADD __id int(11) unsigned NOT NULL AUTO_INCREMENT FIRST,
                    ADD __action varchar(100) DEFAULT NULL AFTER __id,
                    ADD __id_author int(11) unsigned DEFAULT NULL AFTER __action,
                    ADD __action_date datetime AFTER __id_author,
                    ADD id int(11) unsigned NOT NULL AFTER __action_date,
                    ADD PRIMARY KEY (__id);";

            $this->connection->query($sql);

            $this->_dropIndexes($auditTableName);
        }

        return true;
    } // end createAuditTable

    private function _dropIndexes($tableName)
    {
        $search = array(
            'Key_name&<>' => 'PRIMARY'
        );

        $where = $this->connection->getSqlCondition($search);
        $sql = 'SHOW INDEX FROM '.$tableName." WHERE ".join(" AND ", $where);
        $keys = $this->connection->getAll($sql);
        if (!$keys) {
            return;
        }

        foreach ($keys as $key) {
            $sql = 'ALTER TABLE `'.$tableName.
                '` DROP INDEX '.$key['Key_name'].';';

            $this->connection->query($sql);
        }
    }
}
