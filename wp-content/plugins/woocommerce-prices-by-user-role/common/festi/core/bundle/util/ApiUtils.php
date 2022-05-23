<?php

require_once 'bundle/util/Curl.php';

class ApiUtils
{
    const REQUEST_METHOD_GET = "GET";
    const REQUEST_METHOD_POST = "POST";
    const REQUEST_METHOD_PUT = "PUT";
    const REQUEST_METHOD_DELETE = "DELETE";       
    
    private static $_token = null;
    private static $_tokenName = 'Access-Token';
    
    public static function setToken($token)
    {
        static::$_token = $token;
    }
    
    public static function getToken()
    {
        if (!static::_hasToken()) {
            throw new ApiException('Access Token Not Found');
        }
        
        return static::$_token;
    }
    
    private static function _hasToken()
    {
        return static::$_token != null;
    }
    
    public static function sendGet($url, $headers = false)
    {
        return static::send($url, false, false, $headers);
    }
    
    public static function sendPost($url, $postData = false, $headers = false)
    {
        return static::send($url, $postData, static::REQUEST_METHOD_POST);
    }
    
    public static function sendPut($url, $postData = false, $headers = false)
    {
        return static::send($url, $postData, static::REQUEST_METHOD_PUT);
    }
    
    public static function sendDelete($url, $headers = false)
    {
        return static::send($url, false, static::REQUEST_METHOD_DELETE);
    }
    
    public static function send(
        $url, $data = false, $requestMethod = false, $headers = false, &$responseHeaders = null
    )
    {
        //if (!static::_hasToken()) {
            //static::_setUserToken();
        //}
        
        $options = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FOLLOWLOCATION => false,
        );
        
        if (!$headers) {
            $headers = array(
                //static::$_tokenName.': '.static::$_token,
                'Content-type: application/json',
            );
        }
        
        $options[CURLOPT_HTTPHEADER] = $headers;

        if ($requestMethod) {
            $options[CURLOPT_CUSTOMREQUEST] = $requestMethod;
        }
       
        $curl = new Curl($options);

        if (is_array($data)) {
            $data = json_encode($data);
        }

        $result = $curl->getUrl($url, $data);

        if (!$result) {
            $msg = __(
                'Api Service Not Found. Url: %s. Data: %s',
                $url,
                json_encode($data)
            );
            throw new ApiException($msg, $curl->getCode());
        }

        $responseHeaders = $curl->getResponseHeaders();

        return json_decode($result, true);
    }
    
    private static function _setUserToken()
    {
        $token = Core::getInstance()->user->getValue('access_token');
        if (!$token) {
            throw new ApiException('Access Token Not Found');
        }
        
        static::setToken($token);
        
        return true;
    }
    
    public static function getTokenName()
    {
        return static::$_tokenName;
    }
    
    public static function setTokenName($name)
    {
        static::$_tokenName = $name;
    }
    
}
