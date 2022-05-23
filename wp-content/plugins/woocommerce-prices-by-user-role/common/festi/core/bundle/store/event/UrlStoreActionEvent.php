<?php

namespace core\dgs\event;

class UrlStoreActionEvent extends \FestiEvent
{
    public function __construct(string &$url, array &$params, \Store &$store)
    {
        $target = array(
            'url'    => &$url,
            'params' => &$params,
            'store'  => &$store
        );

        parent::__construct(\Store::EVENT_PREPARE_ACTION_URL, $target);
    } // end __construct

    public function &getParams()
    {
        return $this->getTargetValueByKey('params');
    } // end getParams

    public function &getUrl()
    {
        return $this->getTargetValueByKey('url');
    } // end getUrl
}
