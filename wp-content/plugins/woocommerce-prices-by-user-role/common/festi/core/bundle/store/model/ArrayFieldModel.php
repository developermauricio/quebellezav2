<?php

class ArrayFieldModel extends FieldModel
{
    public function getAttributes()
    {
        return $this->storage;
    } // end getAttributes
    
    /**
     * @return bool
     */
    public function hasOptions(): bool
    {
        return !empty($this->storage['options']);
    } // end hasOptions
    
    /**
     * @return array
     */
    public function getOptions(): array
    {
        if (is_array($this->storage['options'])) {
            return $this->storage['options'];
        }
        
        $options = explode("\n", (string) $this->storage['options']);
        
        $result = array();
        foreach ($options as $value) {
            $info = explode(':', $value);
            $item = array();
            
            if (isset($info[0])) {
                $item['id'] = $item['value'] = $info[0];
            }
            if (isset($info[1])) {
                $item['value'] = $info[1];
            }
            if (isset($info[2])) {
                $item['note'] = $info[2];
            }
            
            if (!empty($item)) {
                $result[] = $item;
            }
        }
        
        return $result;
    } // end getOptions
    
}