<?php

require_once 'bundle/store/action/InsertAction.php';

class RemoveAction extends InsertAction
{
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $vars = null;
        try {
            $this->primaryKeyValue = $this->store
                                          ->getPrimaryKeyValueFromRequest();
            
            $isCheckPermission = true;
            
            if ($this->getStore()->isApiMode()) {
                $isCheckPermission = false;
            }
            
            $this->data = $this->store->loadRowByPrimaryKey(
                $this->primaryKeyValue,
                $isCheckPermission
            );

            $this->store->loadForeignKeys();

            if ($this->isExec()) {
                $vars = $this->onRemove($response);
            }

        } catch (Exception $exp) {
            $this->doHandleException($exp);
        } 
        
        $view = $this->store->getView();
        $view->onActionResponse($this, $response, $vars);

        return true;
    } // end onStart

    /**
     * @param Response $response
     * @return array|null
     * @throws StoreException
     */
    protected function onRemove(Response &$response): ?array
    {
        $this->store->begin();

        // XXX: Needed for update by reference via event
        $isUpdated = false;
        
        $this->updateInfo = array(
            'id'        => $this->primaryKeyValue,
            'action'    => $this->store->getAction(),
            'values'    => false,
            'data'      => $this->data,
            'response'  => &$response,
            'isUpdated' => &$isUpdated,
        );

        try {
            
            $this->event(Store::EVENT_BEFORE_REMOVE, $this->updateInfo);
            
            if (!$this->isCustomUpdate()) {
                $this->store->removeByPrimaryKey($this->primaryKeyValue);
            }
        
            $this->event(Store::EVENT_REMOVE, $this->updateInfo);
            
            // TODO: Deprecated
            if (!empty($this->lastErrorMessage)) {
                $this->setError($this->lastErrorMessage);
                $this->store->rollback();
                return null;
            }
        } catch (Exception $exp) {
            
            if ($exp instanceof DatabaseException) {
                $this->_doHandleDatabaseException($exp);
            }
            
            if (!$this->isCustomUpdate()) {
                $this->doHandleException($exp);
                return null;
            }
        }
        
        if ($this->store->isBegin()) {
            $this->store->commit();
        }

        return $this->updateInfo;
    } // end onRemove
    
    private function _doHandleDatabaseException($dbExp)
    {
        if ($dbExp->getCode() == DatabaseException::ERROR_CONSTRAINT) {
            $this->event(Store::EVENT_ON_REMOVE_INTEGRITY, $this->updateInfo);
        }

        return false;
    } // end _doHandleDatabaseException
    
    protected function getValueForField(AbstractField &$field)
    {
        $name = $field->getName();
        $value = isset($this->data[$name]) ? $this->data[$name] : null;
        return $field->displayRO($value);
    } // end getValueForField
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_REMOVE;
    } // end getActionName
}
