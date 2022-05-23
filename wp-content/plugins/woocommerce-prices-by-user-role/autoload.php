<?php

if (!class_exists('SettingsWooUserRolePrices')) {
    require_once $pluginPath.'SettingsWooUserRolePrices.php';
}

if (!class_exists('FestiTeamApiClient')) {
    $fileName = 'FestiTeamApiClient.php';
    require_once $pluginPath.'common/api/'.$fileName;
}

if (!class_exists('WooUserRolePricesBackendTab')) {
    $fileName = 'WooUserRolePricesBackendTab.php';
    require_once $pluginPath.'common/backend/'.$fileName;
}