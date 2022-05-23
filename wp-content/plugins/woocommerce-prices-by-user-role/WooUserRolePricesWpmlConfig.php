<?php

class WooUserRolePricesWpmlConfig
{
    protected $wpmlKey = PRICE_BY_ROLE_WPML_KEY;
    
    public function getWpmlKey()
    {
        return $this->wpmlKey;
    } // end getWpmlKey
    
    public function getTranslateList()
    {
        $list = array(
            PRICE_BY_ROLE_OPTIONS_PREFIX.'settings' => array(
                'textForNonRegisteredUsers',
                'textForUnregisterUsers',
                'textForRegisterUsers',
                'textForEmptyPrice'
            ),
        );
        
        return $list;
    }
}