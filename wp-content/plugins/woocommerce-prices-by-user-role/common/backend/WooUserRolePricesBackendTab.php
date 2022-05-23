<?php

class WooUserRolePricesBackendTab
{
    protected $backend;

    public function __construct($backend)
    {
        $this->backend = $backend;
    } // end __construct

    public function display()
    {
        $vars = $this->_getCurrentValues();

        echo $this->backend->fetch('settings_page.phtml', $vars);
    } // end display

    public function getOptionsFieldSet()
    {
        return $this->backend->getOptionsFieldSet();
    } // end getOptionsFieldSet

    public function doUpdateOptions($params)
    {
        try {
            $key = WooUserRolePricesFestiPlugin::ID_USER_ORDER_OPTION_KEY;
            EngineFacade::getInstance()->deleteTransient($key);
            $this->backend->updateOptions('settings', $params);
            $this->backend->displayOptionPageUpdateMessage(
                'Settings are updated successfully'
            );
        } catch (Exception $exception) {
            $message = $exception->getMessage();
            $this->backend->displayError($message);
        }
    } // end doUpdateOptions

    private function _getCurrentValues()
    {
        $options = $this->backend->getOptions('settings');

        $vars['fieldset'] = $this->getOptionsFieldSet();
        $vars['currentValues'] = $options;

        return $vars;
    } // end _getCurrentValues

    public function getCurrentRoleValueByColumn(
        $roleName,
        $columnKey,
        $currentValue,
        $defaultValue = 0
    )
    {
        $value = $defaultValue;

        if ($this->_hasRoleValueInColumn(
            $roleName,
            $currentValue,
            $columnKey
        )) {
            $value = $currentValue[$roleName][$columnKey];
        }

        return $value;
    } // end getCurrentRoleValueByColumn

    private function _hasRoleValueInColumn($roleName, $currentValue, $columnKey)
    {
        return array_key_exists($roleName, $currentValue) &&
               array_key_exists($columnKey, $currentValue[$roleName]);
    } // end _hasRoleValueInColumn

    public function hasUserRolesInTemplateForm($item)
    {
        if (!array_key_exists('type', $item)) {
            return false;
        }

        $templateTypes = array(
            'multicheck',
            'multidiscount',
            'tax_table',
            'quantity_discount_table'
        );

        return in_array($item['type'], $templateTypes);
    } // end hasUserRolesInTemplateForm

    public function isTaxTableOptionFields($fieldName)
    {
        return $fieldName == 'taxTableHeader' ||
               $fieldName == 'taxByUserRoles';
    } // end isTaxTableOptionFields
}