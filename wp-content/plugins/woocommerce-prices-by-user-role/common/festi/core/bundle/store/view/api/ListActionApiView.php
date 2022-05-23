<?php

class ListActionApiView extends AbstractActionApiView
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
        $response->setType(Response::JSON);

        if (!array_key_exists('tableData', $vars)) {
            $this->getErrorMessage($action, $response, $vars);
            return true;
        }

        $this->getSuccessMessage($action, $response, $vars);

        return true;
    } // end onResponse

    public function getSuccessMessage(
        AbstractAction &$action, Response &$response, &$vars
    )
    {
        $name = $action->getStore()->getName();

        $response->status      = static::STATUS_OK;
        $response->$name       = $vars['tableData'];
        $response->rowsPerPage = $action->getStore()->getRowsPerPageCount();
        $response->totalRows   = $action->getStore()->getTotalCount();

        return true;
    } // end getSuccessMessage

    public function getErrorMessage(
        AbstractAction &$action, Response &$response, &$vars)
    {
        $response->setStatus(Response::STATUS_BAD_REQUEST);
        $response->status  = static::STATUS_ERROR;
        $response->message = __(
            "Undefined tableData into ListActionView data"
        );
        return true;
    } // end getErrorMessage
}