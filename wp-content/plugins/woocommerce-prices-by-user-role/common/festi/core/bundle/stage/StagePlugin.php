<?php

class StagePlugin extends DisplayPlugin
{
    private $_stage;
    
    public function __construct()
    {
        parent::__construct();
    
        $this->_stage = new StageBuilder(2, StageBuilder::LAYOUT_ROWS);
    } // end __construct
    
    public function setSize($size)
    {
        $this->_stage->setSize($size);
    }
    
    public function setLayout($layout)
    {
        $this->_stage->setLayout($layout);
    }
    
    public function addBlock($instance, $size = 1, $className = false)
    {
        return $this->_stage->addBlock($instance, $size, $className);
    } // end addBlock
    
    public function addStoreBlock($store, $size = 1)
    {
        return $this->_stage->addStoreBlock($store, $size);
    } // end addStoreBlock
    
    public function addStoreActionBlock($store, $size = 1)
    {
        return $this->_stage->addStoreActionBlock($store, $size);
    } // end addStoreActionBlock
    
    public function addCallbackBlock(
        $callback, $externalParams = false, $size = 1
    )
    {
        return $this->_stage->addCallbackBlock(
            $callback, 
            $externalParams, 
            $size
        );
    } // end addCallbackBlock
    
    public function addUrlBlock($store, $size = 1)
    {
        return $this->_stage->addUrlBlock($store, $size);
    } // end addUrlBlock
    
    public function onRequest(Response &$response)
    {
        return $this->_stage->onRequest($response);
    } // end onRequest
}