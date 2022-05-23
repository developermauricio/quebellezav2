<?php

class LocaleModel
{
    protected $labels = array();
    
    public function addDictionary(IDictionaryLocale $dictionary)
    {
        $dictionary->load();
        $this->labels += $dictionary->getAll();
    }
    
    public function get($key)
    {
        return $this->labels[$key] ?? false;
    }
    
}
