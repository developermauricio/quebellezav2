<?php

class MysqlObjectDriver extends AbstractObjectDriver
{
    public function __construct($db)
    {
        $db->query("SET NAMES 'utf8'");
    } // end __construct

    public function quoteTableName($name)
    {
        return '`'.$name.'`';
    } // end quoteTableName
    
    public function quoteColumnName($key)
    {
        $key = "`".$key."`";

        if (strpos($key, '.') !== false) {
            $key = str_replace(".", "`.`", $key);
        }
        
        return $key;
    } // end quoteColumnName

    public function getSplitOnPages(IDataAccessObject $object, string $query, int $col, int $page): array
    {
        $result = array();
        if ($page !== 0) {
            $page -= 1;
        }

        // XXX: Fixed it
        if (!preg_match('/SQL_CALC_FOUND_ROWS/Umis', $query)) {
            $query = preg_replace("/^SELECT/Umis", "SELECT SQL_CALC_FOUND_ROWS ", $query);
        }

        $query .= " LIMIT ".($page * $col).", ".$col;

        $result['rows']    = $object->getAll($query);
        $result['cnt']     = $object->getOne('SELECT FOUND_ROWS()');
        $result['pageCnt'] = $result['cnt'] > 0 ? ceil($result['cnt'] / $col) : 0;

        return $result;
    }// end getSplitOnPages

    public function getTableIndexes(IDataAccessObject $object, string $tableName): array
    {
        return $object->getAll("SHOW INDEX FROM ".$this->quoteTableName($tableName));
    }
}