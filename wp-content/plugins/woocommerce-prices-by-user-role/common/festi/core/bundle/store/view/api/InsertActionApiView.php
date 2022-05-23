<?php

class InsertActionApiView extends AbstractActionApiView
{
    public function getSuccessMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $response->status  = static::STATUS_OK;
        $response->message = $this->_getMessage($vars);

        return true;
    } // end getSuccessMessage

    private function _getMessage($vars)
    {
        $mainKey = __("Was Added Record #%d:", $vars['id']);
        $message = array(
            $mainKey => array()
        );
        
        if (!is_array($vars) || !array_key_exists('values', $vars)) {
            return false;
        }
        
        foreach ($vars['values'] as $key => $value) {
            $message[$mainKey][$key] = $value;
        }

        return $message;
    } // end _getMessage

    public function getErrorMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $response->setStatus(Response::STATUS_BAD_REQUEST);
        $response->status  = static::STATUS_ERROR;
        $response->message = $action->getLastError();

        return true;
    } // end getSuccessMessage
}