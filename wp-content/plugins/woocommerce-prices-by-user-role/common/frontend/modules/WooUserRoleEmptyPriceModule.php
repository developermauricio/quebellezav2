<?php

class WooUserRoleEmptyPriceModule extends AbstractWooUserRoleModule
{
    public function isDisplayTextInsteadOfPriceEnabled()
    {
        $userRole = $this->userRole;

        if (!$userRole) {
            return false;
        }

        $settings = $this->frontend->getOptions('settings');
        
        if (!$settings) {
            $settings = array();
        }

        return array_key_exists('hideEmptyPrice', $settings) &&
               !empty($settings['hideEmptyPrice']) &&
               array_key_exists($userRole, $settings['hideEmptyPrice']);
    } // end isDisplayTextInsteadOfPriceEnabled
    
    public function onGetTextInsteadOfEmptyPrice()
    {
        $settings = $this->frontend->getOptions('settings');
        $textInsteadOfEmptyPrice = $settings['textForEmptyPrice'];
        $vars = array(
            'text' => $textInsteadOfEmptyPrice
        );
        
        return $this->frontend->fetch('custom_text.phtml', $vars);
    } // end onGetTextInsteadOfEmptyPrice
    
    public function onHideEmptyPrice()
    {
        if ($this->isDisplayTextInsteadOfPriceEnabled()) {
            $this->frontend->addFilterListener(
                'woocommerce_empty_price_html',
                'onGetTextInsteadOfEmptyPrice'
            );
                
            return true;
        }
        
        return false;
    } // end onHideEmptyPrice
}