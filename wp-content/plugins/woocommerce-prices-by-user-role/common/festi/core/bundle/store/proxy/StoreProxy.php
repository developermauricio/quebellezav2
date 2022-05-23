<?php

require_once 'bundle/store/proxy/IProxyRepository.php';
require_once 'bundle/store/proxy/IProxy.php';
require_once 'bundle/store/proxy/IStoreProxyForeignKeyValuesListener.php';
require_once 'bundle/store/proxy/FiltersProxy.php';
require_once 'bundle/store/proxy/ForeignKeyProxy.php';
require_once 'bundle/store/proxy/BaseStoreProxy.php';

/**
 * Class StoreProxy. Abstract class to describe logic between DGS and physical storage.
 */
abstract class StoreProxy implements IProxy
{
    use FiltersProxy, ForeignKeyProxy, BaseStoreProxy;

    const STORE_NAME_AND_COLUMN_SEPARATOR = ".";

    /**
     * Reference to DGS.
     *
     * @var Store
     */
    protected $store;

    /**
     * Reference to DGS Model.
     *
     * @var StoreModel
     */
    protected $model;

    /**
     * Reference to storage connection.
     *
     * @var IDataAccessObject
     */
    protected $connection;

    /**
     * Total records into DGS storage.
     *
     * @var int
     */
    private $_totalRows;

    /**
     * True when we use use pagination logic at list.
     *
     * @var bool
     */
    private $_isUseLimit = true;

    /**
     * StoreProxy constructor.
     *
     * @param Store $store
     */
    public function __construct(Store &$store)
    {
        $this->store = &$store;
        $this->model = &$store->getModel(); 
        $this->connection = &$store->getConnection();
    } // end __construct

    /**
     * Returns true if field isn't use into list.
     *
     * @param AbstractField $field
     * @param bool $isAllColumns
     * @return bool
     */
    protected function isHiddenColumnInList(AbstractField $field, bool $isAllColumns): bool
    {
        return (!$field->isShow() && !$isAllColumns) || $field->isCustom();
    } // end isHiddenColumnInList

    /**
     * Returns true if field has aggregate function like SUM, AVG, COUNT etc. or sub query into field name.
     *
     * @param string $fieldName
     * @param array|null $matches
     * @return bool
     */
    public static function isComplexField(string $fieldName, array &$matches = null): bool
    {
        return static::isExpression($fieldName) &&
               preg_match("#as\s([\.a-zA-z0-9]+)#", $fieldName, $matches);
    } // end isComplexField

    /**
     * Returns true if filed name has expression.
     *
     * @param $fieldName
     * @return bool
     */
    public static function isExpression($fieldName)
    {
        return strpos($fieldName, '(') !== false;
    } // end isExpression

    /**
     * Returns true if store name included into field name.
     *
     * @param string $fieldName
     * @return bool
     */
    public static function hasStoreName(string $fieldName): bool
    {
        return strpos($fieldName, static::STORE_NAME_AND_COLUMN_SEPARATOR) !== false;
    } // end hasStoreName

    /**
     * Returns order by expression.
     *
     * @return string|null
     * @throws StoreException
     */
    protected function getQueryOrderDirection(): ?string
    {
        $fieldName = $this->store->getOrderByFieldName();

        if (!$fieldName) {
            return null;
        }

        $orderByDirection = $this->store->getOrderByDirection();

        $field = $this->model->getFieldByName($fieldName);
        if ($field) {
            $fieldName = $this->getColumnNameByOrderField($field);
        }

        return $fieldName." ".$orderByDirection;
    } // end getQueryOrderDirection

    /**
     * @param AbstractField $field
     * @return string
     * @throws StoreException
     */
    protected function getColumnNameByOrderField(AbstractField $field): string
    {
        $columnName = $this->getColumnNameByFilter($field);
        if (!$columnName) {
            return $field->getName();
        }

        return $columnName;
    } // end getColumnNameByOrderField

    /**
     * Returns total rows count.
     *
     * @override
     * @return int
     */
    public function getCount(): int
    {
        return $this->_totalRows;
    } // end getCount

    /**
     * Set a total rows count.
     *
     * @param int $total
     */
    public function setCount(int $total): void
    {
        $this->_totalRows = $total;
    } // end setCount

    /**
     * Set true if you would like disable use pagination in load list values.
     *
     * @param bool $isUseLimit
     */
    public function setUseLimit(bool $isUseLimit): void
    {
        $this->_isUseLimit = $isUseLimit;
    } // end setUseLimit

    /**
     * Returns true if a list use pagination logic.
     *
     * @return bool
     */
    public function isUseLimit(): bool
    {
        return $this->_isUseLimit;
    } // end isUseLimit

    /**
     * Returns concat condition statement.
     *
     * @param array $condition
     * @return string
     */
    abstract protected function getConcatCondition(array $condition): string;

}