<?php
namespace cron;

interface IWorkerSpool
{
    public function getWorkerColumnNameInStorage(): string;
    public function getWorkerSteadyValue(): string;
    public function getWorkerType(): string;
    public function getStorageName(): string;
    public function getSpoolExternalCondition(): string;
    public function getErrorColumnNameInStorage(): string;
    public function getSpoolItemsLimit(): int;
    public function getPrimaryColumnNameInStorage(): string;

}