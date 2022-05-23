<?php

abstract class ValuesObject extends Entity
{
    private $_values;

    public function __construct($data)
    {
        $this->_values = $data;
    } // end __construct

    protected function get($key)
    {
        if ($this->has($key)) {
            return $this->_values[$key];
        }

        throw new SystemException("Undefined property: ".$key);
    } // end get

    protected function has($key)
    {
        return array_key_exists($key, $this->_values);
    }

    public function toJson()
    {
        return json_encode($this->_values);
    }
    
    protected function set($key, $value)
    {
        $this->_values[$key] = $value;
    }
    
    public function getValues()
    {
        return $this->_values;
    } // end getValues

    public function isEmpty()
    {
        return empty($this->_values);
    } // end isEmpty

    public static  function convert($rows)
    {
        $className = get_called_class();
    
        if (is_scalar($rows)) {
            return new $className($rows);
        }
    
        $result = array();
        foreach ($rows as $values) {
            $result[] = new $className($values);
        }
    
        return $result;
    } // end convert
}