<?php

use \core\dgs\action\StoreActionException;

class ForeignKeyLoadAction extends AbstractAction
{
    private $_values;

    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $data = $this->getDataFromRequest();

        if ($data['ajaxChild'] && $data['ajaxParent']) {
            return $this->_onStartLoadChildFieldValues($response, $data);
        } else if ($data['fieldName']) {
            return $this->_onStartLoadFieldValues($response, $data);
        }

        throw new StoreActionException("Undefined request", $this);
    } // end onStart

    private function _onStartLoadFieldValues(Response &$response, array $data) : bool
    {
        // TODO: We have to make refactoring of field name's
        $fieldName = preg_replace("#^__#Umis", "", $data['fieldName']);
        $field = $this->model->getFieldByName($fieldName);

        if (!$field) {
            die("ooooo");
            throw new StoreActionException("Not found field with name: ".$data['fieldName'], $this);
        }

        if ($field instanceof ForeignKeyField) {
            $options = $field->getAttributes();
            $options['foreignLimit'] = $options['autocompleteLimit'];

        } else {
            $options = array(
                'foreignKeyField'   => 'keyValue',
                'foreignValueField' => $field->getName(),
                'foreignTable'      => $field->getStoreName(),
                'unique'            => true
            );
        }

        if ($data['term']) {
            $options['search'] = array(
                $options['foreignValueField'].'&like' => '%'.$data['term'].'%'
            );
        } else if ($data['value']) {
            $options['search'] = array(
                $options['foreignKeyField'].'&IN' => explode(",", $data['value'])
            );
        }

        $this->_values = $this->store->getProxy()->loadForeignValues($options);

        $results = array();
        foreach ($this->_values as $key => $value) {
            $results[] = array(
                'key'   => $value,
                'value' => $key
            );
        }

        // TODO: Double check
        $response->setType(Response::JSON);

        $response->results = $results;

        return true;
    } // end _onStartLoadFieldValues

    private function _onStartLoadChildFieldValues(Response &$response, array $data) : bool
    {
        $view = $this->store->getView();
        $view->info = $data;

        if (!array_key_exists('value', $_REQUEST)) {
            throw new StoreActionException("Undefined value param");
        }

        $data['value'] = $_REQUEST['value'];

        $parentField = $this->model->getFieldByName($data['ajaxParent']);

        $childsFields = explode('|', $data['ajaxChild']);

        $childFieldsValues = $this->store->getRequestParam('ajaxChildValues');

        $response->content = "";
        foreach ($childsFields as $fieldName) {
            $childField = $this->model->getFieldByName($fieldName);

            $options = $childField->getAttributes();

            if (!empty($options['ajaxParentField'])) {
                $options['ajaxParentColumn'] = $options['ajaxParentField'];
            }

            $options['ajaxParentValue'] = $data['value'];

            $this->_values = $this->store->getProxy()->loadForeignValues($options);

            $view->values = $this->_values;
            $view->childValue = $childFieldsValues[$fieldName];
            $view->fieldName = $fieldName;

            // FIXME: actions/foreign_key_load.php
            $response->content .= $view->fetch('actions/foreign_key_load.php');
        }

        return true;
    } // end _onStartChildValuesLoad

    /**
     * Returns values.
     *
     * @return array|null
     */
    public function getValues(): ?array
    {
        return $this->_values;
    } // end getValues

    /**
     * @override
     * @return array|null
     */
    protected function getRequestFields(): ?array
    {
        $requestFields = array(
            'ajaxChild' => array(
                'type'     => static::FIELD_TYPE_STRING_NULL,
                //'required' => true
            ),

            'ajaxParent' => array(
                'type'    => static::FIELD_TYPE_STRING_NULL,
                //'required' => true
            ),
            'fieldName' => static::FIELD_TYPE_STRING_NULL,
            'term'      => static::FIELD_TYPE_STRING_NULL,
            'value'     => static::FIELD_TYPE_STRING_NULL
        );
        
        return $requestFields;
    } // end getRequestFields
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_FOREIGN_KEY_LOAD;
    }

    /**
     * @override
     */
    public function isExec()
    {
        return true;
    } // end isExec
    
}
