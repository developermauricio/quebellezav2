<?php
use \core\dgs\event\UrlStoreActionEvent;

class StoreStageBlock extends StageBlock
{
    /**
     * @var Store
     */
    protected $instance;

    /**
     * @override
     */
    protected function onInit()
    {
        if (!($this->instance instanceof Store)) {
            throw new StageException(
                "Undefined sore stage block instance type."
            );
        }
        
        $this->instance->addEventListener(
            Store::EVENT_PREPARE_ACTION_URL, 
            array($this, 'onPrepareActionUrl')
        );
    } // end onInit
    
    public function onPrepareActionUrl(UrlStoreActionEvent &$event)
    {
        $params = &$event->getParams();

        $externalParams = $_REQUEST;
        
        $params[StageBuilder::REQUEST_KEY_BLOCK_INDEX] = $this->getStageIndex();
        
        foreach ($externalParams as $key => $value) {
            if (array_key_exists($key, $params)) {
                continue;
            }
            $params[$key] = $value;
        }
    } // end onPrepareActionUrl
    
   /**
    * @override
    */
    protected function onRequest(Response &$response)
    {
        $this->instance->onRequest($response);
        
        if (Response::isAjaxRequest()) {
            return true;
        }
        
        return $response->getContent();
    }

    public function isExec() : bool
    {
        $actionName = $this->instance->getActionNameFromRequest();

        $action = $this->instance->createActionInstance($actionName);

        return $action->isExec();
    } // end isExec
}