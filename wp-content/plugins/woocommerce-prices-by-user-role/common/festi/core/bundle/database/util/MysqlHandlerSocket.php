<?php
/**
 *
 * @link http://code.google.com/p/php-handlersocket/wiki/HandlerSocketExecuteSingle
 * @link http://tokarchuk.ru/2010/12/handlersocket-protocol-and-php-handlersocket-extension/
 */
//http://code.google.com/p/php-handlersocket-wrapper/source/browse/trunk/handlersocket_wrapper.php?r=2

//FIXME: Change index and connection logic
class MysqlHandlerSocket
{
    const MYSQL_HANDLER_ERROR_ALREDY_EXISTS = 121;

    public $host;
    public $name;
    public $readPort;
    public $writePort;

//     protected $connection;

    protected $index = 1;

    public function __construct($host, $name, $readPort = false, $writePort = false)
    {
        $this->host = $host;
        $this->name = $name;
        $this->readPort = $readPort ? $readPort : 9998;
        $this->writePort = $writePort ? $writePort : 9999;

        /*
        try {
            $this->connection = new HandlerSocket($this->host, $this->readPort);
        } catch(Exception $exp) {
            throw new DatabaseException($exp->getMessage(), $exp->getCode());
        }
        */
    }

    private function throwException($method, $table, $index, $code = 0, $values = false, $columns = false)
    {
        $msg = sprintf(
            "Host: ".$this->host.", Name: ".$this->name.", Method: %s; table: %s; index: %s; code: %s;",
            $method,
            $table,
            $index,
            $code
        );
        
        if ($values) {
            $msg .= print_r($values, 1);
        }
        
        throw new DatabaseException($msg);
    }

    public function get($table, $columns, $indexKey, $indexValue, $op = '=')
    {
        try {
            $connection = new HandlerSocket($this->host, $this->readPort);
        } catch(Exception $exp) {
            throw new DatabaseException($exp->getMessage(), $exp->getCode());
        }

        if (is_scalar($indexValue)) {
            $indexValue = array($indexValue);
        }
        
        if (is_scalar($columns)) {
            $columns = explode(",", $columns);
        }

        $select = join(',', $columns);
        $res = $connection->openIndex(1, $this->name, $table, $indexKey, $select);

        if (!$res) {
            $error = $connection->getError()." [columns: ".$select."]";
            $this->throwException(__METHOD__, $table, $indexKey, $error);
        }

        $rows = $connection->executeSingle(1, $op, $indexValue, 1, 0);

        if ($rows === false) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        if (!$rows) {
            return array();
        }

        list($row) = $rows;

        $result = array();

        foreach ($row as $index => $value) {
            $result[$columns[$index]] = $value;
        }

        return $result;
    } // end get

    public function getLimit(
        $table, $columns, $indexKey, $indexValue, $op = '>', $limit = 1, $offset = 0, $filters = array()
    )
    {
        try {
            $connection = new HandlerSocket($this->host, $this->readPort);
        } catch(Exception $exp) {
            throw new DatabaseException($exp->getMessage(), $exp->getCode());
        }

        if (is_scalar($indexValue)) {
            $indexValue = array($indexValue);
        }

        $select = join(',', $columns);

        $res = $connection->openIndex(1, $this->name, $table, $indexKey, $select, implode(',', array_keys($filters)));

        if (!$res) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        $rows = $connection->executeSingle(1, $op, $indexValue, $limit, $offset, null, array(), array_values($filters));

        if ($rows === false) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        if (!$rows) {
            return array();
        }

        $result = array();

        if (is_array($rows)) {
            foreach ($rows as $rowIndex => $row) {
                foreach ($row as $index => $value) {
                    $result[$rowIndex][$columns[$index]] = $value;
                }
            }
        }

        return $result;
    } // end getLimit


    public function insert($table, $values, $indexKey = "PRIMARY")
    {
        $connection = new HandlerSocket($this->host, $this->writePort);

        $res = $connection->openIndex(1, $this->name, $table, $indexKey, join(',', array_keys($values)));

        if (!$res) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        $ret = $connection->executeInsert(1, array_values($values));

        if ($ret === false) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError(), $values);
        }

        return $ret;
    } // end insert

    public function remove($table, $indexKey, $value, $limit = 1, $operation = "=", $filters = array())
    {
        if (!is_array($value)) {
            $value = array($value);
        }
        
        $connection = new HandlerSocket($this->host, $this->writePort);

        $res = $connection->openIndex(1, $this->name, $table, $indexKey, '', implode(',', array_keys($filters)));
        if ($res === false) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        $ret = $connection->executeDelete(1, $operation, $value, $limit, 0, array_values($filters));
        if ($ret === false) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        return $ret;
    }

    public function multipleRemove($table, $indexKey, $values, $limit = 1)
    {
        $connection = new HandlerSocket($this->host, $this->writePort);

        $res = $connection->openIndex(1, $this->name, $table, $indexKey, '');
        if ($res === false) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        $ret = false;

        foreach ($values as $value) {
            $ret = $connection->executeDelete(1, "=", array($value), $limit);
            if ($ret === false) {
                $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
            }
        }

        return $ret;
    }

    public function multipleInsert($table, $values, $indexKey)
    {
        $connection = new HandlerSocket($this->host, $this->writePort);

        list(, $fields) = each($values);
        $fields = array_keys($fields);

        $res = $connection->openIndex(1, $this->name, $table, $indexKey, join(',', $fields));

        if (!$res) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        foreach ($values as $value) {
            $ret = $connection->executeInsert(1, array_values($value));
            if ($ret === false) {
                $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
            }
        }

        return true;
    } // end multipleInsert


    public function update($table, $values, $indexKey, $value, $limit = 1)
    {
        $connection = new HandlerSocket($this->host, $this->writePort);

        $res = $connection->openIndex(1, $this->name, $table, $indexKey, join(',', array_keys($values)));

        if (!$res) {
            throw new DatabaseException($connection->getError());
        }

        if (is_scalar($value)) {
            $value = array($value);
        }

        $res = $connection->executeUpdate(1, "=", $value, array_values($values), $limit, 0);

        if ($res === false) {
            $this->throwException(__METHOD__, $table, $indexKey, $connection->getError());
        }

        return $res;
    }

    public function replace($table, $values, $indexKey, $value)
    {
        $columns = array_keys($values);
        $result = $this->get($table, $columns, $indexKey, $value);

        if (empty($result)) {
            $result = $this->insert($table, $values, $indexKey);
        } else {
            $this->update($table, $values, $indexKey, $value);
        }

        return $result;
    } // end replace
}

?>
