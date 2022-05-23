<?php

require_once 'bundle/store/field/DatetimeField.php';

class TimestampField extends DatetimeField
{
    /**
     * @override
     */
    public function getFormattedValue($value): ?string
    {
        $format = $this->getFormat();

        if (!empty($value)) {
            $value = strftime($format, $value);
        }

        return $value;
    } // end getFormattedValue

    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);

        if (!$value && isset($this->attributes['isnull'])) {
            $value = null;
            return $value;
        }

        return strtotime($value);
    } // end getValue

    protected function getPreparedValueForEdit($value, $format)
    {
        return strftime($format, $value);
    } // end getPreparedValueForEdit

    public function displayRO($value)
    {
        if ($value) {
            $format = $this->getFormat();
            $value = strftime($format, $value);
        }

        return $value;
    } // end displayRO
}