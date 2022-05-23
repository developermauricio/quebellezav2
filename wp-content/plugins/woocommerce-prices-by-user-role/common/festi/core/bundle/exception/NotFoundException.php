<?php

if (!class_exists("NotFoundException")) {
    class NotFoundException extends SystemException
    {
        public function __construct($message = "", $code = 0, Throwable $previous = null)
        {
            parent::__construct($message, $code ? $code : static::ERROR_CODE_NOT_FOUND, $previous);
        }
    }
}