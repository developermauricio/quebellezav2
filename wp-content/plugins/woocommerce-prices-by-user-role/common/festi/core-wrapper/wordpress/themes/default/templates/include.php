<?php
$controller = Controller::getInstance();

$engineBaseUrl = $controller->getOption('engine_url');

$controller->includeCss($engineBaseUrl."js/bootstrap/css/bootstrap.min.css");
$controller->includeCss($engineBaseUrl."js/bootstrap/css/bootstrap-theme.min.css");

$controller->includeJs($engineBaseUrl."js/bootstrap/js/bootstrap.min.js");

?>