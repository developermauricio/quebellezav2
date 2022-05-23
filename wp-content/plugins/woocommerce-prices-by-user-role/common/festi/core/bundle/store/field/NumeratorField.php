<?php

class NumeratorField extends AbstractField
{
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        return $value;
    }
}