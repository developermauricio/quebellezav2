<?php

if (!class_exists("DatabaseException")) {
    class DatabaseException extends Exception
    {
        const ERROR_CONSTRAINT = 1451;
        const ERROR_UNSUPPORTABLE_METHOD = -1000;
        const ERROR_DUPLICATE = 1062;

        private $_query;

        public function __construct($message = "", $code = 0, string $query = null, Throwable $previous = null)
        {
            parent::__construct($message, $code, $previous);

            $this->_query = $query;
        }

        public function getQuery(): ?string
        {
            return $this->_query;
        } // end getQuery

        /**
         * Throw exception.
         *
         * @param int $code
         * @throws DatabaseException
         */
        public static function throwError(int $code)
        {
            $messages = array(
                static::ERROR_UNSUPPORTABLE_METHOD => "Unsupportable method"
            );

            $message = "";
            if (array_key_exists($code, $messages)) {
                $message = $messages[$code];
            }

            throw new DatabaseException($message, $code);
        } // end throwError
    }
}
