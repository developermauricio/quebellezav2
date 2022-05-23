<?php
require_once 'bundle/store/field/SelectField.php';

class RadioField extends SelectField
{
    protected function onInitOptions($scheme)
    {
        if (!$scheme->hasOptions()) {
            return false;
        }
        
        if ($this->get('isnull')) {
            $this->valuesList[''] = array(
                'caption' => __l('...')
            );
        }
        
        $options = $scheme->getOptions();
        
        foreach ($options as $item) {
            $this->valuesList[$item['id']] = array(
                'caption' => $item['value'],
                'note' => !empty($item['note']) ? $item['note'] : null
            );
        }
    } // end onInitOptions

    /**
     * @override
     */
    public function displayValue(?string $value, array $row = null): ?string
    {
        if (is_null($value)) {
            $value = 'NULL';
        }

        if (array_key_exists($value, $this->valuesList)) {
            $note  = $this->valuesList[$value]['note'];
            $value = $this->valuesList[$value]['caption'];

            if (!empty($note)) {
                $value = $value.':'.$note;
            }
        } else {
            $value = "";
        }

        return $value;
    } // end displayValue
    
    protected function onFilterFetch()
    {
        $result = array();
        foreach ($this->valuesList as $id => $item) {
            $result[$id] = $item['caption'];
        }
        $this->filterValues = $result;
    } // end onFilterFetch

    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."../radio/";
    } // end getTemplatePath
}