<?php

class BatchInsertActionApiView extends AbstractActionApiView
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
        $mainKey = __("Was Added %d Records:", count($vars));
        $message = array(
            $mainKey => array()
        );

        foreach ($vars as $var) {
            if (!is_array($var) || !array_key_exists('values', $var)) {
                return __("Can`t find values.");
            }

            foreach ($var['values'] as $key => $value) {
                $message[$mainKey][$var['id']][$key] = $value;;
            }
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