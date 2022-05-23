<?php

class PgsqlObjectDriver extends AbstractObjectDriver
{
    public function __construct($db)
    {
        $db->query("SET NAMES 'utf8'");
    } // end __construct

    public function quoteTableName($name)
    {
        return '"'.$name.'"';
    } // end quoteTableName
    
    public function quoteColumnName($key)
    {
        $key = "\"".$key."\"";
        if (strpos($key, '.') !== false) {
            $key = str_replace(".", "\".\"", $key);
        }
        
        return $key;
    } // end quoteColumnName

    public function getTableIndexes(IDataAccessObject $object, string $tableName): array
    {
        $sql = "SELECT indexname as table, indexdef FROM pg_indexes WHERE tablename = ".$object->quote($tableName);

        return $object->getAll($sql);
    }
}