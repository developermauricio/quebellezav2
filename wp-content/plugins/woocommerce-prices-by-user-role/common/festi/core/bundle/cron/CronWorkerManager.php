<?php

abstract class CronWorkerManager extends CronQueueSingletonWorker
{
    /**
     * @override
     */
    protected function onSpoolCompleted()
    {
    }

    /**
     * @override
     * @param $record
     */
    protected function onRowCompleted($record)
    {
    }

    protected function startProcess($scriptPath)
    {
        $processCmd = $this->getPhpPath().' '.$scriptPath;
        $this->execProcess($processCmd);

        return true;
    } // end startProcess

    protected function execProcess($processCmd)
    {
        if ($this->isProcessRunning($processCmd)) {
            return true;
        }

        $screenCmd = $this->getScreenPath().' -d -m '.$processCmd;

        $output = array();
        exec($screenCmd, $output, $ret);
        if ($ret !== 0) {
            throw new CronException("Shell command error: ".$screenCmd);
        }

        return true;
    } // end execProcess

    /**
     * Returns true if process is ran.
     *
     * @param string $processCmd
     * @return bool
     * @throws CronException
     */
    protected function isProcessRunning(string $processCmd): bool
    {
        $cmd = 'ps aww | grep -i '.escapeshellarg($processCmd).' | grep -v "grep" | grep -v "screen"';

        $processList = array();
        exec($cmd, $processList, $ret);
        if ($ret !== 0) {
            throw new CronException("Shell command error: ".$cmd);
        }

        $lists = array_filter($processList);

        return !empty($lists);
    } // end isProcessRunning

    public function getStorageName(): string
    {
        return 'cron_workers';
    }

    protected function getScreenPath()
    {
        return "screen";
    } // end getScreenPath

    protected function getPhpPath()
    {
        return "php";
    } // end getPhpPath
}