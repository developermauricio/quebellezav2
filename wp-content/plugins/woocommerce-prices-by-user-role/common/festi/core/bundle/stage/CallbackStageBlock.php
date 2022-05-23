<?php
use core\stage\StageException;

class CallbackStageBlock extends StageBlock
{
    private $_externalParams;
    
    public function setExternalParams($params)
    {
        $this->_externalParams = $params;
    }
    
    /**
     * @override
     */
    protected function onRequest(Response &$response)
    {
        // XXX: Compatibility with default DisplayPlugin logic
        $blockResponse = new Response();
        $params = array(
            &$blockResponse
        );
        
        if ($this->_externalParams) {
            $params = array_merge($params, $this->_externalParams);
        }

        $result = call_user_func_array($this->instance, $params);

        // FIXME: Remove when old projects will be fixed
        if (!is_bool($result)) {
            throw new StageException("StageBlock#".$this->getStageIndex()." is expected bool result.");
        }

        return $blockResponse->getContent();
    } // end onRequest
    
}