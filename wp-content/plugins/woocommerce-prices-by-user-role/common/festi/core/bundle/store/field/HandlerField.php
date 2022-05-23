<?php

class HandlerField extends AbstractField
{
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $params = array(
            &$this,
            $value
        );
        
        return $this->callHandlerMethod($params, 'EditInput');
    } // end getEditInput
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        $params = array(
            &$this,
            $value,
            $row
        );
        
        return $this->callHandlerMethod($params, 'CellValue');
    } // end displayValue
    
    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);
        
        $params = array(
            &$this,
            $value,
            $requests
        );
        
        return $this->callHandlerMethod($params, 'Value');
    } // end getValue
    
    public function callHandlerMethod($params, $methodPostfix)
    {
        $prefix = $this->get('method');
        if (!$prefix) {
            $fieldName = $this->getName();
            $chunks = array_map('ucfirst', explode("_", $fieldName));
            $prefix = 'get'.join('', $chunks);
        }
        
        $methodName = $prefix.'Field'.$methodPostfix;
        
        $plugin = $this->get('plugin');

        if ($plugin !== false) {
            if (is_object($plugin)) {
                $handler = $plugin;
            } else {
                $handler = Core::getInstance()->getPluginInstance($plugin);
            }
        } else {
            $handler = &$this->store->getPlugin();
        }

        $method = array($handler, $methodName);
        
        if (!is_callable($method)) {
            throw new StoreException(
                "Undefined method ".$methodName." in handler field ".$this->getName()
            );
        }
        
        return call_user_func_array($method, $params);
    } // end callHandlerMethod
}
