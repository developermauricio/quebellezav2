<?php

class BatchInsertActionWebView extends DefaultActionView
{
    /**
     * @param AbstractAction $action
     * @param Response $response
     * @param array|null $vars
     * @return bool
     */
    public function onResponse(
        AbstractAction &$action, Response &$response, ?array &$vars
    ): bool
    {
        $response->storeAction = $action;
        $response->setType(Response::NORMAL);
        $response->setAction(Response::ACTION_REDIRECT);
        $response->url = $action->getStore()->getOption('current_url');

        return true;
    } // end onResponse
}