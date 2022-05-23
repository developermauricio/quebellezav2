<?php

class StringManagerWooUserRolePrices extends WordpressDispatchFacade
{
    protected $languageDomain = PRICE_BY_ROLE_LANGUAGE_DOMAIN;
    private static $_instance = null;
    
    private function __construct()
    {
        if (self::$_instance !== null) {
            throw new Exception(
                "Instance already defined."
            );
        }

        $this->_doRegisterStringList();
    } // end __construct
    
    public static function start()
    {
        return self::getInstance();
    } // end getInstance
    
    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }
        
        return self::$_instance;
    } // end getInstance
    
    private function _doRegisterStringList()
    {
        $stringsList = $this->getStrings();
        
        
        foreach ($stringsList as $key => $string) {
            $this->doRegisterString($string, $key);
        }
    } // end _doRegisterStringList
    
    public function doRegisterString($string, $key)
    {
        /*
        EngineFacade::getInstance()->dispatchAction(
            FESTI_ACTION_REGISTER_STATIC_STRING,
            $string,
            $key
        );
        */
        $this->dispatchAction(
            FESTI_ACTION_REGISTER_STATIC_STRING,
            $string,
            $key
        );
        
    } // end doRegisterString
    
    public function getStrings()
    {
        $strings = array(
            'cartTotalSavingsRetail' => static::getWord("Total retail"),
            
            'cartTotalSavingsUserPriceTitle' => static::getWord("Your Price"),
            
            'cartTotalSavingsDiscountTitle' => static::getWord("Total savings"),
            
            'productSavingsRegularPrice' => static::getWord("Regular Price"),
            
            'productSavingsUserPriceTitle' => static::getWord("Your Price"),
            
            'productSavingsDiscountTitle' => static::getWord("Savings"),
            
            'subscriptionSignFee' => static::getWord("Sign-up Fee"),
            
            'subscriptionPerMonth' => static::getWord("/ month"),

            'freeProduct' => static::getWord("Free!"),
        );
        
        return $strings;
    } // end getStrings
    
    public function getString($key)
    {
        $strings = $this->getStrings();
        
        if (!array_key_exists($key, $strings)) {
            return $key;
        }
        
        $string = $strings[$key];
        
        $string = $this->getStringTranslation($string, $key);

        return $string;
    } // end getString
    
    public function getStringTranslation($string, $key)
    {
        /*
        $string = EngineFacade::getInstance()->dispatchFilter(
            FESTI_FILTER_GET_STATIC_STRING,
            $string,
            $key
        );
        */
        
        $string = $this->dispatchFilter(
            FESTI_FILTER_GET_STATIC_STRING,
            $string,
            $key
        );
        
        return $string;
    } // end getStringTranslation
    
    public function displayString($key)
    {
        echo $this->getString($key);
    } // end displayString
    
    public static function getWord($word)
    {   
        return __($word, PRICE_BY_ROLE_LANGUAGE_DOMAIN);
    }
}