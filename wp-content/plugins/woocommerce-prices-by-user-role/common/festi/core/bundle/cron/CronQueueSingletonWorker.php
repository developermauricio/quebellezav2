<?php

abstract class CronQueueSingletonWorker extends CronQueueWorker
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
        $id = $this->getOption('id');

        return 'lock_'.get_class($this)."_".$id;
    }

}
