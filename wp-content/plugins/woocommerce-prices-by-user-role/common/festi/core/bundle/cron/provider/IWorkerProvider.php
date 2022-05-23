<?php

namespace cron\provider;

use cron\IWorkerSpool;

interface IWorkerProvider
{
    public function getSpoolItems(IWorkerSpool $spool, \IDataAccessObject $connection): array;

}