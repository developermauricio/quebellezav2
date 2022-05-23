<?php

trait SqlProxyRepository
{
    public function begin(): bool
    {
        if ($this->isBegin()) {
            return false;
        }

        $this->connection->begin();
        return true;
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }

    public function isBegin(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * @param array $values
     * @return mixed
     * @throws SystemException
     */
    public function insert(array $values)
    {
        $tableName = $this->store->getName();

        $tables = $this->_splitValuesByTables($tableName, $values);

        $childStores = array();
        foreach ($tables['general'] as $foreignTableName => $foreignValues) {
            $res = $this->_updateForeignTableValues(
                $tableName,
                $foreignTableName,
                $foreignValues,
                $values
            );

            if ($res !== true) {
                $childStores[$foreignTableName] = array(
                    'foreignKey' => $res,
                    'values' => $foreignValues
                );
            }
        }

        $primaryValue = $this->connection->insert($tableName, $values);

        foreach ($childStores as $childStoreName => $options) {
            $childValues = $options['values'];
            $tmp = explode(".", $options['foreignKey']);
            $childValues[$tmp[1]] = $primaryValue;

            $this->connection->insert($childStoreName, $childValues);
        }

        $callback = !empty($tables['callback']) ? $tables['callback'] : array();

        $this->notifyForeignStores(
            $callback,
            $values,
            $primaryValue,
            Store::ACTION_INSERT
        );

        return $primaryValue;
    } // end insert

    /**
     * @param string $tableName
     * @param array $values
     * @return array
     * @throws Exception
     */
    private function _splitValuesByTables(string $tableName, array &$values): array
    {
        $fields = &$this->store->getModel()->getFields();

        $stores = array(
            'general' => array(),
            'callback' => array()
        );

        foreach ($values as $columnName => $value) {
            if (strpos($columnName, '.') === false) {
                continue;
            }

            list($storeName, $storeColumnName) = explode(".", $columnName);
            $stores['general'][$storeName][$storeColumnName] = $value;
            unset($values[$columnName]);
        }

        foreach ($fields as $columnName => $field) {

            if (!$field->isEditable()) {
                continue;
            }

            $storeName = $field->getStoreName();
            $key = $field->getName();
            if ($storeName != $tableName) {
                $stores['general'][$storeName][$key] = $values[$key];
                unset($values[$key]);
            }
        }

        foreach ($stores['general'] as $foreignStoreName => $foreignValues) {
            $relation = $this->_getRelation($tableName, $foreignStoreName);
            if ($relation['type'] != "callback") {
                continue;
            }

            $options = json_decode($relation['value'], true);
            $options['values'] = $foreignValues;
            $options['storeName'] = $foreignStoreName;

            $stores['callback'][$foreignStoreName] = $options;

            unset($stores['general'][$foreignStoreName]);
        }

        $target = array(
            'general' => &$stores['general'],
            'callback' => &$stores['callback'],
            'store' => &$this->store
        );

        $event = new FestiEvent(Store::EVENT_PREPARE_REPOSITORY_VALUES, $target);
        $this->store->dispatchEvent($event);

        return $stores;
    } // end _splitValuesByTables

    /**
     * @param string $tableName
     * @param string $foreignTableName
     * @param array $foreignValues
     * @param array $values
     * @return bool|mixed
     * @throws SystemException
     */
    private function _updateForeignTableValues(
        string $tableName, string $foreignTableName, array $foreignValues, array &$values, ?array $currentRow = null
    )
    {
        $data = $this->_getDataForUpdateForignTableValues(
            $tableName,
            $foreignTableName,
            $values,
            $currentRow
        );

        if ($this->_isChildForignTableValues($data)) {
            return $data['searchKey'];
        }

        if (!$data['tableColumnValue']) {
            $id = $this->connection->insert($foreignTableName, $foreignValues);
            $values[$data['tableColumnName']] = $id;
            return true;
        }

        $search = array(
            $data['searchKey'] => $data['tableColumnValue']
        );

        $this->connection->update($foreignTableName, $foreignValues, $search);

        return true;
    } //end _updateForeignTableValues

    /**
     * @param array $data
     * @return bool
     */
    private function _isChildForignTableValues($data): bool
    {
        $primaryKey = $this->store->getPrimaryKey();

        return $data['tableColumnName'] == $primaryKey;
    }

    /**
     * @param array $stores
     * @param array $values
     * @param $primaryKeyValue
     * @param string $action
     * @return bool
     * @throws SystemException
     */
    protected function notifyForeignStores(
        array $stores, array $values, $primaryKeyValue, string $action
    ): bool
    {
        foreach ($stores as $foreignStoreName => $options) {

            $options['parentPrimaryKeyValue'] = $primaryKeyValue;
            $options['parentValues'] = $values;
            $options['action'] = $action;

            $this->store->fireForeignStoreCallback($options);
        }

        return true;
    } // end notifyForeignStores

    /**
     * @override
     * @param string|int|bool $primaryKeyValue
     * @param array $values
     * @return bool
     * @throws SystemException
     * @throws Exception
     */
    public function updateByPrimaryKey($primaryKeyValue, array $values): bool
    {
        $primaryKey = $this->store->getPrimaryKey();

        $storeName = $this->store->getName();

        $tables = $this->_splitValuesByTables($storeName, $values);

        $childStores = $this->_getChildStores($storeName, $tables, $values, $primaryKeyValue);

        $search = array(
            $primaryKey => $primaryKeyValue
        );

        $this->connection->update($storeName, $values, $search);

        foreach ($childStores as $childStoreName => $options) {
            $this->_updateChildStoreValues(
                $childStoreName,
                $options,
                $primaryKeyValue
            );
        }

        $callback = !empty($tables['callback']) ? $tables['callback'] : array();

        $this->notifyForeignStores(
            $callback,
            $values,
            $primaryKeyValue,
            Store::ACTION_EDIT
        );

        return true;
    } // end updateByPrimaryKey

    /**
     * @param string $storeName
     * @param array $tables
     * @param array $values
     * @return array
     * @throws SystemException
     */
    private function _getChildStores(
        string $storeName, array $tables, array $values, ?string $primaryKeyValue = null
    ): array
    {
        $childStores = array();

        if (empty($tables['general'])) {
            return $childStores;
        }

        $currentRow = array();
        if ($primaryKeyValue) {
            $currentRow = $this->getRawRow($primaryKeyValue);
        }

        foreach ($tables['general'] as $foreignTableName => $foreignValues) {
            $res = $this->_updateForeignTableValues(
                $storeName,
                $foreignTableName,
                $foreignValues,
                $values,
                $currentRow
            );

            if ($res !== true) {
                $childStores[$foreignTableName] = array(
                    'foreignKey' => $res,
                    'values' => $foreignValues
                );
            }
        }
        return $childStores;
    } // end _getChildStores

    protected function getRawRow(string $primaryKeyValue): ?array
    {
        assert($this instanceof StoreProxy);
        $query = "SELECT * FROM ".$this->getOriginalStoreName()." WHERE ";

        $primaryKey = $this->store->getPrimaryKey();

        $search = array(
            $primaryKey => $primaryKeyValue
        );

        assert($this->connection instanceof IDataAccessObject);

        $where = $this->connection->getSqlCondition($search);
        $query.= join(" AND ", $where);

        return $this->connection->getRow($query);
    } // end getRawRow

    /**
     * @param string $childStoreName
     * @param array $options
     * @param string $primaryKeyValue
     * @return bool
     */
    private function _updateChildStoreValues(
        string $childStoreName, array $options, string $primaryKeyValue
    ): bool
    {
        $childValues = $options['values'];
        $tmp = explode(".", $options['foreignKey']);
        $columnName = $tmp[1];

        $sql = "SELECT COUNT(*) FROM ".$childStoreName." WHERE ".$columnName.
            " = ".$this->connection->quote($primaryKeyValue);
        $count = $this->connection->getOne($sql);

        $childValues[$columnName] = $primaryKeyValue;

        if ($count > 0) {
            $search = array(
                $columnName => $primaryKeyValue
            );

            $this->connection->update($childStoreName, $childValues, $search);
        } else {
            $this->connection->insert($childStoreName, $childValues);
        }

        return true;
    } //end _updateChildStoreValues

    /**
     * @param string $tableName
     * @param string $foreignTableName
     * @param array $values
     * @return array
     * @throws SystemException
     */
    private function _getDataForUpdateForignTableValues(
        string $tableName, string $foreignTableName, array $values, ?array $currentRow = null
    ): array
    {
        $relation = $this->_getRelation($tableName, $foreignTableName);

        $columns = explode(" = ", $relation['value']);
        // TODO:
        if (count($columns) > 2) {
            $msg = "Unsupportable relation: ".$relation['value'];
            throw new SystemException($msg);
        }

        $result = array(
            'searchKey' => false,
            'tableColumnName' => false,
            'tableColumnValue' => false
        );

        foreach ($columns as $column) {
            if (strpos($column, $tableName) === false) {
                $result['searchKey'] = $column;
            } else {
                list($tableName, $columnName) = explode('.', $column);

                $result['tableColumnName'] = $columnName;

                if (array_key_exists($columnName, $values)) {
                    $result['tableColumnValue'] = $values[$columnName];
                } else if ($currentRow && array_key_exists($columnName, $currentRow)) {
                    $result['tableColumnValue'] = $currentRow[$columnName];
                }
            }
        }

        return $result;
    } // end _getDataForUpdateForignTableValues

    /**
     * @param string $tableName
     * @param string $foreignTable
     * @return mixed
     * @throws Exception
     */
    private function _getRelation(string $tableName, string $foreignTable)
    {
        $routers = &$this->store->getModel()->getRouters();

        if (!empty($routers[$tableName][$foreignTable])) {
            return $routers[$tableName][$foreignTable];
        }

        $msg = "Undefined relation between ".$tableName." and ".$foreignTable;
        throw new Exception($msg);
    } // end _getRelation

    /**
     * @param array $search
     * @return string
     * @throws Exception
     */
    protected function getSearchSql(array $search): string
    {
        assert($this instanceof IProxy);

        $columns = $this->getQueryColumns();
        $joins   = $this->getQueryJoins($columns);

        $columnsSection = join(', ', $columns);
        $fromSection    = join(' ', $joins);

        $sql = "SELECT ".$columnsSection." FROM ".$fromSection;

        $where = $this->connection->getSqlCondition($search);
        if ($where) {
            $sql .= ' WHERE '.join(' AND ', $where);
        }

        return $sql;
    } // end getSearchSql

    /**
     * @param array $search
     * @return mixed
     * @throws Exception
     */
    public function search(array $search): array
    {
        $sql = $this->getSearchSql($search);

        return $this->connection->getAll($sql);
    } // end search

    /**
     * @param array $search
     * @return mixed
     * @throws Exception
     */
    public function loadRow(array $search): ?array
    {
        $sql = $this->getSearchSql($search);

        $result = $this->connection->getRow($sql);

        if (!$result) {
            return null;
        }

        return $result;
    } // end loadRow

    /**
     * @override
     * @param $primaryKeyValue
     * @return mixed
     */
    public function removeByPrimaryKey($primaryKeyValue): int
    {
        $sql = 'DELETE FROM '.$this->store->getName().
            ' WHERE '.$this->store->getPrimaryKey(). ' = '.
            $this->connection->quote($primaryKeyValue);

        return $this->connection->query($sql);
    } // end removeByPrimaryKey

    /**
     * Returns row values by primary key.
     *
     * @override
     * @param $id
     * @return mixed
     * @throws Exception
     */
    public function loadRowByPrimaryKey($id): ?array
    {
        $tableName = $this->store->getName();
        $primaryKey = $this->model->getPrimaryKey();

        $search = array(
            $tableName.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$primaryKey => $id
        );

        return $this->loadRow($search);
    } // end loadRowByPrimaryKey
}