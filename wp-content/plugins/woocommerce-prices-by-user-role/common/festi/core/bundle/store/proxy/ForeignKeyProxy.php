<?php

trait ForeignKeyProxy
{
    /**
     * Call plugin callback to override load ForeignKey field values.
     *
     * @param Store $store
     * @param ForeignKeyField $field
     * @param array $info
     */
    protected function firePrepareStoreForeignKeyFieldValuesCallback(
        Store &$store, ForeignKeyField &$field, array &$info
    ): void
    {
        $event = new FestiEvent(Store::EVENT_ON_PROXY_PREPARE_FOREIGN_KEY_VALUES, $info);
        $this->store->dispatchEvent($event);

        $plugin = $this->store->getPlugin();

        if ($plugin && $plugin instanceof IStoreProxyForeignKeyValuesListener) {
            $plugin->onPrepareStoreForeignKeyFieldValues($store, $field, $info);
        }

    } // end firePrepareStoreForeignKeyFieldValuesCallback
}