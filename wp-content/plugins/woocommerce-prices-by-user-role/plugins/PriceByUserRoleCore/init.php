<?php

if (!class_exists('ModulesSwitchListener')) {
    $fileName = 'manage/ModulesSwitchListener.php';
    require_once __DIR__.DIRECTORY_SEPARATOR.$fileName;
}
/*
$facade = EngineFacade::getInstance();

if (!$facade->isTestEnvironmentDefined()) {
    $this->getPluginInstance(ModulesSwitchListener::CORE_PRICES_PLUGIN);
}
*/

$this->addEventListener(
    FestiCoreStandalone::EVENT_ON_INIT,
    function (FestiEvent &$event) {

        $facade = EngineFacade::getInstance();

        if (!$facade->isTestEnvironmentDefined()) {
            $this->getPluginInstance(ModulesSwitchListener::CORE_PRICES_PLUGIN);
        }
    }
);


