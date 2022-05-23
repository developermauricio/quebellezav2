<?php

class StoreAggregationEvent extends FestiEvent
{
    private $_aggregation;
    private $_data;

    public function __construct(&$result, &$data)
    {
        parent::__construct(Store::EVENT_ON_AGGREGATIONS);

        $this->_aggregation = &$result;
        $this->_data = &$data;
    }

    public function &getValues()
    {
        return $this->_aggregation;
    } // end getValues

    public function &getData()
    {
        return $this->_data;
    } // end getData
}