<?php

class ContentStageBlock extends StageBlock
{

    /**
     * @override
     */
    protected function onRequest(Response &$response)
    {
        return $this->instance;
    } // end onRequest
}