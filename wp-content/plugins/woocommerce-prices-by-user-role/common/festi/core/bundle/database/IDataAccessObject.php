<?php

interface IDataAccessObject
{
    const FETCH_ALL   = 100;
    const FETCH_ROW   = 101;
    const FETCH_ASSOC = 102;
    const FETCH_COL   = 103;
    const FETCH_ONE   = 104;

    public function getRow($sql): array;
    public function getAll($sql): array;
    public function getOne($sql);
    public function getAssoc($sql): array;
    public function getCol($sql): array;
    
    public function query($sql);
    public function insert($table, $values, $isUpdateDuplicate = false);
    public function update($table, $values, $condition);
    public function delete($table, $condition);
    public function massInsert($table, $values, bool $inForeach = false);
    public function getInsertID();

    /**
     * Returns true if enabled transaction mode.
     *
     * @return bool
     */
    public function inTransaction(): bool;
    public function begin($isolationLevel = false);
    public function commit();
    public function rollback();
    
    public function quote($obj, $type = null);
    public function quoteTableName($name);
    public function quoteColumnName($name);

    /**
     * Returns type of databases mysql, mssql etc.
     * @retrun string
     */
    public function getDatabaseType();

    /**
     * Returns tables list.
     *
     * @return mixed
     */
    public function getTables();

    /**
     * Enable or disable foreign key checks.
     *
     * @param $isEnable = true
     * @return boolean
     */
    public function setForeignKeyChecks($isEnable = true);

    /**
     * Remove a table.
     * @param $table
     * @return mixed
     */
    public function deleteTable($table);

    /**
     * Returns prepared conditions to filter data.
     *
     * @param array $obj
     * @return mixed
     */
    public function getSqlCondition($obj = array());

    /**
     * Returns table indexes list.
     *
     * @param $tableName
     * @return array
     */
    public function getTableIndexes($tableName): array;

    /**
     * Returns formatted datetime value.
     *
     * @param string $datetime
     * @return string
     */
    public function getDateTimeValue(string $datetime): string;

    /**
     * Returns Driver instance.
     *
     * @return IObjectDriver
     */
    public function getDriver(): IObjectDriver;
}