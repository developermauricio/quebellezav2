<?php

interface ISystemObject
{
    public function getPrefix();
    public function getSettings();
    public function getUrlRules($search);
    public function isInstalled();
    
    public function getUrlAreas($search = array());
    public function addUrlAreas($areas);
    public function getPlugin($search);
    public function getPlugins($search);
    public function addPlugin($values);
    public function searchUrlRules($search);
    public function addUrlRulesToAreas($values);
    public function getSection($search);
    public function changeSections($values, $search);
    public function addSection($values);
    public function getSectionAction($search);
    public function addSectionAction($values);
    public function changeSectionAction($values, $search);
    public function addUserTypesToSection($values);
    public function addUsersToSection($values);
}
