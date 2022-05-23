<?php

class NumberField extends AbstractField
{
    /**
     * @override
     */
    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        $regExp = $this->get('regexp');
        if (!$regExp) {
            $this->set('regexp', '^[\+\-0-9\.]*$');
        }
        
        $mask = $this->get('mask');
        if (!$mask) {
            $this->set('mask', "'mask': '-{0,1}9{0,6}.{0,1}9{0,2}'");
        }
    } // end onInit

    /**
     * @override
     */
    public function getElementAttributes(): string
    {
        $attributes = parent::getElementAttributes();

        $min = $this->get('min');
        if ($min !== false) {
            $attributes .= sprintf(' min="%s"', $min);
        }

        $max = $this->get('max');
        if ($max !== false) {
            $attributes .= sprintf(' max="%s"', $max);
        }

        $step = $this->get('step');
        if ($step !== false) {
            $attributes .= sprintf(' step="%s"', $step);
        }

        return $attributes;
    } // end getElementAttributes

}
