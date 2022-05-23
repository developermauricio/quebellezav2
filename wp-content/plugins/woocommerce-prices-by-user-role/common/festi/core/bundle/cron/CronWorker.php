<?php

use cron\provider\IWorkerProvider;

abstract class CronWorker
{
    private $_options;
    
    public function __construct($options = false)
    {
        if ($options) {
            $this->_options = $options;
        } else {
            $this->_options = $this->getOptions();
        }
        
        $this->onInit();
    } // end __construct
    
    public function getOption($key)
    {
        if (array_key_exists($key, $this->_options)) {
            return $this->_options[$key];
        }
        
        return false;
    } // end getOption
    
    public function start()
    {
        $this->onStart();
        
        while ($this->hasNextIteration()) {
            $this->iteration();
            $this->onDelay();
        }
    } // end start

    /**
     * Return true if worker have to process next iteration.
     *
     * @return bool
     */
    protected function hasNextIteration(): bool
    {
        return true;
    } // end hasNextIteration
    
    public function iteration()
    {
        $this->onSpoolStart();
        
        $records = $this->getSpool();

        if (is_array($records) && $records) {
            foreach ($records as $record) {
                try {
                    $this->onRow($record);
                    $this->onRowCompleted($record);
                } catch (Exception $exp) {
                    $this->onRowError($record, $exp);
                }
            }
        }

        $this->onSpoolCompleted();
    } // end iteration
    
    protected function onDelay()
    {
        if (php_sapi_name() == "cli") {
            sleep(1);
        }
    }

    protected function onRowError($record, $exp)
    {
        throw $exp;
    }
    
    protected function onInit()
    {
    }
    
    protected function onStart()
    {
    }
    
    protected function onRowCompleted($record)
    {
    }
    
    protected function onSpoolStart()
    {
    }
    
    protected function onSpoolCompleted()
    {
    }
    
    abstract protected function getOptions();
    abstract protected function getSpool();
    abstract protected function onRow($record);

    /**
     * Returns true of script is already executed.
     *
     * @return bool
     * @throws Exception
     */
    protected function isRunning()
    {
        $path = $this->getBaseLockPath();

        if (!is_dir($path) && !mkdir($path, 0777)) {
            throw new CronException("Access error: ".$path);
        }

        $filePath = $path.$this->getLockFileName().'.chk';

        $lastPid = false;
        if (file_exists($filePath)) {
            $lastPid = (int) file_get_contents($filePath);
        }

        $res = false;
        if ($lastPid) {
            //$cmd = 'ps aux | grep '.$lastPid.' | grep -v grep | awk "{ print \$2 }"';
            $cmd = 'ps -p '.$lastPid.' | grep '.$lastPid.' | awk "{ print \$1 }"';
            $res = (int) `$cmd`;
        }

        if ($res && $res === $lastPid) {
            return true;
        }

        $pid = getmypid();
        $res = file_put_contents($filePath, $pid);
        if ($res === false) {
            throw new CronException("Access error: ".$filePath);
        }

        return false;
    } // isRunning

    protected function getLockFileName()
    {
        throw new CronException("Not implemented method.");
    }

    protected function getBaseLockPath()
    {
        $path = dirname((new ReflectionClass($this))->getFileName());

        return $path.DIRECTORY_SEPARATOR."locks".DIRECTORY_SEPARATOR;
    } // end getBaseLockPath

    /**
     * Returns provider communication with storage.
     *
     * @param string $type
     * @return IWorkerProvider
     */
    protected function createProvider(string $type): IWorkerProvider
    {
        $className = ucfirst($type).'WorkerProvider';
        $fullClassName = '\\cron\\provider\\'.$className;

        if (class_exists($fullClassName)) {
            return new $fullClassName();
        }

        $path = __DIR__.DIRECTORY_SEPARATOR.'provider'.DIRECTORY_SEPARATOR.$className.".php";
        require_once $path;

        return new $fullClassName();
    } // end getProvider
}
