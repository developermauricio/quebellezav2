<?php

class PriceField extends AbstractField
{
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $value = $this->getViewValue($value);

        $this->value = $value;
        
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
            $this->set('mask', "'mask': '-{0,1}9{0,6}\.{0,1}9{0,2}'");
        }
        
        $format = $this->get('format');
        if (!$format) {
            $this->set('format', 'price');
        }
    } // end onInit
    
    /**
     * @override
     */
    protected function getAttributesFields(): array
    {
        $fields = parent::getAttributesFields();
        
        $fields['locale'] = array(
            'type'     => static::FIELD_TYPE_STRING_NULL,
            'default' => 'en_US.UTF-8'
        );
    
        return $fields;
    } // end getAttributesFields
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath()."price".DIRECTORY_SEPARATOR;
    } // end getTemplatePath
    
    /**
     * Returns currency symbol. 
     * 
     * @see http://php.net/manual/en/resourcebundle.locales.php
     * @return string
     */
    protected function getCurrencySymbol()
    {
        $locale = $this->get('locale');

        if (!class_exists('NumberFormatter')) {

            setlocale(LC_MONETARY, $locale);
            $locale_info = localeconv();

            return $locale_info['currency_symbol'];
        }
        
        $formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $symbol = $formatter->getSymbol(NumberFormatter::CURRENCY_SYMBOL);

        return $symbol;
    } // end getCurrencySymbol
}
