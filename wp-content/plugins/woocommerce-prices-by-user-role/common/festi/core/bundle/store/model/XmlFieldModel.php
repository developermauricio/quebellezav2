<?php

class XmlFieldModel extends FieldModel
{
    public function getAttributes()
    {
        return current($this->storage->attributes());
    } // end getAttributes
    
    /**
     * @return bool
     */
    public function hasOptions(): bool
    {
        return !empty($this->storage->option);
    } // end hasOptions
    
    /**
     * @return array
     */
    public function getOptions(): array
    {
        $index = 0;
        $result = array();
        foreach ($this->storage->option as $item) {
            $value = trim((string) $item);
            if (!$value) {
                continue;
            }
            $index++;
            
            $attr = $item->attributes();
            
            foreach ($attr as $key => $arrtValue) {
                $result[$index][$key] = (string) $arrtValue;
            }
            //$result[$index]['id'] = (string)$attr['id'];
            $result[$index]['value'] = $value;
        }
        
        return $result;
    } // end getOptions
    
}
