<?php

use \core\dgs\event\UrlStoreActionEvent;

abstract class AbstractAction extends Entity
{
    /**
     * @var Store
     */
    protected $store;
    
    /**
     * Model of storage.
     * 
     * @var StoreModel
     */
    protected $model;
    
    protected $hasError = false;
    protected $lastErrorMessage;
    protected $exception;


    public function __construct(Store &$store)
    {
        $this->store   = &$store;
        $this->model   = &$store->getModel();
    } // end __construct

    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        throw new SystemException("Undefined onStart method on Action");
    } // end onStart

    protected function getRequestFields(): ?array
    {
        return null;
    } // end getRequestFields
    
    /**
     * @return array
     * @throws SystemException
     */
    protected function getDataFromRequest(): array
    {
        $fields = $this->getRequestFields();
        if (!$fields) {
            return array();
        }
        
        if (!array_key_exists($this->store->getIdent(), $_REQUEST)) {
            $_REQUEST[$this->store->getIdent()] = array();
        }
        
        $data = $this->getPreparedData(
            $_REQUEST[$this->store->getIdent()], 
            $fields, 
            $errors
        );
        
        if ($errors) {
            $params = array_keys($errors);
            $msg = "Undefined request params: ".join(", ", $params);
            throw new SystemException($msg);
        }
        
        return $data;
    } // end getDataFromRequest

    /**
     * Returns formatted url for an action.
     *
     * @param array $params
     * @return string
     * @throws SystemException
     */
    protected function getUrl(array $params = array()): string
    {
        $url = $this->store->getOption(Store::OPTION_CURRENT_URL);
        if (!$url) {
            $url = Core::getInstance()->getCurrentUrl();
        }
        
        if ($params) {
            $params = array(
                $this->store->getIdent() => $params
            );
        }

        $event = new UrlStoreActionEvent($url, $params, $this->store);
        $this->store->dispatchEvent($event);

        return Core::getInstance()->getUrl($url, $params);
    } // end getUrl
    
    /**
     * Returns true if action has error.
     * 
     * @return boolean
     */
    public function hasError(): bool
    {
        return $this->hasError;
    } // end hasError
    
    /**
     * Set an error message.
     * 
     * @param string|null $error
     * @param Exception|null $exp
     */
    public function setError(?string $error, ?Exception $exp = null): void
    {
        $this->hasError = true;
        $this->lastErrorMessage = $error;
        $this->exception = $exp;
    } // end setError

    /**
     * Returns last exception.
     * @return Exception
     */
    public function getLastException(): ?Exception
    {
        return $this->exception;
    } // end getLastException
    
    /**
     * Returns the last error message.
     * 
     * @return string
     */
    public function getLastError()
    {
        return $this->lastErrorMessage;
    } // end getLastError
    
    /**
     * Prepare an error response.
     *
     * @param Response $response
     * @return bool
     * @throws StoreException
     */
    protected function error(Response &$response): bool
    {
        $response->setType(Response::JSON);
        
        if (!$this->store->isApiMode()) {
            $response->setType(Response::JSON_IFRAME);
            $response->setAction(Response::ACTION_ALERT);
        }
        
        if (!$this->lastErrorMessage) {
            $this->lastErrorMessage = __l('ERR_UNKNOWN');
        }
        
        if ($this->store->isExceptionMode()) {
            throw new StoreException($this->lastErrorMessage);
        }
        
        $response->addMessage($this->lastErrorMessage);

        return true;
    } // end error
    
    /**
     * Prepare an error response.
     *
     * @param Response $response
     * @return bool
     * @throws StoreException
     */
    public function doApplyError(Response &$response): bool
    {
        return $this->error($response);
    } // end doApplyError

    /**
     * Dispatch the action event.
     *
     * @param string $eventName
     * @param array $data
     * @return bool
     * @throws StoreException
     */
    public function event(string $eventName, array &$data = array())
    {
        $target = array(
            'instance' => &$this,
            'action'   => $this->store->getAction()
        );
        
        $target += $data;
        
        $event = new StoreActionEvent($eventName, $target);
        $this->store->dispatchEvent($event);
        
        if ($this->hasError()) {
            throw new StoreException($this->getLastError());
        }
        
        return true;
    } // end event

    /**
     * @return Store
     */
    public function &getStore()
    {
        return $this->store;
    } // end getStore

    public function isExec()
    {
        return false;
    } // end isExec

    abstract public function getActionName(): string;

    public function getAttribute($key)
    {
        $attributes = $this->model->getAction($this->getActionName());

        if (array_key_exists($key, $attributes)) {
            return $attributes[$key];
        }

        return false;
    } // end getAttribute
    
    public function fetchForm($templateName = false, $action = false)
    {
        throw new SystemException("Undefined fetchForm method on Action");
    } // end fetchForm

    public function onDisplayForm(Response &$response)
    {
        throw new SystemException("Undefined onDisplayForm method on Action");
    } // end onDisplayForm


}