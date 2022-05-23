<?php

class RemoveActionWebView extends DefaultActionView
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
            $this->_addMessage($action, $response);
        } else {
            $action->onDisplayForm($response);
        }

        return true;
    } // end onResponse

    private function _addMessage(AbstractAction &$action, Response &$response)
    {
        if ($action->hasError()) {
            return $this->error($action, $response);
        }

        $response->setType(Response::JSON_IFRAME);
        $response->setAction(Response::ACTION_REDIRECT);
        $response->url = $action->getStore()->getCurrentUrl();

        $msg = $action->getStore()->getOption(Store::OPTION_MESSAGE_SUCCESS);
        if (!is_null($msg)) {
            if (!$msg) {
                $msg = __l('STATUS_SUCCESS_DELETE');
            }

            $response->addNotification($msg);
        }
        
        //
        $target = array(
            'instance' => &$action,
            'action'   => $action->getStore()->getAction(),
            'response' => &$response
        );
        
        $event = new FestiEvent(Store::EVENT_ON_RESPONSE, $target);
        $action->getStore()->dispatchEvent($event);
    }
}