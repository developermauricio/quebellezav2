<?php

require_once 'bundle/store/action/InsertAction.php';

class EditAction extends InsertAction
{
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $vars = null;

        if (!$this->primaryKeyValue) {
            $primaryKeyValue = $this->store->getPrimaryKeyValueFromRequest();

            $this->load($primaryKeyValue);
        }

        if ($this->isExec()) {
            $vars = $this->onUpdate($response);
        }

        $view = $this->store->getView();
        $view->onActionResponse($this, $response, $vars);

        return true;
    } // end onStart
    
    /**
     * @param $primaryKeyValue
     * @param bool $isCheckPermission
     * @return $this
     * @throws PermissionsException
     */
    public function load($primaryKeyValue, bool $isCheckPermission = false)
    {
        $this->primaryKeyValue = $primaryKeyValue;

        $this->data = $this->store->loadRowByPrimaryKey(
            $primaryKeyValue, 
            $isCheckPermission
        );
        
        $target = array(
            'values'          => &$this->data,
            'primaryKeyValue' => $this->primaryKeyValue,
            'store'           => &$this->store,
        );
        
        $event = new FestiEvent(Store::EVENT_ON_LOAD_ACTION_VALUES, $target);
        $this->store->dispatchEvent($event);
        
        $this->store->loadForeignKeys();
        
        return $this;
    } // end load
    
    /**
     * @param array $search
     * @return $this
     */
    public function loadRow(array $search)
    {
        $this->data = $this->store->loadRow($search);
        
        $primaryKey = $this->model->getPrimaryKey();
        
        $this->primaryKeyValue = $this->data[$primaryKey];
        
        return $this;
    } // end loadRow
    
    /**
     * @param AbstractField $field
     * @return false|string
     * @throws SystemException
     */
    protected function getValueForField(AbstractField &$field)
    {
        $name = $field->getName();
        $value = isset($this->data[$name]) ? $this->data[$name] : null;
        if (is_null($value) && $field->get('value')) {
            $value = $field->get('value');
        }

        return $field->getEditInput($value, $this->data);
    } // end getValueForField
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_EDIT;
    } // end getActionName
}