<?php

trait SqlJoinProxy
{
    use BaseStoreProxy;

    /**
     * @param $sqlTable
     * @param $needleJoinSqlTable
     * @param $joinRules
     * @param $tablesRelations
     * @return mixed
     * @throws Exception
     */
    public function getSqlJoinForRelationTypeJoin(
        $sqlTable, $needleJoinSqlTable, $joinRules, &$tablesRelations
    )
    {
        $foreignSqlTable = $joinRules['value'];

        $foreignJoins = $this->doSqlJoin(
            $sqlTable,
            $foreignSqlTable,
            $tablesRelations
        );
        $needleJoins = $this->doSqlJoin(
            $foreignSqlTable,
            $needleJoinSqlTable,
            $tablesRelations
        );

        $joins = $foreignJoins + $needleJoins;

        return $joins;
    } // end getSqlJoinForRelationTypeJoin

    /**
     * @param AbstractField $field
     * @return array
     * @throws SystemException
     */
    protected function getForeignKeyJoin(AbstractField $field): array
    {
        $joinType = $field->get('join');
        if (!$joinType || $joinType == "true") {
            $joinType = "LEFT";
        }

        $tableName = $field->get('store');
        if (!$tableName) {
            $tableName = $this->store->getName();
        }
        
        $foreignTableName = $field->getForeignStoreName();
        $foreignKeyField = $field->get('foreignKeyField');

        $aliasTableName = $field->get('alias');

        if ($aliasTableName) {
            $foreignTableNameAlias = $aliasTableName;
        } else {
            $foreignTableNameAlias = $foreignTableName;
        }

        if (strpos($foreignKeyField, '.') === false) {
            $foreignKeyField = $foreignTableNameAlias.".".$foreignKeyField;
        }

        $joinCondition = $tableName.".".$field->getName().' = '.
            $foreignKeyField;
            
        $where = $field->get('where');
        $extendJoin = $field->get('extendJoin');
        if ($where && !$extendJoin) {
            $joinCondition .= " AND ".$field->get('where');
        }

        if ($aliasTableName) {
            $foreignTableName .= " as ".$aliasTableName;
        }

        $join = $joinType.' JOIN '.$foreignTableName.' ON ('.$joinCondition.')';

        if ($extendJoin) {
            $join = $join." ".$extendJoin;
        }

        return array($foreignTableNameAlias, $join);
    } // end getForeignKeyFieldJoin

    /**
     * @param AbstractField $field
     * @return array
     * @throws SystemException
     */
    public function getCompositeJoin(AbstractField $field): array
    {
        $routers = $this->model->getRouters();
        $options = $field->getOptions();

        $fromTable = $this->getOriginalStoreName();

        $joins = array();
        $stores = array();
        foreach ($options as $column) {

            if (!empty($column['store'])) {
                $tableName = $column['store'];
            } else {
                $tableName = $this->store->getName();
            }

            if ($fromTable != $tableName) {
                $stores[$tableName] = $tableName;
                $join = $this->doSqlJoin($fromTable, $tableName, $routers);
                $joins += $join;
            }
        }

        return array($stores, $joins);
    } // end getCombineJoin

    /**
     * @param string $sqlTable
     * @param string $needleJoinSqlTable
     * @param array $tablesRelations
     * @return mixed
     * @throws Exception
     */
    public function doSqlJoin(
        string $sqlTable, string $needleJoinSqlTable, array $tablesRelations
    )
    {
        if (!isset($tablesRelations[$sqlTable][$needleJoinSqlTable])) {
            $msg = "Undefined tablesRelations between ".$sqlTable." and ".
                $needleJoinSqlTable;
            throw new Exception($msg);
        }

        $joinRules = $tablesRelations[$sqlTable][$needleJoinSqlTable];

        $methodName = 'getSqlJoinForRelationType'.ucfirst($joinRules['type']);

        return call_user_func_array(
            array($this, $methodName),
            array($sqlTable, $needleJoinSqlTable, $joinRules, &$tablesRelations)
        );
    } // end doSqlJoin

    /**
     * @param string $sqlTable
     * @param string $needleJoinSqlTable
     * @param array $joinRules
     * @return array
     */
    public function getSqlJoinForRelationTypeInner(
        string $sqlTable, string $needleJoinSqlTable, array $joinRules
    ): array
    {
        $sql = $this->_getSqlJoin($sqlTable, $needleJoinSqlTable, $joinRules);

        $join = array(
            $needleJoinSqlTable => "INNER ".$sql
        );

        return $join;
    } // end getSqlJoinForRelationTypeInner

    /**
     * @param string $sqlTable
     * @param string $needleJoinSqlTable
     * @param array $joinRules
     * @return array
     */
    public function getSqlJoinForRelationTypeLeft(
        string $sqlTable, string $needleJoinSqlTable, array $joinRules
    ): array
    {
        $sql = $this->_getSqlJoin($sqlTable, $needleJoinSqlTable, $joinRules);
        $join = array(
            $needleJoinSqlTable => "LEFT ".$sql
        );

        return $join;
    } // end getSqlJoinForRelationTypeLeft

    /**
     * @param string $sqlTable
     * @param string $needleJoinSqlTable
     * @param array $joinRules
     * @return string
     */
    private function _getSqlJoin(
        string $sqlTable, string $needleJoinSqlTable, array $joinRules
    ): string
    {
        if (!empty($joinRules['joinName'])) {
            $needleJoinSqlTable = $joinRules['joinName'].' as '.
                $needleJoinSqlTable;
        }

        $join = "JOIN ".$needleJoinSqlTable." ON (".$joinRules['value'].")";

        return $join;
    } // end _getSqlJoin

    /**
     * @param string $sqlTable
     * @param string $needleJoinSqlTable
     * @param array $joinRules
     * @return array
     */
    public function getSqlJoinForRelationTypeFull(
        $sqlTable, $needleJoinSqlTable, $joinRules
    ): array
    {
        $sql = $this->_getSqlJoin($sqlTable, $needleJoinSqlTable, $joinRules);

        $join = array(
            $needleJoinSqlTable => $sql
        );

        return $join;
    } // end getSqlJoinForRelationTypeInner


}