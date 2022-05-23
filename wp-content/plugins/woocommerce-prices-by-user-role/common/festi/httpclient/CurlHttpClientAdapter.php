<?php

class CurlHttpClientAdapter extends HttpClient
{
    private $_options;

    private $_resource;

    private $_statusCode;

    public function __construct()
    {
        $this->_options = array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows; U; Windows '.
                'NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
            CURLOPT_HEADER         => 0
        );
    } // end __construct

    public function __destruct()
    {
        if (is_resource($this->_resource)) {
            curl_close($this->_resource);
        }
    } // end __destruct

    public function isUrlAvailable($url)
    {
        $this->_options = array(
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_RETURNTRANSFER => true
        );

        return (bool) $this->get($url);
    } // end isUrlAvailable

    public function get($url, $params = array())
    {
        if ($params) {
            $url .= '?'.http_build_query($params);
        }

        $this->_options[CURLOPT_URL] = $url;

        $this->_prepareRequest();

        return $this->_exec();
    } // end get

    private function _exec()
    {
        $response = curl_exec($this->_resource);

        $this->_statusCode = curl_getinfo($this->_resource, CURLINFO_HTTP_CODE);

        curl_close($this->_resource);

        if ($this->_statusCode >= 400) {
            throw new HttpClientException("", $this->_statusCode);
        }

        return $response;
    } // end _exec

    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    private function _prepareRequest()
    {
        if (is_resource($this->_resource)) {
            curl_close($this->_resource);
        }

        $this->_resource = curl_init();

        curl_setopt_array($this->_resource, $this->_options);
    } // end _prepareRequest

}