<?php

interface IHttpClient
{
    /**
     * Returns true if url available.
     *
     * @param $url
     * @return boolean
     */
    public function isUrlAvailable($url);

    public function get($url, $params = array());

    public function getStatusCode();
}