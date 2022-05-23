<?php

class StoreActionEvent extends FestiEvent
{
    public function getPrimaryKeyValue()
    {
        return $this->getTargetValueByKey('id');
    } // end getPrimaryKeyValue
    
    public function &getValues()
    {
       return $this->getTargetValueByKey('values');
    } // end getValues
    
    public function isUpdated()
    {
        if (array_key_exists('isUpdated', $this->target)) {
            return $this->target['isUpdated'];
        }
        
        return false;
    } // end isUpdated
    
    public function setUpdated($isUpdated = true)
    {
        $this->target['isUpdated'] = $isUpdated;
    } // end setUpdated

    /**
     * @return AbstractAction
     */
    public function &getActionInstance()
    {
        return $this->getTargetValueByKey('instance');
    } // end getActionInstance
    
    public function getActionName()
    {
        return $this->getTargetValueByKey('action');
    } // end getActionName
    
    public function &getData()
    {
        return $this->getTargetValueByKey('data');
    } // end getData

    /**
     * Returns reference to response object.
     *
     * @return Response|null
     */
    public function &getResponse() :?Response
    {
        return $this->getTargetValueByKey('response');
    } // end getResponse
    

}