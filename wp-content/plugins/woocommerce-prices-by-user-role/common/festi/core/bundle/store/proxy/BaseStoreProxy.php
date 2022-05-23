<?php

trait BaseStoreProxy
{
    /**
     * Returns real name in storage/database/api.
     *
     * @return string
     */
    protected function getOriginalStoreName(): string
    {
        $fromTable = $this->model->get('customFrom');
        if (!$fromTable) {
            $fromTable = $this->store->getName();
        }

        return $fromTable;
    } // end getOriginalStoreName
}