<?php

class EnvatoApiServerNotFound extends Exception
{
    public function __construct($message = null, $code = 0)
    {
        $message = $this->_getMessage();
        
        parent::__construct($message, $code);
    } // end __construct
    
    private function _getMessage()
    {
        $msg = __(
            'The server is not responding. Please contact '.
            'Festi Support to activate your license. '
        );
        
        return $msg;
    } // end _getMessage
}