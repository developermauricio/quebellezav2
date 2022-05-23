<?php

class InsertActionWebView extends DefaultActionView
{
    /**
     * @param AbstractAction $action
     * @param Response $response
     * @param array|null $vars
     * @return bool
     * @throws SystemException
     */
    public function onResponse(
        AbstractAction &$action, Response &$response, ?array &$vars
    ): bool
    {
        $response->storeAction = $action;
        
        if ($action->isExec()) {
            $this->_addMessage($action, $response, $vars);
        } else {
            $action->onDisplayForm($response);
        }

        return true;
    } // end onResponse

    private function _addMessage(
        AbstractAction &$action, Response &$response, ?array &$vars
    )
    {
        if ($action->hasError()) {
            return $this->error($action, $response);
        }
        
        $response->setType(Response::JSON_IFRAME);
        $response->setAction(Response::ACTION_REDIRECT);
        $response->url = $this->_getRedirectUrl($action);
        
        $msg = $action->getStore()->getOption(
            Store::OPTION_MESSAGE_SUCCESS
        );

        if (!is_null($msg)) {
            if (!$msg) {
                $msg = __l('STATUS_SUCCESS');
            }

            $response->addNotification($msg);
        }

        //
        $target = array(
            'instance' => &$action,
            'result'   => &$vars,
            'action'   => $action->getStore()->getAction(),
            'response' => &$response
        );

        $event = new FestiEvent(Store::EVENT_ON_RESPONSE, $target);
        $action->getStore()->dispatchEvent($event);

        return true;
    }
    
    private function _getRedirectUrl(AbstractAction &$action)
    {
        $store = &$action->getStore();
        $actionName = $store->getAction();
        $actionData = $store->getModel()->getAction($actionName);

        if ($this->_hasActionRedirectUrl($actionData)) {
            return $actionData[StoreModel::ACTION_ATTRIBUTE_REDIRECT_URL];
        }

        return $store->getCurrentUrl();
    } // end _getRedirectUrl

    private function _hasActionRedirectUrl($actionData)
    {
        return array_key_exists(StoreModel::ACTION_ATTRIBUTE_REDIRECT_URL, $actionData)
            && !empty($actionData[StoreModel::ACTION_ATTRIBUTE_REDIRECT_URL]);
    }
}

