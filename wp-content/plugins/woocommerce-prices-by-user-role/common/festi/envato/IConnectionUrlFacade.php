<?php

interface IConnectionUrlFacade
{
    public function getUrl($url, $postParams = false, $options = false);
    public function isDomainAvailible($url);
}
