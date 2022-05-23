<?php

if (!class_exists("FieldException")) {
    class FieldException extends SystemException
    {
        private $_selector;
    
        /**
         * FieldException constructor.
         * @param string $message
         * @param ?string $selector
         * @param int $code
         */
        public function __construct($message, $selector = null, $code = 0)
        {
            parent::__construct($message, $code);

            $this->_selector = $selector;
        }

        public function getSelector()
        {
            return $this->_selector;
        }
    }
}