<?php

namespace cron\provider;

require_once __DIR__.DIRECTORY_SEPARATOR.'MysqlWorkerProvider.php';

class MssqlWorkerProvider extends MysqlWorkerProvider
{
    public function getSpoolItems(\cron\IWorkerSpool $spool, \IDataAccessObject $connection): array
    {
        $workerColumnName = $spool->getWorkerColumnNameInStorage();
        $idWorker = $connection->quote($spool->getWorkerSteadyValue());

        $limitStatement = "";
        $limit = $spool->getSpoolItemsLimit();
        if ($limit) {
            $limitStatement .= " TOP(".$limit.") ";
        }

        $sql = "UPDATE ".$limitStatement." %s SET %s = %s WHERE %s IS NULL";
        $sql = sprintf(
            $sql,
            $spool->getStorageName(),
            $workerColumnName,
            $idWorker,
            $workerColumnName
        );

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

        $connection->query($sql);

        $sql = "SELECT ".$limitStatement." * FROM %s WHERE %s = %s";
        $sql = sprintf(
            $sql,
            $spool->getStorageName(),
            $workerColumnName,
            $idWorker
        );

        if ($condition) {
            $sql .= sprintf(" AND %s", $condition);
        }

        if ($error) {
            $sql .= sprintf(" AND %s IS NULL", $error);
        }

        return $connection->getAll($sql);
    }
}