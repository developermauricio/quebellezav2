<?php

class CurlConnectionAdapter extends AbstractConnectionUrl
{
    public function getUrl($url, $postParams = false, $options = false)
    {   
        $ch = curl_init();
        
        if (!$options) {
            $options = $this->_getDefaultOptions();
        }
        
        if ($postParams) {
            $options[CURLOPT_POSTFIELDS] = http_build_query($postParams);
        }
        
        $options[CURLOPT_URL] = $url;
        
        curl_setopt_array($ch, $options);
       
        $response = curl_exec($ch);
        
        if (!$response) {
            throw new EnvatoApiServerNotFound();
        }
        
        if ($this->hasErrorInResponse($response)) {
             throw new EnvatoApiServeNotFound();
        }
        
        return $response;
    } // end getUrl
    
    private function _getDefaultOptions()
    {
        $options = array(
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_USERAGENT      => $this->_getUserAgent(),
            CURLOPT_HEADER         => 0
        );
        
        return $options;
    } // end _getDefaultOptions
    
    public function isDomainAvailible($url)
    {
        $options = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true
        );
        
        return (bool) $this->getUrl($url, false, $options);
    } // end isDomainAvailible
    
    private function _getUserAgent()
    {
        return 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) '.
                'Gecko/20080311 Firefox/2.0.0.13';
    } // end _getUserAgent
}
