<?php

class ListActionWebView extends DefaultActionView
{
    /**
     * @param AbstractAction $action
     * @param Response $response
     * @param array|null $vars
     * @return bool
     * @throws SystemException
     */
    public function onResponse(
        AbstractAction &$action, Response &$response, ?array &$vars
    ): bool
    {
        foreach ($vars as $key => $value) {
            $this->view->$key = $value;
        }
        
        $content = $this->view->fetch('list.php');
        
        $content = $this->_getPreparedContent($content);
        
        $target = array(
            'instance' => &$action,
            'content'  => &$content
        );
        
        $event = new FestiEvent(Store::EVENT_ON_FETCH_LIST, $target);
        $action->getStore()->dispatchEvent($event);
        
        $response->content     = $content;
        $response->storeAction = $action;

        if (!empty($vars['info']) && $this->_isFlushResponse($vars['info'])) {
            $response->flush();
        }

        return true;
    } // end onResponse
    
    /**
     * @param array $info
     * @return bool
     */
    private function _isFlushResponse(array $info): bool
    {
        return $info['filter'] == StoreModel::OPTION_FILTERS_MODE_AJAX
            && Response::isAjaxRequest();
    }
    
    /**
     * @param string $content
     * @return string
     * @throws SystemException
     */
    private function _getPreparedContent(string $content): string
    {
        $templatePath = Core::getInstance()->getOption('engine_path').
                        "templates".DIRECTORY_SEPARATOR;
    
        $content .= $this->view->fetch('list_footer.phtml', null, $templatePath);
        
        return $content;
    } // end _getPreparedContent
}