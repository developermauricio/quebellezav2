<?php 

require_once __DIR__.DIRECTORY_SEPARATOR.'ObjectAdapter.php';

/**
 * Adapter for WordpressDB 
 *
 * @package    ObjectDB
 * @author     Denis Panaskin <goliathdp@gmail.com>
 */
class ObjectWPDBAdapter extends ObjectAdapter
{
    public function __construct(&$db)
    {
       parent::__construct($db);
       $this->db->hide_errors();
    } // end __construct
    
    public function quote($obj, $type = null)
    {
        return "'".esc_sql($obj)."'";
    }  // end quote
    
    public function getRow($sql): array
    {
        $result = $this->db->get_row($sql, ARRAY_A);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }

        return $result ? $result : array();
    } // end getRow
    
    private function _hasError()
    {
        return !empty($this->db->last_error);
    } // end _hasError
    
    private function _getError()
    {
        return $this->db->last_error;
    } // end _getError
    
    public function getAll($sql): array
    {
        $result = $this->db->get_results($sql, ARRAY_A);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }

        return $result;
    } // end getAll
    
    public function getOne($sql)
    {
        $result = $this->db->get_row($sql, ARRAY_N);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }

        return is_null($result[0]) ? false : $result[0];
    } // end getOne
    
    public function query($sql)
    {
        $result = $this->db->query($sql);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }
        
        return $result;
    } // end query
    
    public function getCol($sql): array
    {
        $result = $this->db->get_col($sql);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }
        
        return $result;
    } // end getCol
    
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
    
    public function begin($isolationLevel = false)
    {
        $this->query('START TRANSACTION');
        
        self::$_isStartTransaction = true;
    } // end begin
    
    public function commit()
    {
        $this->query('COMMIT');

        self::$_isStartTransaction = false;
    } // end commit
    
    public function rollback()
    {
        $this->query('ROLLBACK');

        self::$_isStartTransaction = false;
    } // end rollback
    
    public function getInsertID()
    {
        return $this->getOne("SELECT LAST_INSERT_ID()");
    } // end getInsertID

    public function getPrefix()
    {
        return $this->db->prefix;
    } // end getPrefix
    
    public function getDatabaseType()
    {
        return DataAccessObject::TYPE_MYSQL;
    } // end getDatabaseType
}
