<?php

class EditActionApiView extends AbstractActionApiView
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
        $mainKey = __("Was Changed Record #%d:", $vars['id']);
        $message = array(
            $mainKey => array()
        );

        if (!is_array($vars) || !array_key_exists('values', $vars)) {
            return false;
        }

        foreach ($vars['values'] as $key => $var) {
            $message[$mainKey][$key] = $var;
        }

        return $message;
    } // end _getMessage
}