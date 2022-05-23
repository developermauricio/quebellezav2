<?php

class RemoveActionApiView extends AbstractActionApiView
{
    public function getSuccessMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $response->status  = static::STATUS_OK;
        $response->message = $this->_getMessage($vars);

        return true;
    }

    private function _getMessage($vars)
    {
        $mainKey = __("Record Was Delete Success:");
        $message = array(
            $mainKey => array()
        );

        if (array_key_exists('data', $vars)) {
            return false;
        }

        foreach ($vars['data'] as $key => $value) {
                $message[$mainKey][$key] = $value;
        }

        return $message;
    }
}