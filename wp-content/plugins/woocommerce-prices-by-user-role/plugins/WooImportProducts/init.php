<?php

if (!class_exists('CsvWooProductsImporter')) {
    $fileName = 'CsvWooProductsImporter.php';
    require_once __DIR__.DIRECTORY_SEPARATOR.$fileName;
}

if (!class_exists('ImportWooProductException')) {
    $fileName = 'ImportWooProductException.php';
    require_once __DIR__.DIRECTORY_SEPARATOR.$fileName;
}

$this->getPluginInstance(ModulesSwitchListener::IMPORT_PRODUCTS_PLUGIN);