<?php


use cron\provider\IWorkerProvider;
use cron\IWorkerSpool;

abstract class CronQueueWorker extends CronWorker implements IWorkerSpool
{
    const OPTION_LIMIT = 'limit';

    /**
     * @var IDataAccessObject
     */
    protected $db;
    
    protected $processedStorageRecords;

    private $_provider;

    public function __construct(IDataAccessObject &$db, $options = false)
    {
        $this->db = &$db;
        $this->processedStorageRecords = array();
        
        parent::__construct($options);

        $type = $this->db->getDatabaseType();
        $this->_provider = $this->createProvider($type);
    } // end __construct

    /**
     * @override
     */
    protected function getOptions()
    {
        $params = array(
            'id:',
            'type::',
            'limit::'
        );

        $options = getopt("", $params);

        if (!array_key_exists('id', $options) || !$options['id']) {
            throw new CronException("Undefined id");
        }

        if (!array_key_exists(static::OPTION_LIMIT, $options) || !$options[static::OPTION_LIMIT]) {
            $options[static::OPTION_LIMIT] = 10;
        }

        return $options;
    } // end getOptions

    /**
     * @override
     */
    protected function onRowCompleted($record)
    {
        $primaryColumnName = $this->getPrimaryColumnNameInStorage();
        
        $id = $record[$primaryColumnName];
        $this->processedStorageRecords[$id] = $id;
    } // end onRowComplete

    protected function onRowError($record, $exp)
    {
        $primaryColumnName = $this->getPrimaryColumnNameInStorage();

        $id = $record[$primaryColumnName];
        unset($this->processedStorageRecords[$id]);

        $errorColumnName = $this->getErrorColumnNameInStorage();
        $errorValue = $this->getErrorMessage($record, $exp);
        $errorValue = $this->db->quote($errorValue);
        $id = $this->db->quote($id);

        $sql = "UPDATE %s SET %s = %s WHERE %s = %s";
        $sql = sprintf(
            $sql,
            $this->getStorageName(),
            $errorColumnName,
            $errorValue,
            $primaryColumnName,
            $id
        );

        if ($this->db->inTransaction()) {
            $this->db->rollback();
        }

        $this->db->query($sql);
    } // end onRowError

    /**
     * Returns the error message.
     * You can override the method to return more informative message.
     *
     * @param array $record
     * @param Exception $exp
     * @return string
     */
    protected function getErrorMessage($record, $exp)
    {
        return $exp->getMessage();
    } // end getErrorMessage

    protected function getProcessedRecords()
    {
        return $this->processedStorageRecords;
    } // end getProcessedRecords
    
    /**
     * @override
     */
    protected function onSpoolCompleted()
    {
        if (!$this->processedStorageRecords) {
            return false;
        }
        
        foreach ($this->processedStorageRecords as &$value) {
            $value = $this->db->quote($value);
        }
        unset($value);
        
        $sql = "DELETE FROM %s WHERE %s IN (%s)";

        $sql = sprintf(
            $sql, 
            $this->getStorageName(),
            $this->getPrimaryColumnNameInStorage(),
            join(", ", $this->processedStorageRecords)
        );

        $this->db->query($sql);
        
        $this->processedStorageRecords = array();
        
    } // end onSpoolComplete
    
    abstract public function getStorageName(): string;
    
    public function getWorkerColumnNameInStorage(): string
    {
        return 'id_worker';
    }

    public function getPrimaryColumnNameInStorage(): string
    {
        return 'id';
    }

    public function getErrorColumnNameInStorage(): string
    {
        return 'error';
    }
    
    public function getSpoolExternalCondition(): string
    {
        return '';
    }

    public function getWorkerSteadyValue(): string
    {
        return $this->getOption('id');
    } // end getWorkerSteadyValue

    protected function getSpool(): array
    {
        return $this->getProvider()->getSpoolItems($this, $this->db);
    } // end getSpool

    public function getSpoolItemsLimit(): int
    {
        return (int) $this->getOption(static::OPTION_LIMIT);
    }

    public function getWorkerType(): string
    {
        return $this->getOption('type');
    }

    /**
     * Returns instance of provider.
     *
     * @return IWorkerProvider
     * @throws CronException
     */
    protected function getProvider(): IWorkerProvider
    {
        if (!$this->_provider) {
            throw new CronException("Undeclared provider");
        }

        return $this->_provider;
    } // end getProvider
}