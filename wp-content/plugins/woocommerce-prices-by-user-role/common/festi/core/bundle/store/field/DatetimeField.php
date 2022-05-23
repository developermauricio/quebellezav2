<?php

/**
 * DateTime Field
 *
 * @package Festi
 * @subpackage Fields
 */
class DatetimeField extends AbstractField
{
    public $needTime;
    
    /**
     * @override
     */
    protected function getAttributesFields(): array
    {
        $fields = parent::getAttributesFields();

        $fields['format'] = array(
            'type'     => PARAM_STRING,
            'default'  => '%m/%d/%Y %I:%M %P',
        );

        $fields['time'] = static::FIELD_TYPE_STRING_NULL;

        return $fields;
    } // end getAttributesFields
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $format = $this->getFormat();

        if ($this->isHtmlFiveVersion()) {
            $format = '%Y-%m-%d';
            if ($this->get('time')) {
                $format = '%Y-%m-%d %H:%M:%S';
            }
        }

        if (empty($value)) {
            $default = $this->get('default');
            if ($default) {
                $value = $default;
            } else if (!$this->get('isnull')) {
                $value = strftime($format);
            }
        } else {
            $value = $this->getPreparedValueForEdit($value, $format);
        }

        if (!empty($this->attributes['readonly'])) {
            //return $this->displayRO($value) ;
        }

        return $this->getHtml($value, $format);
    } // end getEditInput

    /**
     * Returns date format according to locale settings.
     */
    public function getFormat()
    {
        $format = $this->get('format');
        if ($format) {
            return $format;
        }

        $this->needTime = 'false';
        $format = '%Y-%m-%d';

        return $format;
    } // end getFormat
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        return $this->getFormattedValue($value);
    } // end displayValue

    /**
     * @override
     */
    public function getFormattedValue($value): ?string
    {
        $format = $this->getFormat();

        if (!empty($value)) {
            $value = strftime($format, strtotime($value));
        }

        return $value;
    } // end getFormattedValue

    public function displayRO($value)
    {
        if ($value) {
            $format = $this->getFormat();
            $value = strftime($format, strtotime($value));
        }

        return $value;
    } // end displayRO

    private function getHtml($value, $format)
    {
        $this->value = htmlspecialchars($value);
        $this->format = $format;

        return $this->fetch('edit.phtml');
    } // end getHtml

    protected function getPreparedValueForEdit($value, $format)
    {
        return strftime($format, strtotime($value));
    } // end getPreparedValueForEdit

    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);

        if (!$value && isset($this->attributes['isnull'])) {
            $value = null;
            return $value;
        }

        if ($this->getLastErrorMessage()) {
            return false;
        }

        return date('Y-m-d H:i:s', strtotime($value));
    } // end getValue

    protected function onFilterFetch()
    {

    } // end onFilterFetch

    protected function getFilterTemplateName()
    {
        if ($this->getFilterType() == static::FILTER_TYPE_RANGE) {
            return 'datetime_range.phtml';
        }

        return 'datetime.phtml';
    } // end getFilterTemplateName

    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."datetime/";
    } // end getTemplatePath

    protected function isHtmlFiveVersion()
    {
        $htmlFive = $this->get('html5');

        return $htmlFive && $htmlFive == "true";
    } // end isHtmlFiveVersion

}
