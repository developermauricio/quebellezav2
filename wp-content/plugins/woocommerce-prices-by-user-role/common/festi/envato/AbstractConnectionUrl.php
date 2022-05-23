<?php

abstract class AbstractConnectionUrl implements IConnectionUrlFacade
{
    public function hasErrorInResponse($response)
    {
        $data = json_decode($response, true);
        
        return !empty($data['error']);
    } // end hasErrorInResponse
    
    public function getUrl($url, $postParams = false, $options = false)
    {
        throw new UnsupportableFacadeMethod();
    } // end getUrl
    
    public function isDomainAvailible($url)
    {
        throw new UnsupportableFacadeMethod();
    } // end isDomainAvailible
}
