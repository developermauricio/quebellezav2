<?php

require_once 'bundle/store/action/EditAction.php';

class InfoAction extends EditAction
{
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $vars = null;
        $primaryKeyValue = $this->primaryKeyValue;
        try {
            if (!$primaryKeyValue) {
                $primaryKeyValue = $this->store
                    ->getPrimaryKeyValueFromRequest();

                $this->load($primaryKeyValue);
            }

            $vars = $this->getValues();

            $this->row = $this->store->loadRowByPrimaryKey(
                $primaryKeyValue, false
            );
        } catch (Exception $exp) {
            $this->setError($exp->getMessage(), $exp);
        }

        $view = $this->store->getView();
        $view->onActionResponse($this, $response, $vars);

        return true;
    } // end onStart

    protected function getValueForField(AbstractField &$field)
    {
        $name = $field->getName();

        if (!$name) {
            $name = "__".$field->getIndex();
        }

        $value = isset($this->data[$name]) ? $this->data[$name] : null;

        if (is_null($value) && $field->get('value')) {
            $value = $field->get('value');
        }

        $field->setItemValue($value);

        return $field->getInfoValue($value, $this->row);
    } // end getValueForField

    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_INFO;
    } // end getActionName

    /**
     * @override
     * @param AbstractField $field
     * @return bool
     */
    protected function isDisplayFieldIntoForm(AbstractField $field): bool
    {
        $primaryKey = $this->store->getPrimaryKey();

        return $field->getName() != $primaryKey &&
            !$field->isVirtualField(Store::ACTION_INSERT);
    } // end isDisplayFieldIntoForm
}
