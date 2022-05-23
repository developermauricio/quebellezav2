<?php

class ObjectCassandraAdapter extends ObjectAdapter
{
    public function __construct(&$db)
    {
        parent::__construct($db);
    } // end __construct
    
    public function quote($obj, $type = null)
    {
        return $obj;
    } // end quote
    
    public function getRow($sql): array
    {
        $sql .= " LIMIT 1";
        
        $result = $this->query($sql);
        
        if ($result->count() == 0) {
            return array();
        }
        
        $row = $result->first();
        $this->_prepareValues($row);
        
        return $row;
    } // end getRow
    
    public function getAll($sql): array
    {
        $result = $this->query($sql);
        
        if ($result->count() == 0) {
            return array();
        }
        
        $rows = array();
        
        foreach ($result as $row) {
            $this->_prepareValues($row);
            $rows[] = $row;
        }
        
        return $rows;
    } // end getAll
    
    public function getOne($sql)
    {
        $row = $this->getRow($sql);
        
        return array_shift($row);
    } // end getOne
    
    public function getAssoc($sql): array
    {
        $rows = $this->getAll($sql);
        
        $result = array();
        foreach ($rows as $row) {
            $val = array_shift($row);
            if (count($row) == 1) {
                $row = array_shift($row);
            }
            $result[$val] = $row;
        }
        
        return $result;
    } // end getAssoc
    
    public function getCol($sql): array
    {
        $rows = $this->getAll($sql);
        
        $result = array();
        foreach ($rows as $row) {
            $result[] = array_shift($row);
        }
        
        return $result;
    } // end getCol
    
    public function query($sql)
    {
        try {
            $result = $this->db->execute($sql);
        } catch (Exception $exp) {
            throw new DatabaseException($exp->getMessage(), $exp->getCode());
        }
        
        return $result;
    } // end query
    
    public function getInsertID()
    {
        throw new DatabaseException("Unsupportable method.");
    } // end getInsertID
    
    public function begin($isolationLevel = false)
    {
    } // end begin
    
    public function commit()
    {
    } // end commit
    
    public function rollback()
    {
    } // end rollback
    
    public function getDatabaseType()
    {
        return 'cassandra';
    } // end getDatabaseType
    
    private function _prepareValues(&$row)
    {
        foreach ($row as $key => &$value) {
            if ($value instanceof Cassandra\Timestamp) {
                $value = $value->time();
            }
        }
    } // end _prepareValues
}