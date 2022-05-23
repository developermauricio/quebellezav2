<?php

class InfoActionApiView extends AbstractActionApiView
{
    public function getSuccessMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $response->status = static::STATUS_OK;
        $response->addParam($vars);

        return true;
    } // end getSuccessMessage

    public function getErrorMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $primaryKey = $action->getStore()->getPrimaryKeyValueFromRequest();

        $response->setStatus(Response::STATUS_NOT_FOUND);
        $response->status  = static::STATUS_ERROR;
        $response->message = __('Record #%d isn`t exist', $primaryKey);
        return true;
    } // end getErrorMessage
}