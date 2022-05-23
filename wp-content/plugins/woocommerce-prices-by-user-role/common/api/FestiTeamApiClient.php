<?php

class FestiTeamApiClient
{
    
    public static function getDownloadLinkInfo($hash, $ip = false)
    {
        
        $url = "/app/download/archive/?hash=".$hash;
        
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $url .= "&referer=".urlencode($_SERVER['HTTP_REFERER']);
        }
        
        if ($ip) {
            $url .= "&ip=".$ip;
        }
        
        return static::_api($url);
    } // end getDownloadLinkInfo
    
    public static function addInstallStatistics($idPlugin)
    {
        $url = "/statistics/plugin/".$idPlugin."/install/";
        
        $params = array();

        if (!empty($_SERVER['SERVER_ADDR'])) {
            $params['ip'] = $_SERVER['SERVER_ADDR'];
        }

        if (!empty($_SERVER['SERVER_NAME'])) {
            $params['host'] = $_SERVER['SERVER_NAME'];
        }
        
        if (function_exists('get_option')) {
            $params['admin_email'] = get_option('admin_email');
        }
        
        static::_api($url, $params);
    } // end addInstallStatictics
    
    private static function _api($url, $params = false)
    {
        $baseUrl = 'https://api.festi.team';
        
        if (defined('FESTI_API_URL')) {
            $baseUrl = FESTI_API_URL;
        }
        
        $url = $baseUrl.$url;
        
        if ($params) {
            $context = array('http' => array(
                'method' => 'POST',
                'content' => http_build_query($params)
            ));
        } else {
            $context = array('http' => array(
                'method' => 'GET'
            ));
        }
        
        $ctx = stream_context_create($context);
        $fp = @fopen($url, 'rb', false, $ctx);
        $result = false;
        if ($fp) {
            $result = @stream_get_contents($fp);
        }
        
        if ($result !== false) {
            $result = json_decode($result, true);
        }
        
        return $result;
    } // end _api

}