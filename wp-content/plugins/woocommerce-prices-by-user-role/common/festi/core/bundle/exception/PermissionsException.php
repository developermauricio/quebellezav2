<?php

if (!class_exists("PermissionsException")) {
    class PermissionsException extends SystemException
    {
        protected $code = 403;
    }
}