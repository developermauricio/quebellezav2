<?php
if (class_exists('FestiEvent')) {
    return;
}

class FestiEvent
{
    public $bubbles;
    public $currentTarget;
    public $type;
    public $target;
    
    private $_isPropagation;
     
     const INIT   = "INIT";
     const UPDATE = "UPDATE";
     const ADD    = "ADD";
     const DELETE = "DELETE";
     
     public function __construct($type, &$currentTarget = null, $bubbles = false)
     {
         $this->type = $type;
         $this->currentTarget = &$currentTarget;
         $this->target = &$currentTarget;
        
         $this->bubbles = $bubbles;
        
        $this->_isPropagation = true;
     }
    
    /**
     * Keeps the rest of the handlers from being executed and prevents the 
     * event.
     */
    public function stopPropagation()
    {
        $this->_isPropagation = false;
    } // end stopPropagation
    
    /**
     * Returns whether event->stopPropagation() was ever called on this event 
     * object.
     * 
     * @return boolean
     */
    public function isPropagationStopped()
    {
        return !$this->_isPropagation;
    } // end isPropagationStopped
    
    public function getType()
    {
        return $this->type;
    } // end getType

    /**
     * Returns reference to the target object or array.
     *
     * @return mixed
     */
    public function &getTarget()
    {
        return $this->target;
    } // end getTarget

    /**
     * Returns reference to an element of target object.
     *
     * @param $key
     * @return mixed|array|null
     */
    public function &getTargetValueByKey($key)
    {
        $ref = null;
        if (array_key_exists($key, $this->target)) {
            $ref = &$this->target[$key];
        }

        return $ref;
    } // end getTargetValueByKey

    /**
     * Returns reference to an element of target object.
     *
     * @param $key
     * @return array
     * @throws SystemException
     */
    public function &getDataByKey($key): array
    {
        $ref = null;
        if (!array_key_exists($key, $this->target) || !$this->target[$key]) {
            $ref = array();
        } else {
            $ref = &$this->target[$key];
        }

        if (!is_array($ref)) {
            throw new SystemException("Undefined data type");
        }

        return $ref;
    } // end getDataByKey
    
}
