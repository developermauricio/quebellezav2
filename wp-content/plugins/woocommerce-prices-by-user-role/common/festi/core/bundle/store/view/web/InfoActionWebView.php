<?php

require_once 'bundle/store/view/web/EditActionWebView.php';

class InfoActionWebView extends EditActionWebView
{
    /**
     * @param AbstractAction $action
     * @param Response $response
     * @param array|null $vars
     * @return bool
     * @throws StoreException
     * @throws SystemException
     */
    public function onResponse(
        AbstractAction &$action, Response &$response, ?array &$vars
    ): bool
    {
        $response->storeAction = $action;
        
        if ($action->hasError()) {
            return $this->error($action, $response);
        }
        
        $action->onDisplayForm($response);

        return true;
    } // end onResponse
}