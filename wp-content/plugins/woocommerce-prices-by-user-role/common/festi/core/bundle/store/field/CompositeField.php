<?php

class CompositeField  extends AbstractField
{
    const VALUES_SEPARATOR = "|";

    protected $options;

    /**
     * @override
     */
    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);

        $this->onInitOptions($scheme);
    } // end onInit

    /**
     * @override
     */
    protected function onInitOptions($scheme)
    {
        $this->options = $scheme->getOptions();
        
        $this->set('onlyList', true);
        $this->set('sorting', 'false');
        //$this->set('filter', false);
    } // end onInitOptions
    
    public function getOptions()
    {
        return $this->options;
    }
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        if (!$value) {
            return "";
        }
        
        $separator = $this->get('separator');
        if (!$separator) {
            $separator = " ";
        }

        $chunks = explode(static::VALUES_SEPARATOR, $value);
        $values = array();
        foreach ($chunks as $item) {
            list($key, $itemValue) = explode(":", $item);
            $values[$key] = $itemValue;
        }
        
        $format = $this->get('format');
        if ($format) {
            return Entity::fillString($format, $values);
        }
        
        return join($separator, $values);
    } // end displayValues
    
}