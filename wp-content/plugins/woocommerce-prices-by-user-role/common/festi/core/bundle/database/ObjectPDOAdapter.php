<?php

/**
 * Adapter for PDO
 */
class ObjectPDOAdapter extends ObjectAdapter
{
    public function __construct(&$db)
    {
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_EMPTY_STRING);
        $db->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        parent::__construct($db);
    } // end __construct
    
    public function quote($obj, $type = null)
    {
        return $this->db->quote($obj, $type);
    } // end quote

    private function _execute($sql): PDOStatement
    {
        $query = null;
        try {
            $query = $this->db->prepare($sql);
        } catch (PDOException $exp) {
            throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
        }

        if ($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], (int) $info[1], $sql);
        }

        try {
            $res = $query->execute();
            if (!$res) {
                $info = $query->errorInfo();
                throw new DatabaseException($info[2], (int) $info[1], $sql);
            }
        } catch (PDOException $exp) {
            throw new DatabaseException($exp->getMessage(), (int) $exp->getCode(), $sql, $exp);
        }

        return $query;
    } // end _execute

    public function getRow($sql): array
    {
        $query = $this->_execute($sql);

        $result = $query->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            $result = array();
        }

        return $result;
    } // end getRow

    public function getAll($sql): array
    {
        $query = $this->_execute($sql);

        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if (!$result) {
            $result = array();
        }

        return $result;
    } // end getAll

    public function getCol($sql): array
    {
        $query = $this->_execute($sql);

        $result = array();

        while (($cell = $query->fetchColumn()) !== false) {
            $result[] = $cell;
        }
        
        return $result;
    } // end getCol

    public function getOne($sql)
    {
        $query = $this->_execute($sql);

        return $query->fetchColumn();
    } // end getOne

    public function getAssoc($sql): array
    {
        $query = $this->_execute($sql);
        
        $result = array();

        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            $val = array_shift($row);
            if (count($row) == 1) {
                $row = array_shift($row);
            }
            $result[$val] = $row;
        }

        return $result;
    } // end getAssoc

    public function begin($isolationLevel = false)
    {
        $this->db->beginTransaction();

        self::$_isStartTransaction = true;
    } // end begin

    public function commit()
    {
        $this->db->commit();

        self::$_isStartTransaction = false;
    } // end commit

    public function rollback()
    {
        $this->db->rollBack();

        self::$_isStartTransaction = false;
    } // end rollback

    public function query($sql)
    {
        $affectedRows = 0;

        try {
            $affectedRows = $this->db->exec($sql);
        } catch (PDOException $exp) {
            $code = (int) $exp->getCode();
            $code = $this->driver->getErrorCode($code);
            throw new DatabaseException($exp->getMessage(), $code, $sql, $exp);
        }

        // TODO: Remove deprecated logic
        if ($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], (int) $info[1], $sql);
        }

        return $affectedRows;
    } // end query

    public function getInsertID()
    {
        return $this->db->lastInsertId();
    } // end getInsertID
    
    public function getDatabaseType()
    {
        $type = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($type == "sqlsrv" || $type == "dblib") {
            return DataAccessObject::TYPE_MSSQL;
        }

        return $type;
    } // end getDatabaseType
}