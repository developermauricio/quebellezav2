<?php

if (!class_exists("SystemException")) {
    class SystemException extends Exception
    {
        const ERROR_CODE_INTERNAL_SERVER = 500;
        const ERROR_CODE_NOT_FOUND = 404;
        
        private $_data;
        private $_label;
        private $_source;
        private $_displayMessage;

        public function __construct(
            $message = "",
            $code = 0,
            $data = false,
            $source = null,
            $displayMessage = false
        )
        {
            parent::__construct($message, $code);

            if (is_array($data)) {
                $this->_data = $data;
            } else {
                $this->_label = $data;
            }

            $this->setDisplayMessage($displayMessage);
            $this->setSource($this->_source);
        } // end __construct

        public function getLabel()
        {
            return $this->_label;
        }

        public function getData()
        {
            return $this->_data;
        }

        /**
         * Set an object of exception. For example, you can set a Store (DGS)
         *
         * @param mixed &$source
         */
        public function setSource(&$source)
        {
            $this->_source = &$source;
        } // end setSource

        /**
         * Returns the object of exception.
         *
         * @return mixed
         */
        public function &getSource()
        {
            return $this->_source;
        } // end getSource

        /**
         * Returns true if exception has the object.
         *
         * @return bool
         */
        public function hasSource()
        {
            return !empty($this->_source);
        } // end hasSource

        /**
         * Set a message to display.
         * @param string $message
         */
        public function setDisplayMessage($message)
        {
            $this->_displayMessage = $message;
        } // end setDisplayMessage

        public function hasDisplayMessage()
        {
            return !empty($this->_displayMessage);
        } // end hasDisplayMessage
        
        /**
         * Returns message to display
         * @return string
         */
        public function getDisplayMessage()
        {
            if (!$this->_displayMessage) {
                return $this->getMessage();
            }

            return $this->_displayMessage;
        } // end getDisplayMessage

    }
}
