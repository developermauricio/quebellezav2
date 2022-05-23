<?php

class PercentField extends AbstractField
{
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        if (!empty($value)) {
            $this->value = $value;
        } else {
            $this->value = $this->get('isnull') ? '':0;
        }

        return $this->fetch('edit.phtml');
    } // end getEditInput

    /**
     * @override
     */
    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        $regExp = $this->get('regexp');
        if (!$regExp) {
            $this->set('regexp', '^[\+\-0-9]*\.{0,1}[0-9]{0,2}$');
        }
        
        $mask = $this->get('mask');
        if (!$mask) {
            $this->set('mask', "'mask': '9{0,6}.{0,1}9{0,2}'");
        }
    } // end onInit

    /**
     * @param string $value
     * @param array $row
     * @return string
     */
    public function getInfoValue($value, $row = null)
    {
        $value = $this->displayValue($value, $row);
        return sprintf('%s%%', $value);
    }

    /**
     * override
     */
    protected function getDefaultCellValue(?string $value, array $row = array()): string
    {
        if (empty($value) && !$this->get('isnull')) {
            $value = 0;
        }

        $this->currentValue = $value;

        $value = $this->fetch('cell_value.phtml');

        return $value;
    } // end getDefaultCellValue
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."percent/";
    } // end getTemplatePath
}