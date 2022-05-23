<?php

class WpUserBridge extends DefaultUser
{
    const TYPE_ADMIN = 1;
    const TYPE_USER = 2;
    
    private $_dataFieldRelations = array(
        'auth_login' => 'user_login'
    );
    
    private $_data = array();
    
    public function __construct() 
    {
    }
    
    public function isLogin(): bool
    {
        return is_user_logged_in();
    }
    
    public function getRole()
    {
        if (!is_user_logged_in()) {
            return self::TYPE_ANONYM;
        }
        
        $user = wp_get_current_user();
        $index = array_search('administrator', $user->roles);
        if ($index !== false) {
            return self::TYPE_ADMIN;
        }
        
        return self::TYPE_USER;
    } // end getRole
    
    public function get($name) 
    {
        $user = wp_get_current_user();
        
        if ($name == "auth_data") {
            return (array) $user->data;
        }
        
        if (!isset($this->_dataFieldRelations[$name])) {
            var_dump($user->data);
            die("!!!!: ".$name);
        }
        
        $key = $this->_dataFieldRelations[$name];

        return $user->data->$key;
    } // end get
    
    public function set($name, $value) 
    {
    } // end set
    
    public function getSessionData()
    {
    } //end getSessionData
    
    public function getID()
    {
       return get_current_user_id();
    } //end getID
}