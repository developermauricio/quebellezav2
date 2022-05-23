<?php

require_once 'bundle/store/action/EditAction.php';

class GrideditorAction extends EditAction
{
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $this->primaryKeyValue = $this->store->getPrimaryKeyValueFromRequest();
            
        $this->data = $this->store->loadRowByPrimaryKey($this->primaryKeyValue);

        if ($this->store->getPostParam('performPost')) {
            $this->onUpdate($response);
        } else {
            $response->isFlush = true;
            $this->onDisplayForm($response);
        }

        return true;
    } // end onStart

    /**
     * @param Response $response
     * @return array|null
     * @throws StoreException
     */
    public function onUpdate(Response &$response): ?array
    {
        $data = $this->getDataFromRequest();

        $field = $this->model->getFieldByName($data['field']);

        $value = $field->getValue($_REQUEST);

        $values = array(
            $field->getName() => $value
        );

        $this->store->updateByPrimaryKey($this->primaryKeyValue, $values);

        $displayValue = $this->getCellValue($field, $value, $this->data);

        $response->setType(Response::JSON_IFRAME);
        $response->setAction(Response::ACTION_CALLBACK);

        $response->name = 'Jimbo.gridEditorCell';
        $response->data = $data;
        $response->html = $displayValue;

        return $values;
    } // end onUpdate
    
    public function onDisplayForm(Response &$response): void
    {
        $data = $this->getDataFromRequest();

        $field = $this->model->getFieldByName($data['field']);
        
        $fieldName = $field->getName();
        
        $displayValue = $this->getCellValue(
            $field, 
            $this->data[$fieldName], 
            $this->data
        );
        
        $info = array(
            'primaryKeyValue' => $this->primaryKeyValue,
            'url'             => $this->getUrl($data)
        );
        
        $this->store->loadForeignKeys();
        
        $vars = array(
            'value'           => $this->getValueForField($field),
            'info'            => $info,
            'data'            => $data,
            'displayValue'    => $displayValue,
            'primaryKeyValue' => $this->primaryKeyValue,
            'store'           => &$this->store
        );
        
        $view = $this->store->getView();

        foreach ($vars as $key => $value) {
            $view->$key = $value;
        }
        
        $response->content = $view->fetch('form_grideditor.php');
    } // end onDisplayForm
    
    protected function getRequestFields(): ?array
    {
        $requestFields = array(
           Store::ACTION_KEY_IN_REQUEST => array(
                'type'     => self::FIELD_TYPE_STRING_NULL,
                'required' => true
           ), 
           
           Store::PRIMARY_KEY_IN_REQUEST => array(
                'type'    => self::FIELD_TYPE_STRING_NULL,
                'required' => true
           ), 
           
           'field' => array(
                'type'    => self::FIELD_TYPE_STRING_NULL,
                'required' => true
           )
        );
        
        return $requestFields;
    } // end getRequestFields
    
    
}

