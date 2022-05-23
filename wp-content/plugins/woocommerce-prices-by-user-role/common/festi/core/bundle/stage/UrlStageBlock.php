<?php

class UrlStageBlock extends StageBlock
{
    /**
     * @override
     */
    protected function onRequest(Response &$response)
    {
        
        $this->view->containerName = 'stage-block-url-'.$this->getStageIndex();
        $this->view->url = $this->instance;
        
        return $this->view->fetch('stage/block_url.phtml');
    } // end onRequest
}