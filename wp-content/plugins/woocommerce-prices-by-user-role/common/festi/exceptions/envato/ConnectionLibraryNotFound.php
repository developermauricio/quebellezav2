<?php

class ConnectionLibraryNotFound extends Exception
{
    public function __construct($message = null, $code = 0)
    {
        $message = $this->_getMessage();
        
        parent::__construct($message, $code);
    }
    
    private function _getMessage()
    {
        $msg = __(
            'Following PHP directive is disabled: '.
            'allow_url_fopen OR curl. In order to activate '.
            'the license, please enable this directive.'
        );
        
        return $msg;
    }
}
