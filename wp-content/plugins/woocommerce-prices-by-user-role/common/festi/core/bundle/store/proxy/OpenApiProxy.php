<?php

namespace core\dgs\proxy;

use Store;
use StoreProxy;

require_once 'bundle/util/ApiUtils.php';

/**
 * Class OpenApiProxy
 * @package core\dgs\proxy
 * @see https://swagger.io/docs/specification/about/
 */
abstract class OpenApiProxy extends StoreProxy
{
    /**
     * @var array
     */
    private $_responseHeaders;

    public function __construct(Store &$store)
    {
        parent::__construct($store);

        $this->_responseHeaders = array();
    }

    protected function getPrimaryKeyValueFromResponse(array $response)
    {
        $primaryKey = $this->model->getPrimaryKey();
        if (!array_key_exists($primaryKey, $response)) {
            throw new \StoreException("Undefined primary key value in API response");
        }

        return $response[$primaryKey];
    } // end getPrimaryKeyValueFromResponse

    /**
     * @param bool $isAllColumns
     * @return array
     * @throws \SystemException
     */
    public function loadListValues(bool $isAllColumns = false): array
    {
        $storeName  = $this->getOriginalStoreName();
        $columns    = $this->getQueryColumns($isAllColumns);
        $conditions = $this->getQueryWhere();
        $orderBy    = $this->getListValuesOrderBy();
        $limit      = $this->getQueryLimit();

        return $this->loadRemoteListValues($storeName, $columns, $conditions, $orderBy, $limit);
    }

    protected function getListValuesOrderBy(): array
    {
        $orderBy = $this->getQueryOrderDirection();

        $orderChunk = explode(" ", $orderBy);

        $columnName = empty($orderChunk[0]) ? $this->store->getPrimaryKey() : $orderChunk[0];

        $chunk = explode(".", $columnName);
        $fieldName = empty($chunk[1]) ? $chunk[0] : $chunk[1];

        return array(
            'orderBy'          => $fieldName,
            'orderByDirection' => empty($orderChunk[1]) ? 'asc' : strtolower($orderChunk[1])
        );
    } // end getListValuesOrderBy

    abstract function getBaseEndpointUrl(...$params): string;

    protected function getExternalEndpointUrlParams(...$params): array
    {
        return array();
    } // end getExternalEndpointUrlParams

    protected function getEndpointUrl(...$params): string
    {
        $url = $this->getBaseEndpointUrl(...$params);

        $requestParams = array();
        foreach ($params as $param) {

            if (!is_array($param)) {
                $url .= $param.'/';
            } else {
                $requestParams = array_merge_recursive($requestParams, $param);
            }
        }

        $externalParams = $this->getExternalEndpointUrlParams();

        $requestParams = array_merge_recursive($requestParams, $externalParams);

        return $url.'?'.http_build_query($requestParams);
    } // end getEndpointUrl

    protected function getHeaders(string $storeName): array
    {
        return array();
    } // end getHeaders

    protected function loadRemoteListValues(
        string $storeName, array $columns, array $conditions, array $orderBy, array $limit
    ): array
    {
        $url = $this->getEndpointUrl($storeName);
        $url = $this->getListValuesEndpointUrl($url, $columns, $conditions, $orderBy, $limit);

        $method = $this->getListValuesMethod();

        $headers = $this->getHeaders($storeName);

        $response = $this->send($url, null, $method, $headers);

        $rows = $this->getListValuesRows($response);
        $rows = $this->getListValues($rows, $columns);

        $total = $this->getListValuesTotalRows($response);
        $this->setCount($total);

        $this->prepareForeignKeyFieldsValues($rows);

        return $rows;
    } // end loadData

    protected function getResponseHeaders(): array
    {
        return $this->_responseHeaders;
    } // end getResponseHeaders

    protected function prepareForeignKeyFieldsValues(array &$rows)
    {
        $fields = $this->store->getModel()->getFields();

        $foreignFields = array();
        foreach ($fields as $field) {
            if ($field instanceof \ForeignKeyField) {
                $foreignFields[] = $field;
            }
        }

        $foreignFieldsValues = array();
        foreach ($rows as $row) {
            foreach ($foreignFields as $field) {
                $name = $field->getName();
                $foreignFieldsValues[$name][$row[$name]] = $row[$name];
            }
        }

        foreach ($foreignFields as $field) {
            $name = $field->getName();
            $values = $foreignFieldsValues[$name];

            $keys = $this->getForeignKeyFieldKeys($field, $values);

            foreach ($keys as $key => $value) {
                $foreignFieldsValues[$name][$key] = $value;
            }
        }

        foreach ($rows as &$row) {
            foreach ($foreignFields as $field) {
                $name = $field->getName();

                $foreignKey = $row[$name];
                $foreignValue = $foreignFieldsValues[$name][$foreignKey];
                $row[$name] = $foreignValue;
                $row[ '_foreign_'.$name] = $foreignKey;
            }
        }
    } // end prepareForeignKeyFieldsValues

    protected function getForeignKeyFieldKeys(\ForeignKeyField $field, array $values): array
    {
        $storeName   = $field->getForeignStoreName();
        $keyColumn   = $field->getForeignFieldKey();
        $valueColumn = $field->getForeignFieldValue();

        $result = array();
        foreach ($values as $value) {
            $url = $this->getEndpointUrl($storeName, $value);

            $method = $this->getListValuesMethod();

            $headers = $this->getHeaders($storeName);

            $response = $this->send($url, null, $method, $headers);

            $rows = $this->getForeignKeyValuesRows($storeName, array($response), $keyColumn, $valueColumn);

            $result = $result + $rows;
        }

        return $result;
    } // end getForeignKeyFieldKeys

    protected function getListValuesTotalRows(array $response): int
    {
        if ($this->isListValuesTotalInHeaders()) {
            $headers = $this->getResponseHeaders();
            return $this->getListValuesTotalFromHeaders($headers);
        }

        if ($this->isListValuesTotalInResponse()) {
            return $this->getListValuesTotalFromResponse($response);
        }

        throw new \StoreException("Undefined total of list values");
    } // end getListValuesTotalRows

    protected function isListValuesTotalInHeaders(): bool
    {
        return false;
    } // end isListValuesTotalInHeaders

    protected function isListValuesTotalInResponse(): bool
    {
        return false;
    } // end isListValuesTotalInResponse

    public function getListValuesTotalFromHeaders(array $headers): int
    {
        throw new \StoreException("Unimplemented method");
    }

    public function getListValuesTotalFromResponse(array $response): int
    {
        throw new \StoreException("Unimplemented method");
    }

    protected function getListValues(array $rows, array $columns): array
    {
        $result = array();

        foreach ($rows as $row) {
            $result[] = $this->getItemValues($row, $columns);
        }

        return $result;
    } // end getListValues

    protected function getItemValues(array $row, array $columns)
    {
        $item = array();
        foreach ($columns as $columnName) {
            $item[$columnName] = array_key_exists($columnName, $row) ? $row[$columnName] : null;
        }

        return $item;
    } // end getItemValues

    protected function getListValuesMethod(): string
    {
        return \ApiUtils::REQUEST_METHOD_GET;
    } // end getListValuesMethod

    protected function getInsertMethod(): string
    {
        return \ApiUtils::REQUEST_METHOD_POST;
    } // end getInsertMethod

    protected function getRemoveMethod(): string
    {
        return \ApiUtils::REQUEST_METHOD_DELETE;
    } // end getRemoveMethod

    protected function getUpdateMethod(): string
    {
        return \ApiUtils::REQUEST_METHOD_PUT;
    } // end getUpdateMethod

    protected function getListValuesEndpointUrl(
        string $baseUrl, array $columns, array $conditions, array $orderBy, array $limit
    ): string
    {
        $params = $this->convertConditionsToRequest($conditions);

        $this->appendListValuesLimitRequestParam($params, $limit);

        $this->appendListValuesOrderByRequestParam($params, $orderBy);

        return $baseUrl.'&'.http_build_query($params);
    } // end getListValuesUrl

    protected function appendListValuesOrderByRequestParam(array &$params, array $orderBy): void
    {
        foreach ($orderBy as $key => $value) {
            $params[$key] = $value;
        }
    }

    protected function appendListValuesLimitRequestParam(array &$params, array $limit): void
    {
        foreach ($limit as $key => $value) {
            $params[$key] = $value;
        }
    }

    protected function send(string $url, ?array $data = null, ?string $method = null, ?array $headers = null): ?array
    {

        return \ApiUtils::send($url, $data, $method, $headers, $this->_responseHeaders);
    } // end send

    /**
     * Override this method to return rows from api response.
     *
     * @param array $response
     * @return array
     */
    protected function getListValuesRows(array $response): array
    {
        return $response;
    } // end getListValuesResponse

    public function loadRowByPrimaryKey($id): ?array
    {
        $storeName = $this->getOriginalStoreName();

        $url = $this->getEndpointUrl($storeName, $id);

        $method = $this->getListValuesMethod();

        $headers = $this->getHeaders($storeName);

        $response = $this->send($url, null, $method, $headers);

        $columns = $this->getQueryColumns();

        return $this->getItemValues($response, $columns);
    } // end loadRowByPrimaryKey

    public function removeByPrimaryKey($primaryKeyValue): int
    {
        $storeName = $this->getOriginalStoreName();
        $url = $this->getEndpointUrl($storeName, $primaryKeyValue);

        $method = $this->getRemoveMethod();

        $headers = $this->getHeaders($storeName);

        $this->send($url, null, $method, $headers);

        return true;
    } // end removeByPrimaryKey

    public function insert(array $values)
    {
        $storeName = $this->getOriginalStoreName();
        $url = $this->getEndpointUrl($storeName);

        $method = $this->getInsertMethod();

        $headers = $this->getHeaders($storeName);

        $response = $this->send($url, $values, $method, $headers);

        return $this->getPrimaryKeyValueFromResponse($response);
    } // end insert

    public function updateByPrimaryKey($primaryKeyValue, array $values): bool
    {
        $storeName = $this->getOriginalStoreName();

        $url = $this->getEndpointUrl($storeName, $primaryKeyValue);

        $method = $this->getUpdateMethod();

        $headers = $this->getHeaders($storeName);

        $response = $this->send($url, $values, $method, $headers);

        $this->getPrimaryKeyValueFromResponse($response);

        return true;
    } // end updateByPrimaryKey

    /**
     * @override
     * @return array
     */
    protected function getQueryLimit()
    {
        $rowsPerPage = $this->store->getRowsPerPageCount();
        
        $pageIndex = $this->store->getCurrentPageIndex();
        
        $startLimit = ($pageIndex - 1) * $rowsPerPage;
        
        return array(
            'startLimit'  => $startLimit,
            'rowsPerPage' => $rowsPerPage
        );
    } // end getQueryLimit

    protected function convertConditionsToRequest(array $search): array
    {
        return $search;
    } // end convertConditionsToRequest

    public function loadRow(array $search): ?array
    {
        $storeName = $this->getOriginalStoreName();

        $params = $this->convertConditionsToRequest($search);

        $url = $this->getEndpointUrl($storeName, $params);

        $method = $this->getListValuesMethod();

        $headers = $this->getHeaders($storeName);

        $response = $this->send($url, null, $method, $headers);

        $columns = $this->getQueryColumns();

        return $this->getItemValues($response, $columns);
    }

    public function isBegin(): bool
    {
        return true;
    }
    
    public function begin(): bool
    {
        return true;
    }
    
    public function commit(): void
    {
    }
    
    public function rollback(): void
    {
    }
    
    public function removeAllManyToManyValuesByPrimaryKey($primaryKeyValue): bool
    {
        return true;
    }
    
    public function search(array $search): array
    {
        $storeName = $this->getOriginalStoreName();

        $params = $this->convertConditionsToRequest($search);

        $url = $this->getEndpointUrl($storeName, $params);

        $method = $this->getListValuesMethod();

        $headers = $this->getHeaders($storeName);

        $response = $this->send($url, null, $method, $headers);

        $columns = $this->getQueryColumns();

        $rows = $this->getListValuesRows($response);

        return $this->getListValues($rows, $columns);
    }

    /**
     * Load values form foreignKey fields. Result saved to $field->keyData.
     *
     * @param \ForeignKeyField $field
     * @return bool
     * @throws \StoreException
     */
    public function loadForeignKeyValues(\ForeignKeyField &$field): bool
    {
        $storeName   = $field->getForeignStoreName();
        $keyColumn   = $field->getForeignFieldKey();
        $valueColumn = $field->getForeignFieldValue();

        $search = null;
        if ($field->get('valuesWhere')) {
            $search = $this->_convertConditionToArray($field->get('valuesWhere'));
        } else if ($field->get('where')) {
            $search = $this->_convertConditionToArray($field->get('where'));
        }

        $orderBy = $field->get('orderBy');
        if ($orderBy) {
            $orderBy = array($orderBy);
        }

        $info = array(
            'columns' => array(
                'key'   => &$keyColumn,
                'value' => &$valueColumn
            ),
            'table' => &$storeName,
            'where' => &$search,
            'order' => &$orderBy,
            'what'  => $this->store->getAction(),
            'field' => &$field,
            'values' => null
        );

        $this->firePrepareStoreForeignKeyFieldValuesCallback($this->store, $field, $info);

        if (!is_null($info['values'])) {
            $field->setValuesList($info['values']);
            return true;
        }

        $url = $this->getEndpointUrl($storeName);

        $method = $this->getListValuesMethod();

        $headers = $this->getHeaders($storeName);

        $response = $this->send($url, null, $method, $headers);

        $result = $this->getForeignKeyValuesRows($storeName, $response, $keyColumn, $valueColumn);

        $field->setValuesList($result);

        return true;
    } // end loadForeignKeyValues

    protected function getForeignKeyValuesRows(
        string $storeName, array $response, string $keyColumn, string $valueColumn
    ): array
    {
        $rows = $this->getListValuesRows($response);

        $result = array();
        foreach ($rows as $row) {
            if (!array_key_exists($keyColumn, $row)) {
                throw new \StoreException("Undefined required keys in foreignKey values");
            }

            $result[$row[$keyColumn]] = array_key_exists($valueColumn, $row) ? $row[$valueColumn] : null;
        }

        return $result;
    } // end getForeignKeyValuesRows
    
    public function getQueryColumns(bool $isAllColumns = false): array
    {
        $primaryKey = $this->store->getPrimaryKey();

        $fields = array();

        $fields[] = $primaryKey;

        foreach ($this->model->getFields() as $field) {

            if ($this->isHiddenColumnInList($field, $isAllColumns)) {
                continue;
            }

            $fields[] = $field->getName();
        }

        $fields = array_unique($fields);

        return $fields;
    } // end getQueryColumns

    /**
     * @override
     * @return array
     */
    public function getQueryWhere(): array
    {
        $search = $this->model->getSearch();

        foreach ($this->model->getFields() as $field) {
            $condition = $field->getQueryWhere();
            if ($condition) {
                $fieldSearch = $this->_convertConditionToArray($condition);
                $search = array_merge_recursive($search, $fieldSearch);
            }

            $filterCondition = $this->getFilterConditionsByField($field);
            if ($filterCondition) {
                $search = array_merge_recursive($search, $filterCondition);
            }
        }

        return $search;
    }

    private function _convertConditionToArray($condition): array
    {
        $conditions = array_map('trim', explode("AND", $condition));

        $search = array();

        foreach ($conditions as $condition) {
            $regExp = "#(?<key>[a-zA-Z\.\s_]+)(?<operation>[=><]|like)(?<value>.+$)#Umis";
            if (preg_match($regExp, $condition, $matches)) {
                $operation = $matches['operation'];
                $op = ($operation == "=") ? "" : '&'.$operation;
                $search[trim($matches['key']).$op] = trim($matches['value']);
            }
        }

        return $search;
    } // end _convertConditionToArray

    public function loadForeignAssigns($primaryValue, array $options): array
    {
        throw new \StoreException("Unsupportable method");
    }

    public function loadForeignValues(array $options): array
    {
        throw new \StoreException("Unsupportable method");
    }

    public function loadAggregations(): array
    {
        throw new \StoreException("Unsupportable method");
    }

    public function createAuditTable($auditTableName, $originalTableName): bool
    {
        throw new \StoreException("Unsupportable method");
    }

    public function updateManyToManyValues(\Many2manyField $item, int $id, array $values): bool
    {
        throw new \StoreException("Unsupportable method");
    }

    public function getQueryJoins(array $columns): array
    {
        throw new \StoreException("Unsupportable method");
    }
}