<?php

class InsertAction extends AbstractDisplayAction
{
    const ATTRIBUTE_PREFILL = "prefill";
    const ATTRIBUTE_MODE    = "mode";

    const MODE_NEW = "new";
    
    private $_formTemplatePath;
    private $_hasParentTransaction;

    protected $updateInfo;
    protected $data;
    protected $primaryKeyValue;
    
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $vars = null;

        if ($this->isExec()) {
            $vars = $this->onUpdate($response);
        }

        $view = $this->store->getView();
        $view->onActionResponse($this, $response, $vars);

        return true;
    } // end onStart

    /**
     * @param Response $response
     * @return array|null
     * @throws StoreException
     */
    public function onUpdate(Response &$response): ?array
    {
        $request = $this->getRequest();

        if (!$request) {
            $this->setError("Invalid Request.");
            return null;
        }

        return $this->apply($request, $response);
    } // end onUpdate
    
    /**
     * @return array|null
     */
    protected function getRequest(): ?array
    {
        return $this->model->getRequest();
    } // end getRequest

    /**
     * Return true if accept a request for processing a data.
     * 
     * @return boolean
     */
    public function isExec()
    {
        $execParam = Store::ACTION_PERFORM_KEY_IN_POST;
        
        return $this->store->getPostParam($execParam) || 
               $this->store->isApiMode();
    } // end isExec

    /**
     * Invoke processing a request data.
     * 
     * @param array $request
     * @param Response|null $response
     * @return array|null
     */
    public function apply(array $request, Response &$response = null): ?array
    {
        try {
            $values = $this->_getPreparedValues($request);
            
            $proxy = &$this->store->getProxy();
            
            $this->_hasParentTransaction = $proxy->isBegin();
            
            if (!$this->_hasParentTransaction) {
                $proxy->begin();
            }

            // XXX: Needed for update by reference via event
            $isUpdated = false;
            $primaryKeyValue = $this->primaryKeyValue;
            
            $this->updateInfo = array(
                'id'        => &$primaryKeyValue,
                'values'    => &$values,
                'data'      => $this->data,
                'isUpdated' => &$isUpdated,
                'response'  => &$response
            );

            $this->_dispatchBeforeUpdateEvent();

            $this->_doSync($proxy, $values);

            $this->_dispatchCompleteEvent();
        } catch (Exception $exp) {
            $this->doHandleException($exp);
            return null;
        }

        if ($this->hasError()) {
            if (!$this->_hasParentTransaction) {
                $proxy->rollback();
            }

            return null;
        }

        if (!$this->_hasParentTransaction) {
            $proxy->commit();
        }
        
        try {
            $this->event(Store::EVENT_AFTER_UPDATE, $this->updateInfo);
        } catch (Exception $exp) {
            // TODO: in future we have to add rollback after commit
            $this->doHandleException($exp);
            return null;
        }

        return $this->updateInfo;
    } // end apply

    /**
     * Handling an exception.
     *
     * @param Exception $exp
     * @return bool
     */
    protected function doHandleException(Exception $exp)
    {
        $proxy = &$this->store->getProxy();

        $inTransaction = $proxy->isBegin();

        if (!$this->_hasParentTransaction && $inTransaction) {
            $proxy->rollback();
        }
        
        $message = $exp->getMessage();
        if ($exp instanceof SystemException) {
            $exp->setSource($this);
            $message = $exp->getDisplayMessage();
        }

        $this->setError($message, $exp);

        return true;
    } // end doHandleException

    /**
     * Update data into a store.
     * 
     * @param IProxy $proxy
     * @param array $values
     * @return boolean
     */
    private function _doSync(IProxy $proxy, array $values): bool
    {
        if ($this->isCustomUpdate()) {
            return false;
        }
        
        if ($this->primaryKeyValue) {
            $proxy->updateByPrimaryKey($this->primaryKeyValue, $values);
        } else {
            $this->updateInfo['id'] = $proxy->insert($values);
            $this->primaryKeyValue = $this->updateInfo['id'];
        }
        
        return true;
    } // end _doSync
    
    /**
     * @throws StoreException
     */
    private function _dispatchBeforeUpdateEvent(): void
    {
        $eventName = Store::EVENT_BEFORE_UPDATE;
        if (!$this->primaryKeyValue) {
            $eventName = Store::EVENT_BEFORE_INSERT;
        }
        
        $this->event($eventName, $this->updateInfo);
    } // end _dispatchBeforeUpdateEvent
    
    /**
     * @throws StoreException
     */
    protected function _dispatchCompleteEvent(): void
    {
        $type = Store::EVENT_INSERT;
        if ($this->store->getAction() == Store::ACTION_EDIT) {
            $type = Store::EVENT_UPDATE;
        }
        
        $this->event($type, $this->updateInfo);
        
        $this->event(Store::EVENT_UPDATE_VALUES, $this->updateInfo);
    } // end _dispatchCompleteEvent
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_INSERT;
    } // end getActionName

    /**
     * @param array $action
     * @return bool
     */
    private function _isNeedPrefill(array $action): bool
    {
        return array_key_exists(self::ATTRIBUTE_PREFILL, $action);
    } // end _isNeedPrefill

    /**
     * @param array $action
     */
    protected function doPrefill(array $action): void
    {
        // FIXME: Fast insert form and performance
        $this->store->setRowsPerPageCount(1);
        $this->store->setCurrentPageIndex(1);
        $rows = $this->store->load(true);

        $values = array();
        if ($rows) {
            $values = $rows[0];
        }

        $fields = &$this->store->getModel()->getFields();

        $target = array(
            'instance' => &$this,
            'fields'   => &$fields,
            'action'   => $action,
            'values'   => &$values,
            'store'    => &$this->store
        );

        $event = new FestiEvent(Store::EVENT_ON_FIELDS_PREFILL_VALUES, $target);
        $this->store->dispatchEvent($event);

        if ($values) {
            foreach ($fields as $fieldName => $field) {
                if (array_key_exists($fieldName, $values)) {
                    $field->set('default', $values[$fieldName]);
                }
            }
        }

    } // end doPrefill

    // TODO: Move to view (InsertActionWebView)
    /**
     * @param bool $templateName
     * @param bool $action
     * @return false|string
     * @throws SystemException
     */
    public function fetchForm($templateName = false, $action = false)
    {
        if (!$action) {
            $action = $this->model->getAction($this->getActionName());
        }

        if (!$action) {
            throw new SystemException(__("Undefined action in model"));
        }

        if ($this->_isNeedPrefill($action)) {
            $this->doPrefill($action);
        }

        $info = array(
            'caption'         => $this->_getFormCaption($action),
            'action'          => Store::ACTION_PERFORM_SAVE,
            'primaryKeyValue' => $this->primaryKeyValue,
            'token'           => $this->store->createInsertToken(),
            'url'             => $this->getUrl(),
            'base_http_icon'  => $this->store->getOption('theme_url').'images/'
        );

        // FIXME:
        if (isset($action['button'])) {
            $info['actionbutton'] = $action['button'];
        } else if (
            !array_key_exists('button', $action) ||
            $action['button'] !== false
        ) {
            $info['actionbutton'] = __l('Submit');
        }

        $this->store->loadForeignKeys();

        $items = $this->_getItems();

        $action = $this->getActionInfoForListDataItem($action);

        if (!$templateName) {
            $templateName = $this->_getTemplateName($action);
        } else {
            $templateName .= ".php";
        }

        $sections = $this->_getPreparedSections($items);

        // XXX: Compability with the old sectionIdent
        // Will be removed in the future
        if (!$sections) {
            $sections = $this->_getPreparedSectionsByItems($items);
        }

        $vars = array(
            'action'       => $action,
            'items'        => &$items,
            'sections'     => &$sections,
            'info'         => &$info,
            'what'         => $this->store->getAction(),
            'values'       => $this->data,
            'store'        => &$this->store,
            'templateName' => $templateName,
            'isTabsMode'   => $this->_isSectionsTabsMode(),
        );

        $event = new FestiEvent(Store::EVENT_ON_FETCH_FORM, $vars);
        $this->store->dispatchEvent($event);

        $view = $this->store->getView();

        return $view->fetch(
            $vars['templateName'],
            $vars,
            $this->_formTemplatePath
        );
    } // end fetchForm

    /**
     * @param $items
     * @return array|null
     */
    private function _getPreparedSections(array &$items): ?array
    {
        $sections = $this->model->getSections();
        if (!$sections) {
            return null;
        }

        $defaultSection = false;

        if ($this->_isSectionsTabsMode()) {
            $defaultSection = $this->_getDefaultSection($sections);
        }

        foreach ($items as $fieldName => $item) {
            $section = $this->_getItemSection($item, $sections);

            if (!$section && $defaultSection) {
                $section = $defaultSection;
            }

            $items[$fieldName]['options']['section'] = $section;

            if ($section) {
                $sections[$section]['fields'][$fieldName] = $fieldName;
            }
        }

        return $sections;
    } // end _getPreparedSections
    
    /**
     * @return bool
     */
    private function _isSectionsTabsMode(): bool
    {
        return $this->model->getOption(StoreModel::OPTION_SECTIONS_MODE) ==
            StoreModel::OPTION_SECTIONS_MODE_TABS;
    } // end _isSectionsTabsMode
    
    /**
     * @param array $sections
     * @return mixed
     */
    private function _getDefaultSection(array $sections)
    {
        $sections = array_keys($sections);
        return array_shift($sections);
    } // end _getDefaultSection

    /**
     * @param array $item
     * @param array $sections
     * @return string|null
     */
    private function _getItemSection(array $item, array $sections): ?string
    {
        if (!$this->_isItemHasSection($item) ||
            !array_key_exists($item['options']['section'], $sections)) {

            return null;
        }

        return $item['options']['section'];
    } // end _getItemSection
    
    /**
     * @param array $items
     * @return array|null
     */
    private function _getPreparedSectionsByItems(array &$items): ?array
    {
        $sections = array();

        foreach ($items as $fieldName => &$item) {
            if (!$this->_isItemHasSectionIdent($item)) {
                $item['options']['section'] = null;
                continue;
            }

            $ident = $item['options']['sectionIdent'];

            if (!array_key_exists($ident, $sections)) {
                $sections[$ident] = array(
                    'caption' => $item['options']['sectionCaption']
                );
            }

            $sections[$ident]['fields'][$fieldName] = $fieldName;
            $item['options']['section'] = $ident;
        }

        if (empty($sections)) {
            return null;
        }

        return $sections;
    } // end _getPreparedSectionsByItems
    
    /**
     * @param array $item
     * @return bool
     */
    private function _isItemHasSection(array $item): bool
    {
        return array_key_exists('section', $item['options']) &&
            !empty($item['options']['section']);
    } // end _isItemHasSection
    
    /**
     * @param array $item
     * @return bool
     */
    private function _isItemHasSectionIdent(array $item): bool
    {
        return array_key_exists('sectionIdent', $item['options']) &&
            !empty($item['options']['sectionIdent']);
    } // end _isItemHasSectionIdent
    
    /**
     * Returns prepared values from a request and append predefined
     * values.
     *
     * @param array $request
     * @return array
     * @throws FieldException
     * @throws StoreException
     * @throws SystemException
     */
    private function _getPreparedValues(array $request): array
    {
        $externalValues = $this->model->getExternalValues();
        if ($externalValues) {
            $request = array_merge($request, $externalValues);
        }
        
        $target = array(
            'request' => &$request,
        );
        
        $this->event(Store::EVENT_PREPARE_ACTION_REQUEST, $target);
        
        $values = array();
        foreach ($this->model->getFields() as $field) {

            if (!$this->_isNeedPrepareFieldValue($field)) {
                continue;
            }
            
            $this->_appendFieldValue($field, $request, $values);
        }

        if ($externalValues) {
            $values = array_merge($values, $externalValues);
        }
        
        $token = $this->store->getInsertTokenFromRequest();
        if ($token) {
            $this->_appendSessionValues($token, $values);
        }
        
        $filters = $this->model->getFilters();
        if ($filters) {
            $values = array_merge($values, $filters);
        }
        
        $target = array(
            'values' => &$values,
        );
        
        $this->event(Store::EVENT_PREPARE_VALUES, $target);
        
        return $values;
    } // end _getPreparedValues
    
    /**
     * @param AbstractField $field
     * @param array $request
     * @param array $values
     * @return bool
     * @throws FieldException
     * @throws SystemException
     */
    private function _appendFieldValue(
        AbstractField $field, array &$request, array &$values
    ): bool
    {
        $value = $field->getValue($request);
        if ($value === false) {
            throw new FieldException(
                $field->getLastErrorMessage(), 
                $field->getCssSelector()
            );
        } else if ($value === true) {
            // XXX: That means the field does update value by self.
            // You can see that in Many2manyField
            return false;
        }
        
        $values[$field->getName()] = $value;
        
        return true;
    } // end _appendFieldValue
    
    /**
     * @param string $token
     * @param array $values
     * @return bool
     */
    private function _appendSessionValues(string $token, array &$values): bool
    {
        $session = &$this->store->getSession();
        if (empty($session['insert'][$token])) {
            return false;
        }

        $tokenValues = $session['insert'][$token];
        foreach ($tokenValues as $key => $value) {
            if ($key && !isset($values[$key])) {
                $values[$key] = $value;
            }
        }

        return true;
    } // end _appendSessionValues

    /**
     * Returns true if a value need processing in a field.
     * 
     * @param AbstractField $field
     * @return boolean
     */
    private function _isNeedPrepareFieldValue(AbstractField $field): bool
    {
        $primaryKey = $this->model->get('primaryKey');
        $fieldName = $field->getName();

        return  !$field->get('readonly') && 
                !$field->isVirtualField() &&
                !$field->get('onlyList') &&
                $fieldName != $primaryKey;
    } // end _isNeedPrepareFieldValue
    
    /**
     * @param array $action
     * @return string
     */
    private function _getTemplateName(array $action): string
    {
        $templateName = 'form_'.$this->getActionName();
        if ($action['mode'] == Store::ACTION_VIEW_MODE_NEW) {
            $templateName .= '_new';
        }

        $templateName .= '.php';

        $view = $this->store->getView();

        if (!$view->isTemplateExists($templateName)) {
            $templateName = 'form.php';
        }

        return $templateName;
    } // end _getTemplateName

    /**
     * Returns true if a field has to display into the action form.
     * 
     * @param AbstractField $field
     * @return boolean
     */
    protected function isDisplayFieldIntoForm(AbstractField $field)
    {
        $primaryKey = $this->store->getPrimaryKey();

        if ($field->get('onlyList')) {
            return false;
        }

        return $field->getName() != $primaryKey && 
               !$field->isVirtualField(Store::ACTION_INSERT);
    } // end isDisplayFieldIntoForm
    
    /**
     * @return array
     * @throws SystemException
     */
    private function _getItems(): array
    {
        $items = array();
        foreach ($this->model->getFields() as $field) {

            if (!$this->isDisplayFieldIntoForm($field)) {
                continue;
            }

            $field->setAction($this);
            
            $fieldName = $field->getName();

            $value = $this->getValueForField($field);

            $item = array(
                'caption'    => $field->get('caption'),
                'required'   => $field->get('required'),
                'readonly'   => $field->get('readonly'),
                'disclaimer' => $field->get('disclaimer'),
                'name'       => $fieldName,
                'input'      => $value,
                'value'      => $field->getItemValue(),
                'hint'       => $field->get('hint'),
                'options'    => $field->getAttributes()
            );

            if ($fieldName) {
                $items[$fieldName] = $item;
            } else {
                $items[] = $item;
            }
        } // end foreach


        $target = array(
            'instance' => &$this,
            'result'   => &$items,
            'action'   => $this->store->getAction()
        );

        $event = new FestiEvent(Store::EVENT_ACTION_ITEMS, $target);
        $this->store->dispatchEvent($event);

        return $items;
    } // end _getItems
    
    /**
     * @param AbstractField $field
     * @return false|string
     * @throws SystemException
     */
    protected function getValueForField(AbstractField &$field)
    {
        $defaultValue = $field->get('default');
        $value = $defaultValue ? $defaultValue : null;

        return $field->getEditInput($value);
    } // end getValueForField
    
    /**
     * @param array $action
     * @return string
     */
    private function _getFormCaption(array $action): string
    {
        $caption = $action['caption'];
        if (!empty($action['title'])) {
            $caption = $action['title'];
        }

        if (!empty($this->data)) {
            $caption = Display::fillString($caption, $this->data);
        }

        return $caption;
    } // end _getFormCaption

    public function setFormTemplatePath($path)
    {
        $this->_formTemplatePath = $path;
    } // end setFormTemplatePath

    public function getPrimaryKeyValue()
    {
        return $this->primaryKeyValue;
    } // end getPrimaryKeyValue

    public function getValues(): ?array
    {
        return $this->data;
    } // end getValues

    public function setValues($data)
    {
        $this->data = $data;
    } // end setValues
    
    /**
     * @param Response $response
     * @throws SystemException
     */
    public function onDisplayForm(Response &$response)
    {
        $action = $this->model->getAction($this->getActionName());

        if ($this->isActionWithAjaxResponse($action)) {
            $response->flush();
        }

        $response->content = $this->fetchForm(false, $action);
    } // end onDisplayForm
    
    /**
     * Returns true if using a custom update for a store.
     * 
     * @return boolean
     */
    protected function isCustomUpdate(): bool
    {
        return !empty($this->updateInfo['isUpdated']) &&
               $this->updateInfo['isUpdated'] === true;
    } // end isCustomUpdate
    
}
