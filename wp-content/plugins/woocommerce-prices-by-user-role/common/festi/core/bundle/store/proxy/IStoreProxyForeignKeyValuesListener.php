<?php

/**
 * Interface IStoreProxyForeignKeyValuesListener describe callback for foreign key values.
 */
interface IStoreProxyForeignKeyValuesListener
{
    /**
     * Override method to implement custom or external logic for foreign key filed values.
     *
     * @param Store $store
     * @param ForeignKeyField $field
     * @param array $info
     */
    public function onPrepareStoreForeignKeyFieldValues(Store &$store, ForeignKeyField &$field, array &$info): void;

}