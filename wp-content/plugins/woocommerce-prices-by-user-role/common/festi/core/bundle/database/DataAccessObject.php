<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'DatabaseException.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'IDataAccessObject.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'driver'.DIRECTORY_SEPARATOR.'IObjectDriver.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'driver'.DIRECTORY_SEPARATOR.'AbstractObjectDriver.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'ObjectAdapter.php';

require_once __DIR__.DIRECTORY_SEPARATOR.'query'.DIRECTORY_SEPARATOR.'IQuery.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'query'.DIRECTORY_SEPARATOR.'Query.php';


/**
 * Class DataAccessObject
 */
abstract class DataAccessObject implements IDataAccessObject
{
    const TYPE_MYSQL = 'mysql';
    const TYPE_MSSQL = 'mssql';
    const TYPE_PGSQL = 'pgsql';

    const DEFAULT_PORT_MYSQL = '3306';
    const DEFAULT_PORT_PGSQL = '5432';
    const DEFAULT_PORT_MSSQL = '1433';

    /**
     * @var array Instances of objects
     */
    private static $_instances;

    /**
     * @var ObjectAdapter Instance of adapter storage connection
     */
    protected $adapter;

    /**
     * Create an adapter instance by storage connection.
     *
     * @param mixed $connection Reference to storage connection
     * @return IDataAccessObject
     * @throws DatabaseException
     */
    public static function factory(&$connection): IDataAccessObject
    {
        if ($connection instanceof IDataAccessObject) {
            return $connection;
        }

        $className = static::getAdapterClassName($connection);
        if (!$className) {
            throw new DatabaseException('Not found an adapter class for storage connection.');
        }

        return static::create($className, $connection);
    } // end factory

    /**
     * Create DataAccessObject instance by class name.
     *
     * @param string $className
     * @param mixed $connection
     * @return IDataAccessObject
     * @throws DatabaseException
     */
    public static function create(string $className, $connection = null): IDataAccessObject
    {
        if (!class_exists($className)) {
            $adapterClassPath = __DIR__.DIRECTORY_SEPARATOR.$className.'.php';

            if (!include_once($adapterClassPath)) {
                throw new DatabaseException('Not found an object adapter class file: '.$adapterClassPath);
            }
        }

        return new $className($connection);
    } // end create

    /**
     * Returns adapter class name for storage connection.
     *
     * @param mixed $connection Reference to storage connection
     * @return string|null string
     */
    public static function getAdapterClassName(&$connection)
    {
        $adapterName = $className = null;
        $connectionClassName = get_class($connection);

        switch ($connectionClassName) {

            case 'W3TC\DbCache_Wpdb':
            case 'wpdb':
            case 'W3_Db':
                $adapterName = 'WPDB';
                break;

            case 'PDO':
                $adapterName = 'PDO';
                break;

            case 'Cassandra\DefaultSession':
                $adapterName = 'Cassandra';
                break;

            case 'MysqlHandlerSocket':
                $adapterName = 'HandlerSocket';
                break;

            default:
                $parentClassName = get_parent_class($connection);
    
                if ($parentClassName == 'MDB2_Driver_Common') {
                    $adapterName = 'MDB2';
                }
        }
        
        if ($adapterName) {
            $className = 'Object' . $adapterName . 'Adapter';
        }

        return $className;
    } // end getAdapterClassName

    /**
     * Returns objects instance by name
     *
     * @param $name entity name
     * @param $connection Reference to storage connection
     * @param bool|string $path Path to default objects directory (optional)
     * @return IDataAccessObject
     * @phan-return mixed
     * @throws DatabaseException
     */
    public static function &getInstance($name, &$connection, $path = false)
    {
        if (isset(self::$_instances[$name])) {
            return self::$_instances[$name];
        }

        self::$_instances[$name] = self::createInstance($name, $connection, $path);

        return self::$_instances[$name];
    } // end getInstance

    /**
     * Create an object instance.
     *
     * @param string $name Entity name
     * @param $connection Reference to storage connection
     * @param bool|string $path Path to default objects directory (optional)
     * @return IDataAccessObject
     * @phan-return mixed
     * @throws DatabaseException
     */
    public static function createInstance($name, &$connection, $path = false)
    {
        $adapter = self::factory($connection);
        
        $className = self::getClassName($name);

        if (!class_exists($className)) {
            $classFilePath = self::getClassPath($className, $path);
            if (!$classFilePath) {
                throw new DatabaseException('Not found a path to object class: '.$className);
            }

            if (!include_once($classFilePath)) {
                throw new DatabaseException('Not found object class file: '.$classFilePath);
            }
        }

        if (!class_exists($className)) {
            throw new DatabaseException('Not found object class: '.$className);
        }

        return new $className($adapter);
    } // end createInstance

    /**
     * @deprecated
     * @param $name
     * @param $db
     * @param bool $path
     * @return IDataAccessObject
     * @phan-return mixed
     * @throws DatabaseException
     */
    public static function getNewInstance($name, &$db, $path = false)
    {
        return self::createInstance($name, $db, $path);
    }

    /**
     * Returns the object class name by entity name.
     *
     * @param string $name Entity name
     * @return string
     */
    public static function getClassName($name)
    {
        if (defined('DAO_CLASS_POSTFIX')) {
            $postfix = DAO_CLASS_POSTFIX;
        } else {
            $postfix = "Object";
        }

        return $name.$postfix;
    } // end getClassName

    /**
     * Returns path to object class file.
     *
     * @param string $className Object class name
     * @param bool|string $path Path to default objects directory (optional)
     * @return bool|string
     */
    public static function getClassPath($className, $path = false)
    {
        // custom path
        if ($path) {
            $classFilePath = $path.DIRECTORY_SEPARATOR.$className.'.php';
            if (!file_exists($classFilePath)) {
                $path = false;
            }
        }

        // default system path
        if (!$path) {
            if (defined('DAO_CLASSES_PATH')) {
                $path = DAO_CLASSES_PATH;
            } else {
                // compatibility with old version
                $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.
                    DIRECTORY_SEPARATOR.'objects'.DIRECTORY_SEPARATOR;
                $path = realpath($path);
            }
        }

        $classFilePath = $path.DIRECTORY_SEPARATOR.$className.'.php';

        if (!file_exists($classFilePath)) {
            $classFilePath = false;
        }

        return $classFilePath;
    } // end getClassPath

    /**
     * DataAccessObject constructor.
     *
     * @param ObjectAdapter $adapter
     */
    public function __construct(&$adapter)
    {
        $this->adapter = $adapter;
    } // end __construct
    
    public function quote($obj, $type = null)
    {
        return $this->adapter->quote($obj, $type);
    } // end quote
    
    public function getRow($sql): array
    {
        return $this->adapter->getRow($sql);
    } // end getRow
    
    public function getAll($sql): array
    {
        return $this->adapter->getAll($sql);
    } // end getAll
    
    public function getOne($sql)
    {
        return $this->adapter->getOne($sql);
    } // end getOne
    
    public function quoteTableName($name)
    {
        return $this->adapter->quoteTableName($name);
    } // end quoteTableName
    
    public function quoteColumnName($name)
    {
        return $this->adapter->quoteColumnName($name);
    } // end quoteColumnName
    
    public function getCol($sql): array
    {
        return $this->adapter->getCol($sql);
    } // end getCol
    
    public function insert($table, $values, $is_update_dublicate = false)
    {
        return $this->adapter->insert($table, $values, $is_update_dublicate);
    } // end insert
    
    public function delete($table, $condition)
    {
        return $this->adapter->delete($table, $condition);
    } // end delete

    public function update($table, $values, $condition = array())
    {
        return $this->adapter->update($table, $values, $condition);
    } // end update

    public function query($sql)
    {
        return $this->adapter->query($sql);
    } // end query
    
    public function getAssoc($sql): array
    {
        return $this->adapter->getAssoc($sql);
    } // end getAssoc
    
    public function inTransaction(): bool
    {
        return $this->adapter->inTransaction();
    } // end inTransaction
    
    public function begin($isolationLevel = false)
    {
        return $this->adapter->begin($isolationLevel);
    } // end begin
    
    public function commit()
    {
        return $this->adapter->commit();
    } // end commit
    
    public function rollback()
    {
        return $this->adapter->rollback();
    } // end rollback
    
    public function getAllSplit(string $query, int $col, int $page): array
    {
        return $this->getDriver()->getSplitOnPages($this, $query, $col, $page);
    }// end getAllSplit
    
    public function searchByPage($sql, $condition, $orderBy, $col, $page)
    {
        $where = $this->getSqlCondition($condition);
        
        if ($where) {
            $sql .= " WHERE ".join(" AND ", $where); 
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY ".join(", ", $orderBy);
        }
        
        return $this->getAllSplit($sql, $col, $page);
    } // end searchByPage

    public function getSqlCondition($obj = array()) 
    {
         return $this->adapter->getSqlCondition($obj);
    } // end getSqlCondition
    
    public function getUpdateValues($values, $tableName = false)
    {
        return $this->adapter->getUpdateValues($values, $tableName);
    } // end getUpdateValues
    
    public function getInsertSQL($table, $values, $is_update_dublicate = false) 
    {
        return $this->adapter->getInsertSQL(
            $table,
            $values,
            $is_update_dublicate
        );
    } // end getInsertSQL
    
    public function getUpdateSQL($table, $values, $condition = array()) 
    {
        return $this->adapter->getUpdateSQL($table, $values, $condition);
    } // end getUpdateSQL
    
    public function massInsert($table, $values, bool $inForeach = false)
    {
        return $this->adapter->massInsert($table, $values, $inForeach);
    } // end massInsert
    
    public function getInsertID() 
    {
        return $this->adapter->getInsertID();
    } // end getInsertID

    /**
     * Returns sql query without where. The method should be overridden
     * 
     * @throws DatabaseException
     */
    protected function getSql()
    {
        throw new DatabaseException('Undefined method getSql', 2001);
    } // end getSql
    
    /**
     * Returns generate select sql query
     *
     * @param array $condition
     * @param string $selectSql
     * @return string
     */
    protected function getSelectSQL($condition, $selectSql, $orderBy = array())
    {
        return $this->adapter->getSelectSQL($condition, $selectSql, $orderBy);
    } // end getSelectSQL

    /**
     * Fetch rows returned from a query
     *
     * @param string|array $selectSql
     * @param array $condition
     * @param array|bool $orderBy
     * @param int $type
     * @return array
     * @throws DatabaseException
     */
    public function select(
        $selectSql, 
        $condition = array(), 
        $orderBy = array(), 
        $type = self::FETCH_ALL
    )
    {
        return $this->adapter->select($selectSql, $condition, $orderBy, $type);
    } // end select
    
    /**
     * Returns an array of filter fields
     * 
     * @param $search
     * @return array
     */
    public function getConditionFields($search)
    {
        return $this->adapter->getConditionFields($search);
    } // end getConditionFields
    
    public function getDatabaseType()
    {
        return $this->adapter->getDatabaseType();
    } // end getDatabaseType

    public function getTables()
    {
        return $this->adapter->getTables();
    } // end getTables

    public function setForeignKeyChecks($isEnable = true)
    {
        return $this->adapter->setForeignKeyChecks($isEnable);
    } // end setForeignKeyChecks

    public function deleteTable($table)
    {
        return $this->adapter->deleteTable($table);
    } // end deleteTable

    public function getTableIndexes($tableName): array
    {
        return $this->adapter->getTableIndexes($tableName);
    }

    public function getDateTimeValue(string $dateTime): string
    {
        return $this->adapter->getDateTimeValue($dateTime);
    }

    public function getDriver(): IObjectDriver
    {
        return $this->adapter->getDriver();
    }
}