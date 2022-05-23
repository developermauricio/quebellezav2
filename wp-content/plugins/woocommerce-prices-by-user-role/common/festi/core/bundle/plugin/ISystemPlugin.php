<?php

interface ISystemPlugin extends IEventDispatcher
{
    const EVENT_ON_PREPARE_MENU_ITEMS = "event_on_prepare_menu_items";
    const EVENT_ON_BEFORE_REQUEST_PLUGIN_METHOD = "event_on_request_plugin";

    const PERMISSION_MASK_READ = 2;
    const PERMISSION_MASK_WRITE = 2;
    const PERMISSION_MASK_EXECUTE = 6;

    public function bindRequest($options = array());
    public function onInitRequest($options);
    public function getResponseModel($call, $regs = array());
    public function getSetting($key);
    public function install();

    public function onDisplayMain(
        Response &$response,
        $storeName = false,
        $pluginName = false,
        $params = array()
    );
    
    public function setActiveMenu($ident);
    public function getActiveMenu();

    public function hasUserPermissionToSection($sectionName, $user = null);
    
    public function __getName();
    public function hasSetting($key);

    public function onBind();
    public function onException($event);

    /**
     * Create or update permission section.
     *
     * @param string $name section name
     * @param int $mask permission mask 2, 4 or 6
     * @param int|null $userType
     * @param array|null $users
     * @return int
     */
    public function setPermissionSection(string $name, int $mask, int $userType = null, array $users = null): int;

    /**
     * Reload a permission sections.
     */
    public function refreshPermissionSections(): void;
}