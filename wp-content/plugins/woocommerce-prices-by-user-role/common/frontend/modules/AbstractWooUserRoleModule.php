<?php

abstract class AbstractWooUserRoleModule
{
    protected static $_frontend;

    protected $ecommerceFacade;
    protected $userRole;
    protected $frontend;

    public function __construct()
    {
        $this->frontend = &static::$_frontend;
        $this->ecommerceFacade = EcommerceFactory::getInstance();
        $this->userRole = $this->frontend->getUserRole();
    } // end __construct
}