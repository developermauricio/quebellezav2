<?php

class PluginAction extends AbstractAction
{
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $actionName = $this->store->getAction();
        $action = $this->model->getAction($actionName);
        
        if (!array_key_exists('plugin', $action)) {
            throw new SystemException("Undefined plugin attribute in action");
        }
        
        $plugin = Core::getInstance()->getPluginInstance(
            $action['plugin']
        );

        $data = $this->getDataFromRequest();
        
        $plugin->{$action['method']}(
            $response, 
            $this->store,
            $data[Store::PRIMARY_KEY_IN_REQUEST]
        );
        
        return true;
    } // end onStart
    
    protected function getRequestFields(): ?array
    {
        return array(
            Store::PRIMARY_KEY_IN_REQUEST => self::FIELD_TYPE_STRING
        );
    } // end getRequestFields
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return \Store::ACTION_PLUGIN;
    }
}