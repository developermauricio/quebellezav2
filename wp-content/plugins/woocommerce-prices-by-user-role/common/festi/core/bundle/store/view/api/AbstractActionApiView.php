<?php

abstract class AbstractActionApiView extends DefaultActionView
{
    const STATUS_ERROR = 'error';
    const STATUS_OK    = 'ok';
    
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
        $response->setType(Response::JSON);
        
        if ($action->hasError() || !is_array($vars)) {
            $this->getErrorMessage($action, $response, $vars);
        } else {
            $this->getSuccessMessage($action, $response, $vars);
        }

        return true;
    }

    protected function getSuccessMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $response->status = static::STATUS_OK;

        return true;
    } // end getSuccessMessage

    protected function getErrorMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $response->setStatus(Response::STATUS_BAD_REQUEST);
        $response->status = static::STATUS_ERROR;

        return true;
    } // end getErrorMessage
}