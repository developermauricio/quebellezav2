<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'IHttpClient.php';

abstract class HttpClient implements IHttpClient
{
    public static function createInstance()
    {
        $classPrefix = false;
        if (extension_loaded('curl')) {
            $classPrefix = "Curl";
        } else if (ini_get('allow_url_fopen')) {
            $classPrefix = "FileOpen";
        } else {
            throw new HttpClientException("Unsupportable Http Client.");
        }

        $className = $classPrefix.'HttpClientAdapter';

        $path = __DIR__.DIRECTORY_SEPARATOR.$className.'.php';

        if (!include_once($path)) {
            throw new HttpClientException('Http Client Adapter not installed');
        }

        return new $className();
    } // end createInstance

}

class HttpClientException extends Exception
{
    const STATUS_NOT_FOUND = 404;

}