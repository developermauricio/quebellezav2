<?php


abstract class CronSingletonWorker extends CronWorker
{
    /**
     * @override
     */
    protected function onInit()
    {
        if ($this->isRunning()) {
            throw new AlreadyRunningCronWorkerException();
        }
    } // end onInit

    /**
     * @override
     */
    protected function getLockFileName()
    {
        return 'lock_'.get_class($this);
    } // end getLockFileName

    protected function getSpool()
    {

    }

    protected function onRow($record)
    {

    }

    protected function getOptions()
    {

    }

    public function start()
    {
        throw new CronException("Not implemented method.");
    }
}