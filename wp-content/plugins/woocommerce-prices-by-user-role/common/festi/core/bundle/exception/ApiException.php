<?php

if (!class_exists("ApiException")) {
    class ApiException extends SystemException
    {
        const ERROR_CODE_PARAM             = 100;
        const ERROR_CODE_AUTH_LOGIN        = 401;
        const ERROR_CODE_METHOD            = 3;
        const ERROR_CODE_PARAM_SIGNATURE   = 104;
        const ERROR_CODE_PERMISSION_DENIED = 403;
        const ERROR_CODE_CONFLICT          = 409;
        
        public $params;

        public function __construct(
            $message = "",
            $code = ApiException::ERROR_CODE_INTERNAL_SERVER,
            $data = false,
            &$source = null,
            $displayMessage = false
        )
        {
            parent::__construct(
                $message,
                $code,
                false,
                $source,
                $displayMessage
            );

            $this->params = $data;
        }

        /**
         * @override
         * @return string|void
         */
        public function getDisplayMessage()
        {
            if ($this->hasDisplayMessage()) {
                return parent::getDisplayMessage();
            }

            $code = $this->getCode();

            $messages = array(
                static::ERROR_CODE_INTERNAL_SERVER => __('Internal Server Error'),
                static::ERROR_CODE_CONFLICT => __('Conflict'),
                static::ERROR_CODE_NOT_FOUND => __('Not Found'),
                static::ERROR_CODE_PERMISSION_DENIED => __('Forbidden'),
                static::ERROR_CODE_AUTH_LOGIN => __('Unauthorized')
            );

            if (!empty($messages[$code])) {
                return $messages[$code];
            }

            return parent::getDisplayMessage();
        } // end getDisplayMessage
    }
}