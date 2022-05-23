<?php

class PasswordField extends AbstractField
{
    public function getInputType()
    {
        return 'password';
    } // end getInputType

    /**
     * @param string $value
     * @param array $row
     * @return string
     */
    public function getInfoValue($value, $row = null)
    {
        return '********';
    }
}
