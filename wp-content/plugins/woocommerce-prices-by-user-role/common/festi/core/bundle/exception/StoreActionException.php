<?php

namespace core\dgs\action;

class StoreActionException extends \SystemException
{
    /**
     * @var \AbstractAction
     */
    private $_action;

    /**
     * StoreActionException constructor.
     *
     * @param string $message
     * @param \AbstractAction|null $action
     * @param int $code
     */
    public function __construct(string $message, \AbstractAction $action = null, $code = 0)
    {
        parent::__construct($message, $code);

        $this->_action = $action;
    } // end __construct

}