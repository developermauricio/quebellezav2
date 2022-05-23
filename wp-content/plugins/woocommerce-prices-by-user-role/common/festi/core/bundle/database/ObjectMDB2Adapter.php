<?php 

require_once __DIR__.DIRECTORY_SEPARATOR.'ObjectAdapter.php';

/**
 * Adapter for PEAR::MDB2
 *
 * @package    phpObjectDB
 * @author     Denis Panaskin <goliathdp@gmail.com>
 */
class ObjectMDB2Adapter extends ObjectAdapter
{
    public function quote($obj, $type = null)
    {
        return $this->db->quote($obj, $type);
    } // end quote
    
    public function getRow($sql)
    {
        $result = $this->db->getRow($sql);
        
        if (PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    } // end getRow
    
    public function getAll($sql)
    {
        $result = $this->db->getAll($sql);
        
        if (PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    } // end getAll
    
    public function getOne($sql)
    {
        $result = $this->db->getOne($sql);
        
        if (PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    } // end getOne
    
    public function getCol($sql)
    {
        $result = $this->db->getCol($sql);
        
        if (PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    } // end getCol

    public function getAssoc($sql)
    {
        $result = $this->db->getAssoc($sql);
        
        if (PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    } // end getAssoc
    
    public function query($sql)
    {
        $result = $this->db->query($sql);
        
        if (PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    } // end query
    
    public function getInsertID()
    {
        return $this->getOne("SELECT LAST_INSERT_ID()");
    } // end getInsertID

    public function begin($isolationLevel = false)
    {
        if ($this->db->inTransaction()) {
            $this->commit();
        }
        
        self::$_isStartTransaction = true;
        
        $this->db->beginTransaction();
    } // end begin
    
    public function commit()
    {
        $this->db->commit();

        self::$_isStartTransaction = false;
    } // end commit
    
    public function rollback()
    {
        $this->db->rollback();

        self::$_isStartTransaction = false;
    } // end rollback
    
    public function getDatabaseType()
    {
        return "mdb2";
    } // end getDatabaseType
}