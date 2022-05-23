<?php

class Permissions
{
    public static function doValidate($options = array())
    {
        $core = Core::getInstance();
        
        if (!$core->user->isLogin()) {
            self::throwPermissionsException();
        }
    } // end doValidatePermission
    
    protected static function throwPermissionsException($code = 0)
    {
        $msg = __('You are not authorized to execute this operation');
        throw new PermissionsException($msg, $code);
    } // end throwPermissionsException
    
}
