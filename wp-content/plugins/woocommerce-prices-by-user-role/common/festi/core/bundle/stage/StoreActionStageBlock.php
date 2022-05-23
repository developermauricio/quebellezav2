<?php

class StoreActionStageBlock extends StageBlock
{
    /**
     * @override
     */
    protected function onInit()
    {
        if (!($this->instance instanceof AbstractDisplayAction)) {
            throw new StageException(
                "Undefined sore action stage block instance type."
            );
        }
        
        $store = &$this->instance->getStore();
        $store->addEventListener(
            Store::EVENT_PREPARE_ACTION_URL,
            array($this, 'onPrepareActionUrl')
        );
        
        $actionName = $this->instance->getActionName();
        
        $action = &$store->getModel()->getActionByRef($actionName);
        $action['mode'] = Store::ACTION_VIEW_MODE_NEW;
        
        if (empty($action['cancelUrl'])) { // if default cancel button logic
            $action['cancelUrl'] = false;
        }
        
    } // end onInit
    
    /**
     * @param FestiEvent $event
     */
    public function onPrepareActionUrl(FestiEvent &$event)
    {
        $params = &$event->target['params'];
        
        $externalParams = $_REQUEST;
    
        $params[StageBuilder::REQUEST_KEY_BLOCK_INDEX] = $this->getStageIndex();
        $params[Response::REQUEST_KEY_AJAX_MODE] = 'true';
    
        foreach ($externalParams as $key => $value) {
            if (array_key_exists($key, $params)) {
                continue;
            }
            $params[$key] = $value;
        }
    } // end onPrepareActionUrl
    
    /**
     * @override
     */
    protected function onRequest(Response &$response)
    {
        $this->instance->onStart($response);
        
        if (Response::isAjaxRequest()) {
            return true;
        }
        
        return $response->getContent();
    }
}