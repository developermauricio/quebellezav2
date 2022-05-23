<?php

/**
 * @abstract
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version rc1.2
 */
abstract class ObjectAdapter implements IDataAccessObject
{
    const SQL_WHERE = " WHERE %s";
    const SQL_AND   = " AND ";
    
    const MSG_UNDEFINED_TYPE = "Undefined database type";

    protected $db;
    protected $driver;

    private static $_instances;
    
    /**
     * Flag for detection transaction
     * @var boolean
     */
    protected static $_isStartTransaction = false;

    protected $_dbTableNameDelimiterInColumnName = ".";

    protected $reservedWords = array(
        'NOW()',
        'NOT NULL',
        'NULL',
        'CURRENT_DATE()',
        'CURRENT_TIME()',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'NOW'
    );

    public function __construct(&$db)
    {
        $this->db = $db;
        
        $this->driver = $this->_createDriverInstance($db);
    } // end __construct
    
    private function _createDriverInstance($db)
    {
        $type = $this->getDatabaseType();
        
        $className = ucfirst($type).'ObjectDriver';

        if (!class_exists($className)) {
            require_once __DIR__.DIRECTORY_SEPARATOR.'driver'.DIRECTORY_SEPARATOR.$className.'.php';
        }

        return new $className($db);
    } // end _createDriverInstance
    
    public function inTransaction(): bool
    {
        return self::$_isStartTransaction;
    } // end inTransaction

    /**
     * Returns objects instance by name
     *
     * @param string $name
     * @return Object
     */
    public function &get($name, $path = false)
    {
        if (isset(self::$_instances[$name])) {
            return self::$_instances[$name];
        }

        self::$_instances[$name] = DataAccessObject::getInstance(
            $name,
            $this->db,
            $path
        );

        return self::$_instances[$name];
    } // end get

    public function insert($table, $values, $is_update_dublicate = false)
    {
        $sql = $this->getInsertSQL($table, $values, $is_update_dublicate);

        $this->query($sql);

        return $this->getInsertID();
    } // end insert

    public function delete($table, $condition)
    {
        $where = $this->getSqlCondition($condition);

        $sql = "DELETE FROM ".$table;
        
        if ($where) {
            $sql .= sprintf(static::SQL_WHERE, join(static::SQL_AND, $where));
        }

        return $this->query($sql);
    } // end delete

    /**
     * Generate an mass insert
     *
     * @param string $table
     * @param mixed $values
     * @param bool $inForeach
     * @return mixed
     */
    public function massInsert($table, $values, bool $inForeach = false)
    {
        if ($inForeach) {
            $res = array();
            foreach ($values as $value) {
                $res[] = $this->insert($table, $value);
            }

        } else {
            $rows = array();
            foreach ($values as $items) {
                $data = $this->getInsertValues($items);
                $rows[] = '('.join(', ', $data).')';
            }

            $columns = array_keys($values[0]);
            foreach ($columns as &$column) {
                $column = $this->quoteColumnName($column);
            }
            unset($column);

            $table = $this->quoteTableName($table);

            $sql = "INSERT INTO ".$table." (".join(", ", $columns).") VALUES ".
                   join(', ', $rows);

            $res = $this->query($sql);
        }

        return $res;
    } // end massInsert

    /**
     * Returns quoted values for insert sql
     *
     * @param array $values
     * @see Object::getInsertSQL()
     * @see Object::massInsert()
     * @return array
     */
    private function getInsertValues($values)
    {
        foreach ($values as &$item) {
            if ( is_null($item) ) {
                $item = 'NULL';
                continue;
            }

            if ( !in_array($item, $this->reservedWords) ) {
                $item = $this->quote($item);
            }
        }
        unset($item);

        return $values;
    } // end getInsertValues

    public function update($table, $values, $condition = array())
    {
        $sql = $this->getUpdateSQL($table, $values, $condition);

        return $this->query($sql);
    } // end update
    
    /**
     * Returns the generated SQL query to insert data
     *
     * @param string $table
     * @param array $values
     * @param bool $isUpdateDublicate
     * @return string SQL string
     * @throws DatabaseException
     */
    public function getInsertSQL($table, $values, $isUpdateDublicate = false)
    {
        $values = $this->getInsertValues($values);
        $columns = array_keys($values);
        
        foreach ($columns as &$column) {
            $column = $this->quoteColumnName($column);
        }
        unset($column);

        $sql = "INSERT INTO ".
                    $this->quoteTableName($table)." ".
                    "(".join(", ", $columns).") ".
                    "VALUES (".join(", ", $values).")";

        if ($isUpdateDublicate) {
            $sql = $this->_getUpdateDuplicateSQL($sql, $values);
        }

        return $sql;
    } // end getInsertSQL

    public function getUpdateValues($values, $tableName = false)
    {
        foreach ($values as $key => &$item) {
            
            if ($tableName) {
                $key = $tableName.$this->_dbTableNameDelimiterInColumnName.$key;
            }
            
            if (is_null($item)) {
                $item = $this->quoteColumnName($key)." = NULL";
                continue;
            }

            $lastSymbol = mb_substr($key, mb_strlen($key) - 1, 1);
            if (in_array($lastSymbol, array('+', '-'))) {
                $key = str_replace($lastSymbol, "", $key);
                $item = $key." = ".$key." ".$lastSymbol." ".$this->quote($item);
                continue;
            }

            $key = $this->quoteColumnName($key);
            
            if (!$this->reservedWords || !in_array($item, $this->reservedWords)) {
                $item = $key." = ".$this->quote($item);
            } else {
                $item = $key." = ".$item;
            }
        }
        unset($item);
        
        return $values;
    } // end getUpdateValues
    
    /**
     * Returns the generated SQL query to update the data
     *
     * @param string $table
     * @param array $values
     * @param array $condition
     * @return string
     */
    public function getUpdateSQL($table, $values, $condition = array())
    {
        $values = $this->getUpdateValues($values);
        
        $sql = "UPDATE ".$table." SET ".join(", ", $values);

        if (is_array($condition)) {
            $sqlCondition = $this->getSqlCondition($condition);
            if ($sqlCondition) {
                $sql .= sprintf(static::SQL_WHERE, join(static::SQL_AND, $sqlCondition));
            }
        } else {
            $sql .= sprintf(static::SQL_WHERE, $condition);
        }

        return $sql;
    } // end getUpdateSQL

    /**
     * Condition statement builder.
     *
     * <code>
     * $search = array(
     *  'columnName' => 5,
     *  'columnName2&IN' => array(1, 2, 3, 4)
     *  'columnName3 = 5 AND columnName4 < 7'
     * );
     * </code>
     *
     * @param array|bool $obj
     * @return array|mixed
     */
    public function getSqlCondition($obj = array())
    {
        $result = array();
        
        if ($obj === false) {
            return $result;
        }

        foreach ($obj as $key => $item) {
            // XXX: if numeric then we get sql condition statement
            if (is_numeric($key)) {
                $conditionResult = $item;
            } else {
                $conditionResult = $this->_getConditionResult($key, $item);
            }

            if ($conditionResult) {
                $result[] = $conditionResult;
            }
        }

        return $result;
    } // end getSqlCondition
    
    private function _getConditionResult($key, $item)
    {
        $result = "";
        
        $buffer = explode("&", $key);
        $action = !empty($buffer[1]) ? $buffer[1] : "=";
    
        if ($this->_isNull($item, $buffer)) {
            $result = $buffer[0] . ' IS NULL';
            return $result;
        }
    
        if (in_array($action, array('IN', 'NOT IN'))) {
            $values = array();
            if (!$item) {
                return $result;
            }
            $item = is_scalar($item) ? explode(", ", $item) : $item;
            foreach ($item as $val) {
                $values[] = $this->quote($val);
            }
        
            if ($values) {
                $result = $buffer[0]." ".$action." (".join(', ', $values).')';
            }
            return $result;
        }
    
        $resultKey = $this->_getConditionByKey($key, $item);
    
        if ($resultKey) {
            return $resultKey;
        }
    
        $resultCommand = $this->_getSqlConditionByActionCommand($action, $item, $buffer);
        if ($resultCommand) {
            return $resultCommand;
        }

        // FIXME:
        if ($this->getDatabaseType() == DataAccessObject::TYPE_PGSQL && strtolower($action) == "like") {
            $buffer[0] .= "::text";
        }

        if (!in_array($item, $this->reservedWords)) {
            $result = $buffer[0]." ".$action." ".$this->quote($item);
        } else {
            $result = $buffer[0]." ".$action." ".$item;
        }
        
        return $result;
    }
    
    private function _getConditionByKey($key, $item)
    {
        if ($key == 'sql_or') {
            return $this->_getSqlConditionOR($item);
        }
    
        if ($key == 'sql_and') {
            return $this->_getSqlConditionAND($item);
        }
        
        return null;
    }
    
    private function _getSqlConditionByActionCommand($action, $item, $buffer)
    {
        $command = strtolower($action);
        
        switch ($command) {
            case 'or_sql':
                $sql = '('.join(' OR ', $item).')';
                break;
            case 'or':
                $sql = $this->_getOrCondition($buffer, $item);
                break;
            case 'match':
                $sql = "MATCH (".$buffer[0].") AGAINST (".$this->quote($item).")";
                break;
            case 'between':
                $sql = $this->_getBetweenCondition($buffer, $item);
                break;
            case 'soundex':
                $sql = $this->_getSoundexCondition($buffer, $item);
                break;
            default:
                $sql = null;
        }
        
        return $sql;
    }
    
    private function _getOrCondition($buffer, $item)
    {
        list($value, $others) = $item;
    
        $action = empty($buffer[2]) ? '&=' : '&'.$buffer[2];
    
        $condition = array($buffer[0].$action => $value);
        $ors = $this->getSqlCondition($condition);
    
        $others = $this->getSqlCondition($others);
        $conditions = array_merge($ors, $others);
        return '('.join(' OR ', $conditions).')';
    } // end _getOrCondition
    
    private function _getSqlConditionAND($item)
    {
        $search = array();
        foreach ($item as $row) {
            if (is_scalar($row)) {
                $search[] = $row;
            } else {
                $search[] = join(static::SQL_AND, $this->getSqlCondition($row));
            }
        }
    
        return join(static::SQL_AND, $search);
    } // end _getSqlConditionAND
    
    private function _getSqlConditionOR($item)
    {
        $search = array();
        foreach ($item as $row) {
            if (is_scalar($row)) {
                $search[] = $row;
            } else {
                $search[] = join(static::SQL_AND, $this->getSqlCondition($row));
            }
        }
    
        return '(('.join(' ) OR (', $search).'))';
    } // end _getSqlConditionOR
    
    /**
     * Returns a SOUNDEX condition. Use:
     *
     * <code>
     * $search = array(
     *      'city&SOUNDEX' => 'Chicago'
     * );
     *
     * $search = array(
     *      'city&SOUNDS LIKE' => 'Chicago'
     * );
     *
     * </code>
     *
     * @param array $buffer
     * @param string $item
     * @return string
     */
    private function _getSoundexCondition($buffer, $item)
    {
        return "SOUNDEX(".$buffer[0].") = SOUNDEX(".$this->quote($item).")";
    } // end _getSoundexCondition
    
    /**
     * Returns a BETWEEN condition. Use:
     *
     * <code>
     * $search = array(
     *      'cdate&BETWEEN' => array(
     *          'XXXX-XX-XX',
     *          'XXXX-XX-XX'
     *      )
     * );
     *
     * $search = array(
     *      'cdate&BETWEEN' => array('XXXX-XX-XX') // cdate >= 'XXXX-XX-XX'
     * );
     *
     * $search = array(
     *      'cdate&BETWEEN' => array(1 => 'XXXX-XX-XX') // cdate <= 'XXXX-XX-XX'
     * );
     *
     * $search = array(
     *      'cdate&BETWEEN' => 'XXXX-XX-XX AND XXXX-XX-XX'
     * );
     * </code>
     *
     * @param array $buffer
     * @param mixed $item
     * @throws DatabaseException
     * @return string
     */
    private function _getBetweenCondition($buffer, $item)
    {
        $columnName = $this->quoteColumnName($buffer[0]);
        
        if (is_array($item)) {
            if (count($item) == 1) {
                
                if (array_key_exists(0, $item)) {
                    $operation = ' >= ';
                    $value = $item[0];
                } else if (array_key_exists(1, $item)) {
                    $operation = ' <= ';
                    $value = $item[1];
                } else {
                    throw new DatabaseException(
                        "Syntax error into BETWEEN condition"
                    );
                }
                
                $condition = $columnName.$operation.$this->quote($value);
            } else {
                $condition = $columnName." BETWEEN ".$this->quote($item[0]).
                             static::SQL_AND.$this->quote($item[1]);
            }
            
        } else {
            $condition = $columnName." BETWEEN ".$item;
        }
        
        return $condition;
    } // end _getBetweenCondition

    public function quoteTableName($name)
    {
        return $this->driver->quoteTableName($name);
    } // end quoteTableName
    
    public function quoteColumnName($name)
    {
        return $this->driver->quoteColumnName($name);
    } // end quoteColumnName
    
    /**
     * Returns sql query without where. The method should be overridden
     *
     * @throws DatabaseException
     */
    protected function getSql()
    {
        throw new DatabaseException('Undefined method getSql', 2001);
    } // end getSql
    
    /**
     * Returns generate select sql query
     *
     * @param array $condition
     * @param string $sql
     * @param array $orderBy
     * @return string
     */
    public function getSelectSQL($condition, $sql, $orderBy = array())
    {
        $where = $this->getSqlCondition($condition);
    
        if ($where) {
            $sql .=  sprintf(static::SQL_WHERE, join(static::SQL_AND, $where));
        }
    
        if ($orderBy) {
            $sql .= " ORDER BY ".join(', ', $orderBy);
        }

        return $sql;
    } // end getSelectSQL

    /**
     * Fetch rows returned from a query
     *
     * @param string|array $selectSql
     * @param array $condition
     * @param array|bool $orderBy
     * @param int $type
     * @return array
     * @throws DatabaseException
     */
    public function select(
        $selectSql,
        $condition = array(),
        $orderBy = array(),
        $type = DataAccessObject::FETCH_ALL
    )
    {
        $sql = $this->getSelectSQL($condition, $selectSql, $orderBy);
    
        $methods = array(
            DataAccessObject::FETCH_ALL   => 'getAll',
            DataAccessObject::FETCH_ROW   => 'getRow',
            DataAccessObject::FETCH_ASSOC => 'getAssoc',
            DataAccessObject::FETCH_COL   => 'getCol',
            DataAccessObject::FETCH_ONE   => 'getOne',
        );
    
        if (!isset($methods[$type])) {
            $msg = sprintf('Undefined select type %s', $type);
            throw new DatabaseException($msg, 3005);
        }
    
        if (!is_callable(array($this, $methods[$type]))) {
            $msg = sprintf(
                'Method "%s" was not found in Object.',
                $methods[$type]
            );
            
            throw new DatabaseException($msg, 3006);
        }

        // XXX: Compatibility with PHP7+ hinting ?array
        // FIXME: We've to fix all get* methods in to adapters
        $data = call_user_func_array(array($this, $methods[$type]), array($sql));
        return $data ? $data : array();
    } // end select
    
    /**
     * Returns an array of filter fields
     *
     * @param $search
     * @return array
     */
    public function getConditionFields($search)
    {
        $fields = array();
    
        foreach ($search as $key => $item) {
            $buffer = explode("&", $key);
    
            $info = explode('.', $buffer[0]);
    
            if (!isset($info[1])) {
                continue;
            }
    
            $fields[$info[0]][$info[1]] = $buffer[0];
        }
    
        return $fields;
    } // end getConditionFields

    /**
     * Finds whether a value should be null
     *
     * @param mixed $value
     * @param array $buffer
     * @return bool
     */
    private function _isNull($value, $buffer)
    {
        if (!empty($buffer[1]) &&
            strtolower($buffer[1]) === 'is' &&
            is_scalar($value) &&
            strtolower($value) === 'null'
        ) {
            return true;
        }

        if (is_null($value)) {
            return true;
        }

        return false;
    } // end _isNull

    /**
     * Returns tables list.
     *
     * @return mixed
     * @throws DatabaseException
     */
    public function getTables()
    {
        $type = $this->getDatabaseType();

        switch ($type) {
            case DataAccessObject::TYPE_MYSQL:
                return $this->getCol("SHOW TABLES");
                break;

            case DataAccessObject::TYPE_PGSQL:
                return $this->getCol("SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'");
                break;

            case DataAccessObject::TYPE_MSSQL:
                return $this->getCol("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES");
                break;

            default:
                throw new DatabaseException(static::MSG_UNDEFINED_TYPE);
        }
    } // end getTables


    public function getTableIndexes($tableName): array
    {
        // TODO: Add support compatibility array keys form all databases
        /*
            [table] => __site_contents
            [Non_unique] => 0
            [key_name] => PRIMARY
            [index_type] => BTREE
         */

        return $this->getDriver()->getTableIndexes($this, $tableName);
    } // end getTableIndexes

    /**
     * Enable or disable foreign key checks.
     * @param bool $isEnable = true
     * @return bool
     * @throws DatabaseException
     */
    public function setForeignKeyChecks($isEnable = true)
    {
        $type = $this->getDatabaseType();

        switch ($type) {
            case DataAccessObject::TYPE_MYSQL:
                $sql = "SET FOREIGN_KEY_CHECKS=".intval($isEnable);
                return $this->query($sql);
                break;

            case DataAccessObject::TYPE_PGSQL:
                return $this->_setPostgresTriggersStatus($isEnable);
                break;

            case DataAccessObject::TYPE_MSSQL:
                return $this->_setMsSqlForeignKeyConstraintsStatus($isEnable);
                break;

            default:
                throw new DatabaseException(static::MSG_UNDEFINED_TYPE);
        }
    } // end setForeignKeyChecks

    /**
     * Remove a table.
     * @param $table
     * @return mixed
     */
    public function deleteTable($table)
    {
        $type = $this->getDatabaseType();

        $sql = "DROP TABLE ".$this->quoteTableName($table);

        if ($type == DataAccessObject::TYPE_PGSQL) {
            $sql = $sql.' CASCADE';
        }

        return $this->query($sql);
    } // end deleteTable

    /**
     * Enable/Disable all triggers for postgresql
     * @return bool
     */
    private function _setPostgresTriggersStatus($isEnable = true)
    {
        $tables = $this->getTables();

        $status = 'DISABLE';
        if ($isEnable) {
            $status = 'ENABLE';
        }

        foreach ($tables as $table) {
            $sql = "ALTER TABLE ".$table." ".$status." TRIGGER USER";
            $this->query($sql);
        }

        return true;
    } // end _setPostgresTriggersStatus
    
    private function _getUpdateDuplicateSQL($sql, $values)
    {
        $type = $this->getDatabaseType();

        switch ($type) {
            case DataAccessObject::TYPE_MYSQL:
                $sql .= " ON duplicate KEY UPDATE ";
                break;

            case DataAccessObject::TYPE_PGSQL:
                $msg = sprintf(
                    "Method Insert Not Support Third Param For %s DB Type.",
                    DataAccessObject::TYPE_PGSQL
                );
                throw new DatabaseException($msg);

            default:
                throw new DatabaseException(static::MSG_UNDEFINED_TYPE);
        }

        $rows = array();

        foreach ($values as $field => $value) {
            $rows[] = $field." = ".$value;
        }

        $sql .= join(", ", $rows);
        
        return $sql;
    } // end _getUpdateDuplicateSQL

    private function _setMsSqlForeignKeyConstraintsStatus($isEnable)
    {
        $sql = 'sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"';

        if ($isEnable) {
            $sql = 'sp_msforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"';
        }

        return $this->query($sql);
    } // end _setMsSqlForeignKeyConstraintsStatus

    public function getDateTimeValue(string $datetime): string
    {
        return date('Y-m-d H:i:s', strtotime($datetime));
    } // end getDateTimeValue

    public function getDriver(): IObjectDriver
    {
        return $this->driver;
    }
}