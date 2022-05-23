<?php

class ListAction extends AbstractDisplayAction
{
    protected $storageValues;

    protected $storageTotals;

    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        if ($this->_onStartParentRelationRequest($response)) {
            return true;
        }

        $this->_prepareFiltersFromRequest();

        $vars = $this->_loadData();
        
        $view = $this->store->getView();

        $view->onActionResponse($this, $response, $vars);
        
        return true;
    } // end onStart

    private function _onStartParentRelationRequest(Response &$response): bool
    {
        // XXX: Fixing direct url to DGS with parent relation action.
        $parentAction = $this->store->getModel()->getAction("parent");
        if ($parentAction) {
            $parentValue = $this->store->getParentValue();
            if (!$parentValue) {
                // FIXME:
                $parentAction['mode'] = Store::ACTION_VIEW_MODE_NEW;
                $actionInfo = $this->getActionInfoForListDataItem(
                    $parentAction
                );

                $relations = $this->model->getRelation($actionInfo['type']);

                $storeName = $this->store->getName();

                // XXX: if parent is the same store
                if (array_key_exists($storeName, $relations)) {
                    $relation = $relations[$storeName];
                    if ($relation['type'] == "parent") {
                        return false;
                    }
                }

                $response->setAction(Response::ACTION_REDIRECT);
                $response->url = $actionInfo['link'];
                return true;
            }
        }

        return false;
    } // end _onStartParentRelationRequest

    /**
     * @return bool
     */
    private function _isLoadAllColumns(): bool
    {
        $action = $this->model->getAction(Store::ACTION_LIST);
        return !empty($action['isLoadAllColumns']) && 
               (
                   $action['isLoadAllColumns'] === 'true' || 
                   $action['isLoadAllColumns'] === true
               );
    } // end _isLoadAllColumns

    /**
     * @return array
     */
    private function _loadData(): array
    {
        $isLoadAllColumns = $this->_isLoadAllColumns();

        $this->storageValues = $this->store->load($isLoadAllColumns);

        $vars = array(
            'store'  => &$this->store,
            'values' => &$this->storageValues
        );
        $event = new FestiEvent(Store::EVENT_ON_LOAD_ACTION_ROWS, $vars);
        $this->store->dispatchEvent($event);

        $info = array(
            'name'           => $this->store->getIdent(),
            'primary_key'    => $this->store->getPrimaryKey(),
            'emptyMessage'   => $this->_getEmptyMessage(),
            'caption'        => $this->_getCaption(),
            'parent'         => $this->_getParentCaption(),
            'baseurl'        => $this->store->getOption('http_base'),
            'totalRows'      => $this->store->getTotalCount(),
            'rowsPerPage'    => $this->store->getRowsPerPageCount(),
            'fields'         => $this->_getFields(),
            'generalActions' => false,
            'fieldInputs'    => $this->_getInsertFields(),
            'insert'         => $this->_getInsertAction(),
            'pager'          => false,
            'limitOptions'   => $this->_getLimitOptions(),
            'grouped'        => $this->_getGroupedActions(),
            'base_http_icon' => $this->store->getOption('theme_url').'images/',
            'filter'         => $this->model->get('filter'),
            'fastAdd'        => $this->model->get('fastAdd'),
            'grideditor'     => $this->model->get('grideditor'),
            'url'            => $this->getUrl(),
            'pagingMode'     => $this->model->get('paging'),
            'insertForm'     => false,
            'refreshTimeout' => $this->model->get('refreshDataTimeout')
        );

        if ($this->model->isWebMode()) {
            $info['generalActions'] = $this->_getGeneralActions();
            $info['pager'] = $this->_fetchPager();
        }
        
        $fastForm = $this->_hasFastInsertForm($info);
        if ($fastForm) {
            $this->_prepareFastForm($info, $fastForm);
        }
   
        if ($info['fastAdd']) {
            $info['token'] = $this->store->createInsertToken();
        }
        
        //
        $info['indexes'] = array();
        foreach ($info['fields'] as $columnIndex => $field) {
            if (isset($field['name'])) {
                $info['indexes'][$field['name']] = $columnIndex;
            }
        }
        
        $data = $this->_getListData();

        $this->storageTotals = $this->store->aggregate($this->storageValues);

        $vars = array(
            'info'      => &$info,
            'data'      => &$data,
            'tableData' => $this->storageValues,
            'filters'   => $this->_getFilters(),
            'store'     => &$this->store,
            'totals'    => &$this->storageTotals
        );

        $event = new FestiEvent(Store::EVENT_ON_LOAD_LIST_DATA, $vars);
        $this->store->dispatchEvent($event);
        
        return $vars;
    } // end _loadData

    private function _prepareFastForm(&$info, $fastForm): void
    {
        $templatePrefix = $fastForm;
        if ($fastForm === true || $fastForm == "true") {
            $templatePrefix = 'forms/fast_form';
        }

        $store = $this->store->cloneInstance();
        $action = $store->createActionInstance(Store::ACTION_INSERT);
        $info['insertForm'] = $action->fetchForm($templatePrefix);
    } // end _prepareFastForm

    private function _hasFastInsertForm($info)
    {
        if (!$info['generalActions'] ||
            !array_key_exists(Store::ACTION_INSERT, $info['generalActions'])) {
            return false;
        }

        $action = $info['generalActions'][Store::ACTION_INSERT];

        if (empty($action['fast']) ||
            $action['fast'] === false ||
            $action['fast'] === "false") {
            return false;
        }

        return $action['fast'];
    } // end _hasFastInsertForm

    /**
     * @return bool
     */
    private function _prepareFiltersFromRequest(): bool
    {
        $isUseFilter = $this->store->getRequestParam('filter_wtd', $_GET);
        $request = $this->store->getRequestParam('filter', $_GET);
        
        if (!$isUseFilter || !$request) {
            return false;
        }
        
        $filtersValues = (array) $request;
        $session = &$this->store->getSession();
        
        $filters = &$session[Store::FILTERS_KEY_IN_SESSION];
        $session[Store::FILTERS_KEY_IN_SESSION] = array();
        
        $storageName = $this->store->getIdent();
        
        if (!isset($filters[$storageName])) {
            $filters[$storageName] = array();
        }
        
        foreach ($filtersValues as $key => $value) {
            // XXX: XSS Protection
            $value = $this->_getPrepareFilterValue($value);
            
            if (!is_numeric($value) && empty($value)) {
                 unset($filters[$storageName][$key]);
                 continue;
            }
    
            $filters[$storageName][$key] = $value;
        }

        return true;
    } // end _prepareFiltersFromRequest
    
    private function _getPrepareFilterValue($value)
    {
        if (is_array($value)) {
            $callback = array($this, '_onPrepareFilterValue');
            return array_filter($value, $callback);
        } 
        
        return $this->_onPrepareFilterValue($value);
    } // end _getPrepareFilterValue
    
    private function _onPrepareFilterValue($value)
    {
        $value = filter_var(
            $value, 
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        
        return $value;
    } // end _onPrepareFilterValue
    
    private function _getAllowedActions($rowValues = array()): array
    {
        $actions = $this->model->getActions();
        $alowedActions = array_keys($actions);

        $target = array(
            'instance' => &$this,
            'actions'  => &$alowedActions,
            'values'   => $rowValues
        );
        
        $event = new StoreActionEvent(Store::EVENT_ON_LIST_ACTIONS, $target);
        $this->store->dispatchEvent($event);
        
        return $alowedActions;
    } // end _getAllowedActions

    /**
     * @return bool
     */
    private function _isAllowedInsertAction(): bool
    {
        $res = $this->model->hasAction(Store::ACTION_INSERT);
        if (!$res) {
            return false;
        }
        
        $allowedActions = $this->_getAllowedActions();
        return in_array(Store::ACTION_INSERT, $allowedActions);
    } // end _isAllowedInsertAction
    
    private function _getInsertAction()
    {
        $action = array();
        if ($this->_isAllowedInsertAction()) {
            $action = $this->model->getAction(
                Store::ACTION_INSERT
            );
        }
        
        return $action;
    } // end _getInsertAction

    /**
     * @return array
     */
    private function _getLimitOptions(): array
    {
        return array(
            20 => 20, 50 => 50, 100 => 100, '1000' => __l('All')
        );
    } // end _getLimitOptions
    
    private function _getGroupedActions()
    {
        $groupActions = $this->model->getGroupActions();

        return $groupActions ? $groupActions : false;
    } // end _getGroupedActions

    /**
     * @return string
     */
    private function _getEmptyMessage(): string
    {
        $emptyMessage = $this->model->get('emptyMessage');
        if (!$emptyMessage) {
            $emptyMessage = __l('No records found...');
        }
        
        return $emptyMessage;
    } // end _getEmptyMessage
    
    /**
     * Returns array of field inputs for fast insert form in list page.
     * 
     * @return array
     */
    private function _getInsertFields(): array
    {
        $fieldInputs = array();

        $hasInsertForm = $this->model->get('fastAdd');
        if (!$hasInsertForm) {
            return $fieldInputs;
        }

        $primaryKey = $this->store->getPrimaryKey();        
        $this->store->loadForeignKeys();

        foreach ($this->model->getFields() as $field) {
            $isPrimaryKey = ($field->getName() == $primaryKey);
            
            if ($isPrimaryKey || $field->isVirtualField()) {
                $fieldInputs[] = '';
            } else {
                $defaultValue = $field->get('default');
                $value = $defaultValue ? $defaultValue : null;
                $fieldInputs[] = $field->getEditInput($value, true);
            }
        }
        
        return $fieldInputs;
    } // end _getInsertFields
    
    private function _getListData()
    {
        $data = array();
        $primaryKeyValues = array();
        
        foreach ($this->storageValues as $tdRow) {
            $info = $this->onStorageRow($tdRow);
            $primaryKeyValues[] = $info['id'];
            $data[] = $info;
        }
        
        $this->store->addAllowedTableRow($primaryKeyValues);
        
        return $data;
    } // end _getListData
    
    protected function onStorageRow($row)
    {
        $primaryKey = $this->store->getPrimaryKey();

        if (!isset($row[$primaryKey])) {
            throw new SystemException("Not found primary key");
        }
        
        $primaryKeyValue = $row[$primaryKey];

        $info = array(
            'id'     => $primaryKeyValue,
            'data'   => array(),
            'rowCss' => ''
        );
        
        //
        $highlights = $this->model->getHighlights();
        foreach ($highlights as $rules) {
            $isHighlights = true;
            foreach ($rules['fields'] as $key => $value) {
                if ($row[$key] != $value) {
                    $isHighlights = false;
                    break;
                }
            }
            
            if ($isHighlights) {
                $info['rowCss'] = $rules['css'];
            }
        }

        //
        foreach ($this->model->getFields() as $field) {

            if (!$field->isShow()) {
                continue;
            }
            
            $item = $this->onStorageRowValue($field, $row);
            
            $info['data'][] = $item;
            $info['values'][$item['name']] = $item['sourceValue'];
            $info['displayValues'][$item['name']] = $item['value'];
        } // end foreach

        if ($this->model->isWebMode()) {
            $this->_appendActionsForListDataItem($info, $row);
        }

        return $info;
    } // end onStorageRow
    
    protected function onStorageRowValue(AbstractField $field, $row)
    {
        $primaryKey = $this->store->getPrimaryKey();
        $this->primaryKeyValue = $row[$primaryKey];
        
        $fieldName = $field->getName();
        
        if (!$fieldName) {
            $fieldName = "__".$field->getIndex();
        }
            
        if (!array_key_exists($fieldName, $row)) {
            $row[$fieldName] = null;
        }
            
        $value = $row[$fieldName];
        
        $displayValue = $this->getCellValue($field, $value, $row);
        
        $item = array(
            'type'         => $field->getType(),
            'name'         => $fieldName,
            'value'        => $displayValue,
            'sourceValue'  => $row[$fieldName],
            'containerCss' => $field->get(AbstractField::OPTION_CONTAINER_CSS)
        );
        
        return $item;
    } // end onStorageRowValue

    /**
     * Returns caption for list.
     * 
     * @throws SystemException
     * @return string
     */
    private function _getCaption()
    {
        $listAction = $this->model->getAction(
            Store::ACTION_LIST
        );
        
        if (!$listAction) {
            throw new SystemException("Not found list action");
        }
        
        $caption = $listAction['caption'];
        
        $parentAction = $this->model->getAction(Store::ACTION_PARENT);
        
        if ($parentAction) {
            $parentCaption = $this->store->getSessionValue("caption");
            if ($parentCaption) {
                $caption = $parentCaption.' / '.$caption;
            }
        }
        
        return $caption;
    } // end _getCaption
    
    /**
     * Returns caption for parent list.
     * 
     * @return string
     */
    private function _getParentCaption()
    {
        $parentAction = $this->model->getAction(
            Store::ACTION_PARENT
        );
        
        if (!$parentAction) {
            return false;
        }
        
        return $parentAction['caption'];
    } // end _getParentCaption
    
    /**
     * Returns fields attributes.
     * 
     * @return array
     */
    private function _getFields()
    {
        $fields = array();
        
        foreach ($this->model->getFields() as $key => $field) {
            if (!$field->isShow()) {
                continue;
            }
            
            $info = $field->getAttributes();
            
            $info['sorting'] = $field->isSorting();
            if ($info['sorting']) {
                $info['sorting'] = $this->_getSortingOptionsByField($field);
            }
        
            $fields[] = $info;
        }

        return $fields;
    } // end _getFields

    private function _getSortingOptionsByField(AbstractField $field)
    {
        $fieldName = $field->getName();
        
        $sortingFieldName = $this->store->getOrderByFieldName();
        $sortingDirection = $this->store->getOrderByDirection();
        
        $direction = 'ASC';
        if ($sortingFieldName == $fieldName && $sortingDirection == 'ASC') {
            $direction = 'DESC';
        }
            
        $params = array(
            $this->store->getIdent() => array(
                'order'     => $fieldName,
                'direction' => $direction
            )
        );
                
        $url = Core::getInstance()->getUrl(
            $this->store->getOption('current_url'),
            $params
        );
        
        $options = array(
            'url'       => $url,
            'direction' => $direction,
            'current'   => ($sortingFieldName == $fieldName)
        );
        
        return $options;
    } // end _getSortingUrlByField
    

    /**
     * Returns filters row. Returns false if not found filters.
     * 
     * @return boolean|array
     */
    private function _getFilters() 
    {
        $filters = array();

        $filtersTypes = array(
            Store::ACTION_VIEW_MODE_TOP,
            Store::ACTION_VIEW_MODE_RIGHT
        );

        $filter = $this->model->get('filter');
        $isLoadAllFilters = $this->_isLoadAllColumns() && 
                            in_array($filter, $filtersTypes);

        $hasFilters = false;
        foreach ($this->model->getFields() as $key => $field) {
            if (!$isLoadAllFilters && !$field->isShow()) {
                continue;
            }
            $ident = $field->getFilterKey();
            $filters[$ident][AbstractField::OPTION_CONTAINER_CSS] = $field->get(AbstractField::OPTION_CONTAINER_CSS);
            
            $filter = $field->fetchFilter();

            if (!$filter) {
                $filters[$ident]['html'] = '';
                continue;
            }

            $filters[$ident]['html'] = $filter;

            $hasFilters = true;
        } // end foreach

        return $hasFilters ? $filters : false;
    } // end _getFilters

    /**
     * Returns a list of actions for the table. 
     * That displays at the top of the table
     */
    private function _getGeneralActions()
    {
        $generalActions = array();
        $allowedActions = array();

        $parentValue = $this->store->getParentValue();
        if ($parentValue) {
            $allowedActions[] = Store::ACTION_PARENT;
        }
        
        if (!$this->model->get('fastAdd')) {
            $allowedActions[] = Store::ACTION_INSERT;
        }
        
        foreach ($this->model->getActions() as $type => $action) {
            if (
                !in_array($type, $allowedActions) && 
                $action['view'] != Store::ACTION_VIEW_MODE_TOP
            ) {
                continue;
            }
            
            if ($type == Store::ACTION_PARENT) {
                $action['mode'] = Store::ACTION_VIEW_MODE_NEW;
            }
            
            $generalActions[$type] = $this->getActionInfoForListDataItem(
                $action
            );
        }
        
        $target = array(
            'actions' => &$generalActions,
        );
        
        $event = new FestiEvent(Store::EVENT_ON_LIST_GENERAL_ACTIONS, $target);
        $this->store->dispatchEvent($event);

        return $generalActions;
    } // end _getGeneralActions
    
    /**
     * Returns html pager for list template
     * 
     * @return string
     */
    private function _fetchPager()
    {
        $rowsPerPage = $this->store->getRowsPerPageCount();
        $totalRows = $this->store->getProxy()->getCount();

        if (!$rowsPerPage || $totalRows < $rowsPerPage) {
            return '';
        }

        $pagingMode = $this->model->get('paging');
        $core = Core::getInstance();

        // TODO: Added ThemeKit Wrapper
        $engineBaseUrl = $core->getOption('theme_url')."assets/";

        if ($pagingMode == "ajax") {
            $core->includeJs(
                $engineBaseUrl."js/jquery.infinitescroll.js", 
                false
            );
        }

        $vars = array(
            'totalItems'    => $totalRows,
            'perPage'       => $rowsPerPage,
            'baseUrl'       => $this->getUrl(),
            'currentPage'   => $this->store->getCurrentPageIndex(),
            'pagingMode'    => $pagingMode,
            'engineBaseUrl' => $engineBaseUrl
        );

        $view = $this->store->getView();

        return $view->fetch('pager.php', $vars);
    } // end _fetchPager
    
    private function _appendActionsForListDataItem(&$listRow, $rowData)
    {
        $alowedActions = $this->_getAllowedActions($rowData);
        
        $listRow['actions'] = array();
        $listRow['action_lists'] = array();
        
        $generalActions = array(
            Store::ACTION_LIST, 
            Store::ACTION_INSERT, 
            Store::ACTION_CHILD, 
            Store::ACTION_PARENT
        );
        
        foreach ($this->model->getActions() as $actionType => $action) {
            
            // FIXME:
            if (
                in_array($actionType, $generalActions) || 
                !in_array($actionType, $alowedActions)
            ) {
                continue;
            }
            
            if ($action['view'] == Store::ACTION_VIEW_MODE_TOP) {
                continue;
            }
            
            $actionInfo = $this->getActionInfoForListDataItem(
                $action, 
                $rowData
            );
            
            $lineKey = ($action['view'] == "list") ? 'action_lists' : 'actions';

            $listRow[$lineKey][] = $actionInfo;
        }
    
    } // end _appendActionsForListDataItem
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_LIST;
    } // end getActionName
    
    /**
     * Returns values for rows.
     * 
     * @return array
     */
    public function getValues()
    {
        return $this->storageValues;
    } // end getValues
}
