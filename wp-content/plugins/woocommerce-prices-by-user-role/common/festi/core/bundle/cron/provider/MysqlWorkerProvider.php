<?php
namespace cron\provider;

class MysqlWorkerProvider implements IWorkerProvider
{
    public function getSpoolItems(\cron\IWorkerSpool $spool, \IDataAccessObject $connection): array
    {
        $workerColumnName = $spool->getWorkerColumnNameInStorage();
        $idWorker = $connection->quote($spool->getWorkerSteadyValue());

        $sql = "UPDATE %s SET %s = %s WHERE %s IS NULL";
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

        $limit = $spool->getSpoolItemsLimit();
        if ($limit) {
            $sql .= " LIMIT ".$limit;
        }

        $connection->query($sql);

        $sql = "SELECT * FROM %s WHERE %s = %s";
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

        if ($limit) {
            $sql .= " LIMIT ".intval($limit);
        }

        return $connection->getAll($sql);
    }
}