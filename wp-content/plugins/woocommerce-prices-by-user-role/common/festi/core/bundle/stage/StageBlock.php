<?php

abstract class StageBlock
{
    protected $instance;
    protected $view;
    
    private $_stageIndex;
    private $_size;
    
    public function __construct($instance, int $stageIndex, $size, $view)
    {
        $this->instance    = $instance;
        $this->view        = $view;
        $this->_stageIndex = $stageIndex;
        $this->_size       = $size;
        
        $this->onInit();
    } // end __construct
    
    public function exec(Response &$response = null)
    {
        if (is_null($response)) {
            $response = new Response();
        }
        
        return $this->onRequest($response);
    }
    
    protected function onInit()
    {
    }
    
    abstract protected function onRequest(Response &$response);
    
    public function getSize()
    {
        return $this->_size;
    }

    /**
     * Returns block index in StageBuilder.
     *
     * @return int
     */
    public function getStageIndex() : int
    {
        return $this->_stageIndex;
    } // end getStageIndex

    /**
     * Return TRUE if block is executed.
     *
     * @return bool
     */
    public function isExec() : bool
    {
        return false;
    } // end isExec
}
