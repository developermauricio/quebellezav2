<?php

class ConnectionUrlFacade extends AbstractConnectionUrl 
    implements IConnectionUrlFacade
{
    private $_adapter = null;
    
    const CONNECTION_FOPEN_METHOD = 'fopen';
    const CONNECTION_CURL_METHOD = 'curl';
    
    public function __construct()
    {
        $this->_adapter = $this->_createAdapter();
    } // end __construct
    
    private function _createAdapter()
    {
        $method = $this->_getConnectionMethod();
        
        $className = ucfirst($method).'ConnectionAdapter';
        if (!class_exists($className)) {
            require_once __DIR__."/".$className.".php";
        }
        
        return new $className();
    } // end _createAdapter

    public function getUrl($url, $params = false, $options = false)
    {
        return $this->_adapter->getUrl($url, $params, $options);
    } // end getUrl
    
    public function isDomainAvailible($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        return $this->_adapter->isDomainAvailible($url);
    }
    
    private function _getConnectionMethod()
    {
        if ($this->_hasFopenLibrary()) {
            return static::CONNECTION_FOPEN_METHOD;
        }
        
        if ($this->_hasCurlLibrary()) {
            return static::CONNECTION_CURL_METHOD;
        }
        
        throw new ConnectionLibraryNotFound();
    } // end _getConnectionMethod
    
    private function _hasFopenLibrary()
    {
        return ini_get('allow_url_fopen');
    } // end _hasFopenLibrary
    
    private function _hasCurlLibrary()
    {
        return extension_loaded('curl');
    } // end _hasCurlLibrary 
}
