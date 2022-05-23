<?php

interface IObjectDriver
{
    public function quoteTableName($name);
    public function quoteColumnName($name);

    public function getErrorCode($code): int;

    public function createSelectQuery(
        array $columns,
        string $from,
        ?array $joins = null,
        ?array $where = null,
        ?array $orderBy = null,
        ?int $limit = null,
        ?int $offset = null,
        ?array $groupBy = null,
        ?array $having = null
    ): string;

    public function getSplitOnPages(IDataAccessObject $object, string $query, int $col, int $page): array;
    public function getTableIndexes(IDataAccessObject $object, string $tableName): array;
}