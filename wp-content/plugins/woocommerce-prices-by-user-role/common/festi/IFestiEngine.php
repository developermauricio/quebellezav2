<?php

interface IFestiEngine
{
    public function makeUniversalLink($url = '');

    public function onInstall();
    public function onUninstall();
    
    public function getLanguageDomain();

    public function getPluginPath();
    public function getPluginCachePath($fileName);
    public function getPluginStaticPath($fileName);
    public function getPluginCssPath($fileName);
    public function getPluginImagesPath($fileName);
    public function getPluginJsPath($fileName);
    public function getPluginTemplatePath($fileName);
    public function getPluginLanguagesPath();
    public function getPluginUrl();
    public function getPluginCacheUrl();
    public function getPluginStaticUrl();
    public function getPluginCssUrl($fileName, $customUrl = false);
    public function getPluginImagesUrl($fileName);
    public function getPluginJsUrl($fileName, $customUrl = false);
    public function getPluginTemplateUrl($fileName);

    public function isPluginActive($pluginMainFilePath);
    public function isPathOption($key);

    public function addActionListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    );

    public function addFilterListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    );

    public function addShortCodeListener($tag, $method);

    public function getOptions($optionName);
    public function getCache($fileName);
    public function updateOptions($optionName, $values = array());
    public function updateCacheFile($fileName, $values);

    public function getFileSystemInstance($method = false);

    public function onEnqueueJsFileAction(
        $handle,
        $file = '',
        $deps = '',
        $version = false,
        $inFooter = false,
        $customUrl = false
    );

    public function onEnqueueCssFileAction(
        $handle,
        $file = '',
        $deps = array(),
        $version = false,
        $media = 'all',
        $customUrl = false
    );

    public function fetch($template, $vars = array());
    public function getUrl();

    public function displayError($error);
    public function displayUpdate($text);
    public function displayMessage($text, $type);
}