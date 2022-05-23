<?php 

class CassandraObjectDriver extends AbstractObjectDriver
{
    public function quoteTableName($name)
    {
        return $name;
    } // end quoteTableName
    
    public function quoteColumnName($key)
    {
        return $key;
    } // end quoteColumnName

    public function getTableIndexes(IDataAccessObject $object, string $tableName): array
    {
        throw new SystemException("Unsupportable method.");
    }
}