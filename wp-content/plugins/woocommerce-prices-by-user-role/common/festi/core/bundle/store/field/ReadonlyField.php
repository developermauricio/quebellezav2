<?php

class ReadonlyField extends AbstractField
{
    /**
     * @override
     */
    public function isVirtualField($actionName = false)
    {
        return true;
    }
}
