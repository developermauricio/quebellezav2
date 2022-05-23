<?php

/**
 * Interface IProxy. Implements communication logic between DGS and storage.
 */
interface IProxy extends IProxyRepository
{
    /**
     * Returns rows values for DGS list.
     * if `$isAllColumns` is true then will be returns all DGS columns include hidden columns too.
     *
     * @param bool $isAllColumns
     * @return array
     */
    public function loadListValues(bool $isAllColumns = false): array;

    /**
     * Returns total rows count into DGS list.
     *
     * @return int
     */
    public function getCount(): int;

    /**
     * Returns list of values for foreign key fields with primary key value.
     *
     * @param $primaryValue
     * @param array $options
     * @return array
     */
    public function loadForeignAssigns($primaryValue, array $options): array;

    /**
     * Returns list of values for foreign key fields.
     *
     * @param array $options
     * @return array
     */
    public function loadForeignValues(array $options): array;

    /**
     * Load values for ForeignKey field.
     *
     * @param ForeignKeyField $fields
     * @return bool
     */
    public function loadForeignKeyValues(ForeignKeyField &$fields): bool;

    /**
     * Insert/update values into many to many field.
     *
     * @param Many2manyField $item
     * @param int $id
     * @param array $values
     * @return bool
     */
    public function updateManyToManyValues(Many2manyField $item, int $id, array $values): bool;

    /**
     * Remove many to many field values by primary key.
     *
     * @param $primaryKeyValue
     * @return mixed
     */
    public function removeAllManyToManyValuesByPrimaryKey($primaryKeyValue): bool;

    /**
     * Returns describe columns array.
     *
     * @param bool $isAllColumns
     * @return array
     */
    public function getQueryColumns(bool $isAllColumns = false): array;

    /**
     * Returns describe joins array.
     *
     * @param array $columns
     * @return array
     */
    public function getQueryJoins(array $columns): array;

    /**
     * Returns condition for DGS list.
     *
     * @return array
     */
    public function getQueryWhere(): array;

    /**
     * Set true if you would like disable use pagination in load list values.
     *
     * @param bool $isUseLimit
     */
    public function setUseLimit(bool $isUseLimit): void;

    /**
     * Returns true if a list use pagination logic.
     *
     * @return bool
     */
    public function isUseLimit(): bool;

    /**
     * Returns aggregation totals for DGS.
     *
     * @return array
     */
    public function loadAggregations(): array;

    /**
     * Create audit table for tracking changes values into DGS.
     *
     * @param $auditTableName
     * @param $originalTableName
     * @return bool
     */
    public function createAuditTable($auditTableName, $originalTableName): bool;
}