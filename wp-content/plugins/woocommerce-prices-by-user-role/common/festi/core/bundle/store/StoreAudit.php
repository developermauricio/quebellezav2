<?php

namespace core\dgs;

/**
 * Class StoreAudit. Use for monitoring changes data in DGS.
 */
class StoreAudit
{
    /**
     * @var \Store
     */
    private $_store;
    
    public function apply(\Store &$store)
    {
        $this->_store = &$store;

        $changeListener = array(&$this, 'onChangeValues');
        $res = $this->_store->hasEventListener(\Store::EVENT_UPDATE_VALUES, $changeListener);
        if (!$res) {
            $store->addEventListener(\Store::EVENT_UPDATE_VALUES, $changeListener);
        }

        $removeListener = array(&$this, 'onRemoveValues');
        $res = $this->_store->hasEventListener(\Store::EVENT_REMOVE, $removeListener);
        if (!$res) {
            $store->addEventListener(\Store::EVENT_REMOVE, $removeListener);
        }

    } // end apply
    
    public function onChangeValues(\StoreActionEvent &$event)
    {
        $values = $event->getValues();
        
        $values['id'] = $event->getPrimaryKeyValue();
    
        $this->addAuditRow($values);
       
        return true;
    } // end onChangeValues
    
    public function onRemoveValues(\StoreActionEvent &$event)
    {
        $values = $event->getData();
    
        $values['id'] = $event->getPrimaryKeyValue();

        $this->addAuditRow($values);
    } // end onRemoveValues
    
    protected function addAuditRow($values)
    {
        $values = $this->_getPreparedValues($values);
    
        $tableName = $this->getStore()->getName();
        
        $this->create($tableName, $values);
        
        return true;
    } // end addAuditRow
    
    protected function getStore() : \Store
    {
        return $this->_store;
    } // end getStore
    
    private function _getPreparedValues($values)
    {
        foreach ($values as $key => $value) {
            if (strpos($key, '_foreign_') !== false) {
                $originalKey = str_replace('_foreign_', '', $key);
                $values[$originalKey] = $value;
                unset($values[$key]);
            }
            
            if (preg_match("#__([0-9]+)#mis", $key)) {
                unset($values[$key]);
            }
        }
    
        $values['__action'] = $this->getStore()->getAction();
        $values['__action_date'] = date("Y-m-d H:i:s");
        $values['__id_author'] = \Core::getInstance()->user->getID();
        
        return $values;
    } // end _getPreparedValues
    
    protected function create($tableName, $values)
    {
        $auditTable = "__".$tableName;

        $this->getStore()->getProxy()->createAuditTable(
            $auditTable,
            $tableName
        );

        $this->getStore()->getConnection()->insert($auditTable, $values);
    } // end create
    
}