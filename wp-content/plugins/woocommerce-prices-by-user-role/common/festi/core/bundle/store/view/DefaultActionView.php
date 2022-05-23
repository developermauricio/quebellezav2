<?php

abstract class DefaultActionView implements IActionView
{
    /**
     * @var StoreView
     */
    protected $view;
    
    /**
     * DefaultActionView constructor.
     * @param StoreView $view
     */
    public function __construct(StoreView &$view)
    {
        $this->view = $view;
    }
    
    /**
     * @param AbstractAction $action
     * @param Response $response
     * @return bool
     * @throws StoreException
     * @throws SystemException
     */
    protected function error(AbstractAction &$action, Response &$response)
    {
        $response->setType(Response::JSON);

        if (!$action->getStore()->getModel()->isApiMode()) {
            $response->setType(Response::JSON_IFRAME);
            $response->setAction(Response::ACTION_ALERT);
        }

        $displayMessage = $action->getStore()->getDefaultErrorMessage();
        $msg = $displayMessage;
        
        if ($action->hasError()) {
            $msg = $action->getLastError();  
            
            $exp = $action->getLastException();
            if ($exp instanceof SystemException && $exp->hasDisplayMessage()) {
                $displayMessage = $exp->getDisplayMessage();
            }
        }
        
        if ($action->getStore()->isExceptionMode()) {
            $exp = $action->getLastException();
            if ($exp) {
                if ($exp instanceof SystemException) {
                    $exp->setDisplayMessage($displayMessage);
                }

                throw $exp;
            }

            throw new StoreException(
                $msg,
                StoreException::DEFAULT_ERROR_CODE,
                false,
                $action,
                $displayMessage
            );
        }

        $response->addMessage($displayMessage);

        return true;
    } // end error
}