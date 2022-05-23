<?php 

class DefaultUser extends Entity
{
    const EVENT_LOGIN = "user_login";
    
    const TYPE_ANONYM = -1;
    
    const OPTION_AUTH_ROLE = "auth_role";
    
    /**
     * @var array
     */
    private $_sessionData = array();
    
    protected $requiredFields = array(
        'auth', 'auth_id', 'auth_login', self::OPTION_AUTH_ROLE
    );
    
    /**
     * DefaultUser constructor.
     * @param $_sessionData
     */
    public function __construct(&$_sessionData) 
    {
        $this->_sessionData = &$_sessionData;
        
        if (empty($this->_sessionData[static::OPTION_AUTH_ROLE])) {
            $this->_sessionData[static::OPTION_AUTH_ROLE] = self::TYPE_ANONYM;
        }
    }
    
    /**
     * @param array $fields
     * @return bool
     * @throws SystemException
     */
    public function doLogin(array $fields): bool
    {
        foreach ($this->requiredFields as $name) {
            if (!isset($fields[$name])) {
                throw new SystemException(__('Not found parametr %s', $name));
            }
        }
        
        Core::getInstance()->fireEvent(self::EVENT_LOGIN, $fields);
        
        $this->_sessionData = $fields;
        
        return true;
    } // end doLogin
    
    public function logout()
    {
        $this->_sessionData = array();
    }
    
    /**
     * @return bool
     */
    public function isLogin(): bool
    {
        return isset($this->_sessionData["auth"]) && $this->_sessionData["auth"] == "yes";
    }
    
    public function getRole()
    {
        return $this->get(static::OPTION_AUTH_ROLE);
    }
    
    /**
     * @param $name
     * @return bool|mixed
     */
    public function get($name) 
    {
        return $this->_sessionData[$name] ?? null;
    }
    
    /**
     * @param $name
     * @return bool
     */
    public function getValue($name)
    {
        $authDataKey = 'auth_data';
        if (!array_key_exists($authDataKey, $this->_sessionData)) {
            return false;
        }
        
        if (empty($this->_sessionData[$authDataKey][$name])) {
            return false;
        }
        
        return $this->_sessionData[$authDataKey][$name];
    } // end getValue
    
    public function set($name, $value) 
    {
        $this->_sessionData[$name] = $value;
        
        return $value;
    }
    
    public function getSessionData()
    {
        return $this->_sessionData;
    }
    
    public function getID()
    {
        return $this->get("auth_id");
    }
    
}
