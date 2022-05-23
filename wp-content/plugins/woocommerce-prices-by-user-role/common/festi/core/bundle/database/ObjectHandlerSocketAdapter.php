<?php

/**
 * Adapter for Handler Socket
 *
 * @package    phpObjectDB
 * @author     Denis Panaskin <goliathdp@gmail.com>
 * @author     Maxim Massalskiy <github@bred.in.ua>
 */
class ObjectHandlerSocketAdapter extends ObjectAdapter
{
    const DEFAULT_SELECT_LIMIT = 10000;
    
    const MSG_WRONG_ARGUMENTS      = "Wrong number of arguments";
    const MSG_SUPPORT_TRANSACTIONS = "Handler Sockets do not support transactions";
    
    public function __construct(&$db)
    {
        parent::__construct($db);

        // FIXME: 
        $this->handleSocket = &$this->db->db;
    } // end __construct

    public function quote($obj, $type = null)
    {
        return $obj;
    } // end quote
    
    public function quoteTableName($tableName)
    {
        return $tableName;
    } // end quoteTableName
    
    public function getRow($sql): array
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(static::MSG_WRONG_ARGUMENTS);
        }
        
        $method = $this->getCalledMethod();
        
        $args = func_get_args();
        $args[3] = 1;
        
        $rows = $this->query($args, $method);
        
        if (empty($rows)) {
            return array();
        }
        
        list($row) = $rows;
        
        return $row;
    } // end getRow

    public function getOne($sql)
    {
        if (func_num_args() != 3) {
            throw new DatabaseException(static::MSG_WRONG_ARGUMENTS);
        }
        
        $method = $this->getCalledMethod();
        
        $args = func_get_args();
        $args[3] = 1;
        
        $rows = $this->query($args, $method);
        
        if (empty($rows)) {
            return null;
        }
        
        list(, $val) = each($rows[0]);
        
        return $val;
    } // end getOne
        
    public function getAll($sql): array
    {
        if (func_num_args() < 3) {
            throw new DatabaseException(static::MSG_WRONG_ARGUMENTS);
        }
        
        $method = $this->getCalledMethod();
        
        $args = func_get_args();
        
        return $this->query($args, $method);
    } // end getAll
    
    public function getAssoc($sql): array
    {
        if (func_num_args() < 3) {
            throw new DatabaseException(static::MSG_WRONG_ARGUMENTS);
        }
        
        $method = $this->getCalledMethod();
        
        $args = func_get_args();
        
        $rows = $this->query($args, $method);
        
        if (empty($rows)) {
            return array();
        }
        
        $result = array();
        foreach ($rows as $row) {
            list(, $key) = each($row);
            
            $result[$key] = $row;
        }
        unset($rows);
        
        return $result;
    } // end getAssoc
    
    public function getCol($sql): array
    {
        if (func_num_args() < 3) {
            throw new DatabaseException(static::MSG_WRONG_ARGUMENTS);
        }
        
        $method = $this->getCalledMethod();
        
        $args = func_get_args();
        
        $rows = $this->query($args, $method);
        
        if (empty($rows)) {
            return array();
        }
        
        $result = array();
        foreach ($rows as $row) {
            list(, $key) = each($row);
            
            $result[] = $key;
        }
        unset($rows);
        
        return $result;
    } // end getCol
    
    public function query($sql)
    {
        if (func_num_args() != 2) {
            throw new DatabaseException(static::MSG_WRONG_ARGUMENTS);
        }
        
        $selectArgs = func_get_arg(0);
        $maskArgs = array_fill(0, 6, null);

        $selectArgs = $selectArgs + $maskArgs;
        
        list($tableName, $fields, $indexValue, $limit, $offset, $filters) = $selectArgs; 
        
        $fields = !is_scalar($fields) ? $fields : array($fields);
        
        $indexValue = $this->_getKeyValue($indexValue, $operation);
        $limit = !is_null($limit) ? $limit : self::DEFAULT_SELECT_LIMIT;
        $offset = !is_null($offset) ? $offset : 0;
        $filters = !is_null($filters) ? $filters : array();
        $filters = $this->_getConditionFilters($filters);
        
        $method = func_get_arg(1);
        $indexKey = $this->getMethodToKey($method);
        
        return $this->handleSocket->getLimit(
            $tableName,
            $fields,
            $indexKey,
            $indexValue,
            $operation,
            $limit,
            $offset,
            $filters
        );
    } // end query
    
    private function _getKeyValue($value, &$operation = null, &$specCondition = null)
    {
        if (is_array($value)) {
            $operation = '=';
            return $value;
        }
        
        $buffer = explode('&', $value);
        $operation = isset($buffer[1]) ? $buffer[1] : '=';
        // TODO: 
        // The difference of 'F' and 'W' is that, when a record does not meet the specified condition, 
        // 'F' simply skips the record, and 'W' stops the loop. 
        $specCondition = isset($buffer[2]) && in_array($buffer[2], array('F', 'W')) ? $buffer[2] : 'F';
        
        return $buffer[0];
    } // end _getKeyValue
    
    private function _getConditionFilters($search)
    {
        $filters = array();
        foreach ($search as $key => $value) {
            
            $key = $this->_getKeyValue($key, $operation, $specCondition);
            
            $filters[$key] = array(
                $specCondition,
                $operation,
                count($filters),
                $value
            );
        }
        
        return $filters;
    } // end getConditionFilters
    
    public function insert($table, $values, $is_update_dublicate = false)
    {
        $method = $this->getCalledMethod();
        
        $indexKey = $this->getMethodToKey($method);

        if (!$is_update_dublicate) {
            return $this->handleSocket->insert($table, $values, $indexKey);
        } else {
            return $this->handleSocket->replace(
                $table,
                $values,
                $indexKey,
                $values[$indexKey]
            );
        }
    } // end insert
    
    public function update($table, $values, $condition, $limit = 1)
    {
        $method = $this->getCalledMethod();
        
        $indexKey = $this->getMethodToKey($method);
        
        return $this->handleSocket->update(
            $table,
            $values,
            $indexKey,
            $condition,
            $limit
        );
    } // end update

    public function delete($table, $condition, $limit = 1, $filters = array())
    {
        $method = $this->getCalledMethod();
        
        $indexKey = $this->getMethodToKey($method);
        
        if (is_array($condition)) {
            $result = $this->handleSocket->multipleRemove(
                $table,
                $indexKey,
                $condition,
                $limit
            );
        } else {
            $indexValue = $this->_getKeyValue($condition, $operation);
            
            $filters = $this->_getConditionFilters($filters);
            
            $result = $this->handleSocket->remove(
                $table,
                $indexKey,
                $indexValue,
                $limit,
                $operation,
                $filters
            );
        }
        
        return $result;
    } // end delete
    
    public function massInsert($table, $values, bool $isForeach = false)
    {
        $method = $this->getCalledMethod();
        
        $indexKey = $this->getMethodToKey($method);

        return $this->handleSocket->multipleInsert($table, $values, $indexKey);
    } // end massInsert
    
    public function begin($isolationLevel = false)
    {
        throw new DatabaseException(static::MSG_SUPPORT_TRANSACTIONS);
    } // end begin
    
    public function commit()
    {
        throw new DatabaseException(static::MSG_SUPPORT_TRANSACTIONS);
    } // end commit
    
    public function rollback()
    {
        throw new DatabaseException(static::MSG_SUPPORT_TRANSACTIONS);
    } // end rollback

    protected function getRelationsMethodToKey()
    {
        throw new DatabaseException('Undefined method getRelationsMethodToKey');
    } // end getRelationsMethodToKey
     
    private function getMethodToKey($method)
    {
        $relations = $this->getRelationsMethodToKey();

        if (!isset($relations[$method])) {
            throw new DatabaseException('Undefined object method '.$method);
        }
        
        return $relations[$method];
    } // end getMethodToKey
    
    private function getCalledMethod()
    {
        $result = false;
        
        $trace = debug_backtrace();
        if (!empty($trace[2]['function'])) {
            $result = $trace[2]['function'];
        }

        return $result;
    } // end getCalledMethod
    
    public function getDatabaseType()
    {
        return "handler_socket";
    } // end getDatabaseType
    
    public function getInsertID()
    {
        throw new DatabaseException('Undefined method getInsertID');
    } // end getInsertID
}