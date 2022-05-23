<?php

namespace core\dao;

class Query implements IQuery
{
    /**
     * @var \IDataAccessObject
     */
    private $_connection;

    /**
     * @var array
     */
    private $_columns;

    /**
     * @var string
     */
    private $_from;

    /**
     * @var array
     */
    private $_search;

    /**
     * @var array
     */
    private $_groupBy;

    /**
     * @var array
     */
    private $_orderBy;

    /**
     * @var int
     */
    private $_offset;

    /**
     * @var int
     */
    private $_limit;

    /**
     * @var array
     */
    private $_joins;

    /**
     * @var array
     */
    private $_having;

    public function __construct(\IDataAccessObject $connection)
    {
        $this->_connection = $connection;
        $this->_columns = array();
        $this->_search = array();
        $this->_groupBy = array();
        $this->_orderBy = array();
        $this->_joins = array();
        $this->_having = array();
    }

    public function column(string $name, string $alias = null): IQuery
    {
        $columnName = $alias ?? $name;
        $this->_columns[$columnName] = $name;

        return $this;
    } // end column

    public function from(string $name): IQuery
    {
        $this->_from = $name;

        return $this;
    }

    public function where(array $search): IQuery
    {
        $this->_search = array_merge_recursive($this->_search, $search);

        return $this;
    }

    public function having(array $search): IQuery
    {
        $this->_having = array_merge_recursive($this->_having, $search);

        return $this;
    }

    public function join(string $join): IQuery
    {
        $this->_joins[] = $join;

        return $this;
    }

    public function joins(array $joins): IQuery
    {
        $this->_joins = array_merge_recursive($this->_joins, $joins);

        return $this;
    }

    public function groupBy(string $columnName): IQuery
    {
        $this->_groupBy[$columnName] = $columnName;

        return $this;
    }

    public function orderBy(string $columnName): IQuery
    {
        $this->_orderBy[$columnName] = $columnName;

        return $this;
    }

    public function limit(int $limit, int $offset = null): IQuery
    {
        $this->_offset = $offset;
        $this->_limit = $limit;

        return $this;
    }

    public function getQuery(): string
    {
        $driver = $this->_connection->getDriver();

        $where = $this->_connection->getSqlCondition($this->_search);
        $having = $this->_connection->getSqlCondition($this->_having);

        return $driver->createSelectQuery(
            $this->_columns,
            $this->_from,
            $this->_joins,
            $where,
            $this->_orderBy,
            $this->_limit,
            $this->_offset,
            $this->_groupBy,
            $having
        );
    }

}