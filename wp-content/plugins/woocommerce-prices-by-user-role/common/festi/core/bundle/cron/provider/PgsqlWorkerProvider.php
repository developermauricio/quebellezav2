<?php
namespace cron\provider;

use cron\IWorkerSpool;

class PgsqlWorkerProvider implements IWorkerProvider
{
    public function getSpoolItems(IWorkerSpool $spool, \IDataAccessObject $connection): array
    {
        $workerColumnName = $spool->getWorkerColumnNameInStorage();
        $idWorker = $connection->quote($spool->getWorkerSteadyValue());

        $baseSelectQuery = $this->_getBaseSelectQuery($spool, $connection);

        $sql = "UPDATE %s SET %s = %s WHERE %s IN (".$baseSelectQuery.")";

        $sql = sprintf(
            $sql,
            $spool->getStorageName(),
            $workerColumnName,
            $idWorker,
            $spool->getPrimaryColumnNameInStorage()
        );

        $connection->query($sql);

        $sql = "SELECT * FROM %s WHERE %s = %s";
        $sql = sprintf(
            $sql,
            $spool->getStorageName(),
            $workerColumnName,
            $idWorker
        );

        $sql .= $this->_getCommonCondition($spool, $connection);

        $limit = $spool->getSpoolItemsLimit();
        if ($limit) {
            $sql .= " LIMIT ".$limit;
        }

        return $connection->getAll($sql);
    } // end getSpoolItems

    private function _getBaseSelectQuery(IWorkerSpool $spool, \IDataAccessObject $connection): string
    {
        $sql = "SELECT %s FROM %s WHERE %s";

        $sql = sprintf(
            $sql,
            $connection->quoteColumnName($spool->getPrimaryColumnNameInStorage()),
            $connection->quoteTableName($spool->getStorageName()),
            $this->_getBaseCondition($spool, $connection)
        );

        $limit = $spool->getSpoolItemsLimit();
        if ($limit) {
            $sql .= " LIMIT ".$limit;
        }

        return $sql;
    } // end _getBaseSelectQuery

    private function _getBaseCondition(IWorkerSpool $spool, \IDataAccessObject $connection): string
    {
        $sql = $connection->quoteColumnName($spool->getWorkerColumnNameInStorage())." IS NULL";

        $sql .= $this->_getCommonCondition($spool, $connection);

        return $sql;
    } // end _getBaseCondition

    private function _getCommonCondition(IWorkerSpool $spool, \IDataAccessObject $connection): string
    {
        $sql = "";

        $workerType = $spool->getWorkerType();
        if ($workerType) {
            $sql .= " AND type = ".$connection->quote($workerType);
        }

        $condition = $spool->getSpoolExternalCondition();
        if ($condition) {
            $sql .= sprintf(" AND %s", $condition);
        }

        $error = $spool->getErrorColumnNameInStorage();
        if ($error) {
            $sql .= sprintf(" AND %s IS NULL", $error);
        }

        return $sql;
    } // end _getCommonCondition

}