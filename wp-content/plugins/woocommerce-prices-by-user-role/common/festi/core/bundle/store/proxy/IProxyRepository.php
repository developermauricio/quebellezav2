<?php

/**
 * Interface IProxyRepository. Implement base methods to work with storage.
 */
interface IProxyRepository
{
    /**
     * Returns true if transaction is started.
     *
     * @return mixed
     */
    public function isBegin(): bool;

    /**
     * Start transaction.
     *
     * @return mixed
     */
    public function begin(): bool;

    /**
     * Commit transaction.
     */
    public function commit(): void;

    /**
     * Rollback transaction.
     */
    public function rollback(): void;

    /**
     * Insert values into storage and return primary key value.
     *
     * @param array $values
     * @return mixed
     */
    public function insert(array $values);

    /**
     * Update values by primary key.
     *
     * @param $primaryKeyValue
     * @param array $values
     * @return bool
     */
    public function updateByPrimaryKey($primaryKeyValue, array $values): bool;

    /**
     * Returns rows from storage.
     *
     * @param array $search
     * @return array
     */
    public function search(array $search): array;

    /**
     * Returns one row from storage.
     *
     * @param array $search
     * @return array
     */
    public function loadRow(array $search): ?array;

    /**
     * Remove values by primary key.
     *
     * @param $primaryKeyValue
     * @return int
     */
    public function removeByPrimaryKey($primaryKeyValue): int;

    /**
     * Returns row values by primary key.
     *
     * @param $id
     * @return mixed
     */
    public function loadRowByPrimaryKey($id): ?array;

}