<?php

/**
 * Trait SqlColumnNameProxy. Column Name logic.
 */
trait SqlColumnNameProxy
{
    /**
     * Returns column name for Handler field.
     *
     * @param AbstractField $field
     * @throws SystemException
     */
    protected function getHandlerColumnName(AbstractField $field)
    {
        return $field->callHandlerMethod(array(&$field), 'ColumnName');
    } // end getHandlerColumnName

    /**
     * Returns column name for sql field. Use to sub query logic.
     *
     * @param AbstractField $field
     * @return string
     */
    protected function getSqlColumnName(AbstractField $field): string
    {
        return '('.$field->get('query') .') AS '.$field->getName();
    } // end getSqlColumnName

    /**
     * Returns name for many to many field.
     *
     * @param AbstractField $field
     * @return string
     */
    abstract protected function getMany2manyColumnName(AbstractField $field): string;

    /**
     * Returns column name for foreign key filed.
     *
     * @param AbstractField $field
     * @return array
     * @throws SystemException
     */
    protected function getForeignKeyColumnName(AbstractField $field): array
    {
        $fieldName = $field->getName();
        $foreignValueField = $field->get('foreignValueField');
        $foreignTableName = $field->getForeignStoreName();
        $aliasTableName = $field->get('alias');

        if ($aliasTableName) {
            $foreignTableNameAlias = $aliasTableName;
        } else {
            $foreignTableNameAlias = $foreignTableName;
        }

        $foreignValueColumn = $this->getColumnNameFromForeignKeyFieldName(
            $foreignValueField,
            $foreignTableNameAlias,
            $fieldName
        );

        $foreignKeyField = $field->get('foreignKeyField');
        if (strpos($foreignKeyField, StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR) === false) {
            $foreignKeyField = $foreignTableNameAlias.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$foreignKeyField;
        }

        return array(
            $foreignValueColumn,
            $foreignKeyField." as _foreign_".$field->getName()
        );
    } // end getForeignKeyColumnName

    /**
     * Returns column name for generic field.
     *
     * @param AbstractField $field
     * @return string
     */
    protected function getDefaultColumnName(AbstractField $field): string
    {
        $columnName = $field->getName();

        $tableName = $field->get(AbstractField::OPTION_STORE);
        if (!$tableName) {
            $tableName = $this->store->getName();
        }

        $expression = $field->get('expression');
        if ($expression) {
            return $expression." as ".$columnName;
        }

        return $tableName.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$columnName;
    } // end _getDefaultColumnName

    /**
     * Returns column name by DGS field.
     *
     * @param AbstractField $field
     * @param string $methodPostfix
     * @return mixed|string|null
     * @throws StoreException
     */
    protected function getColumnNameByField(AbstractField $field, string $methodPostfix)
    {
        $fieldType = $field->getType();
        $methodName = 'get'.ucfirst($fieldType).$methodPostfix;

        $method = array(&$this, $methodName);

        if (is_callable($method)) {
            $columnName = call_user_func_array($method, array($field));
        } else {
            $columnName = $this->getDefaultColumnName($field);
        }

        return $columnName;
    } // end getColumnNameByField

    /**
     * Returns column name for foreign key field.
     *
     * @param string $field
     * @param string $table
     * @param string|null $as
     * @return string
     */
    protected function getColumnNameFromForeignKeyFieldName(
        string $field, string $table, string $as = null
    ): string
    {

        $value = $table.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$field;
        if (strpos($field, '(') !== false || strpos($field, '.') !== false) {
            $value = $field;
        }

        if ($as) {
            $value .= ' as '.$as;
        }

        return $value;
    } // end getColumnNameFromForeignKeyFieldName

    /**
     * Returns column name for composite field.
     *
     * @param AbstractField $field
     * @return string
     * @throws SystemException
     */
    public function getCompositeColumnName(AbstractField $field): string
    {
        $options = $field->getOptions();
        $columns = array();

        foreach ($options as $column) {

            if (!empty($column[AbstractField::OPTION_STORE])) {
                $tableName = $column[AbstractField::OPTION_STORE];
            } else {
                $tableName = $this->store->getName();
            }

            $columnName = $tableName.StoreProxy::STORE_NAME_AND_COLUMN_SEPARATOR.$column['value'];
            $columns[] = "'".$column['value'].":', ".$columnName;
        }

        $concatValues = join(", '".CompositeField::VALUES_SEPARATOR."', ", $columns);

        return "CONCAT(".$concatValues.") as ". $field->getName();
    } // end getCompositeColumnName
}