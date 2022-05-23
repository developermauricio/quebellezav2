<?php

require_once 'bundle/exception/StoreException.php';
require_once 'bundle/exception/StoreActionException.php';
require_once 'bundle/store/event/StoreActionEvent.php';
require_once 'bundle/store/event/StoreAggregationEvent.php';
require_once 'bundle/store/event/UrlStoreActionEvent.php';
require_once 'bundle/store/proxy/StoreProxy.php';
require_once 'bundle/store/model/StoreModel.php';
require_once 'bundle/store/view/StoreView.php';
require_once 'bundle/store/view/IActionView.php';
require_once 'bundle/store/view/DefaultActionView.php';
require_once 'bundle/store/view/api/AbstractActionApiView.php';
require_once 'bundle/store/field/AbstractField.php';
require_once 'bundle/store/action/AbstractAction.php';
require_once 'bundle/store/action/AbstractDisplayAction.php';
require_once 'bundle/store/StoreAudit.php';

/**
 * @package Festi
 * Class Store
 */
class Store extends Entity
{
    const ACTION_LIST     = "list";
    const ACTION_INSERT   = "insert";
    const ACTION_EDIT     = "edit";
    const ACTION_REMOVE   = "remove";
    const ACTION_PARENT   = "parent";
    const ACTION_CHILD    = "child";
    const ACTION_INFO     = "info";
    const ACTION_DOWNLOAD = "download";
    const ACTION_CONFIRM  = "confirm";
    const ACTION_CSV      = "csv";
    const ACTION_PLUGIN   = "plugin";
    const ACTION_CSV_IMPORT     = "csvImport";
    const ACTION_BATCH_INSERT   = "batchInsert";
    const ACTION_GRID_EDITOR    = "grideditor";
    const ACTION_RELATION       = "relation";
    const ACTION_FOREIGN_KEY_LOAD = "foreignKeyLoad";
    
    const ACTION_KEY_IN_REQUEST        = "action";
    const ACTION_PERFORM_KEY_IN_POST   = "performPost";
    const REQUEST_KEY_INSERT_TOKEN     = "__token";
    const ACTION_PERFORM_SAVE          = "save";
    
    const PRIMARY_KEY_IN_REQUEST       = "ID";
    const PAGE_INDEX_KEY_IN_REQUEST    = "pageID";
    const ROWS_PER_PAGE_KEY_IN_REQUEST = "pager";
    const ORDER_FIELD_NAME_IN_REQUEST  = "order";
    const ORDER_DIRECTION_IN_REQUEST   = "direction";
    const FIELD_KEY_IN_REQUEST         = "field";
    const THUMB_IMAGE_IN_REQUEST       = "thumb";

    const FILTERS_KEY_IN_SESSION      = "DB_FILTERS";
    const ORDER_FIELD_NAME_IN_SESSION = "order";
    const ORDER_DIRECTION_IN_SESSION  = "direction";

    const PARENT_PRIMARY_KEY_IN_SESSION = 'PARENT';
    const PARENT_ROW_VALUES_IN_SESSION  = 'PARENT_ROW';
    const PARENT_CAPTION_IN_SESSION     = 'PARENT_CAPTION';
    const RELATION_TYPE_CHILD           = 'child';
    const RELATION_TYPE_PARENT          = 'parent';

    const EVENT_PREPARE_ACTION = "db_prepare_action";
    const EVENT_PREPARE_ACTION_REQUEST = "store_prepare_action_request";
    const EVENT_PREPARE_ACTION_URL = "store_prepare_action_url";
    const EVENT_PREPARE_VALUES = "store_prepare_values";
    const EVENT_PREPARE_REPOSITORY_VALUES = "store_prepare_repository_values";
    const EVENT_INSERT = "store_insert";
    const EVENT_BEFORE_INSERT = "store_before_insert";
    const EVENT_UPDATE = "store_update";
    const EVENT_BEFORE_UPDATE = "store_before_update";
    const EVENT_REMOVE = "store_remove";
    const EVENT_BEFORE_REMOVE = "store_before_remove";
    const EVENT_ON_RESPONSE = "store_response";
    const EVENT_ACTION_ITEMS = "store_items";
    const EVENT_ON_LIST_ACTIONS = "store_list_actions";
    const EVENT_ON_LIST_GENERAL_ACTIONS = "store_list_general_actions";
    const EVENT_ON_FETCH_FORM = "fetch_form";
    const EVENT_STORE_INIT = "store_init";
    const EVENT_ON_FETCH_LIST = "fetch_list";
    const EVENT_ON_FETCH_LIST_CELL_VALUE_WITH_LINK = "value_with_link";
    const EVENT_ON_LOAD_LIST_DATA = "load_list_data";
    const EVENT_ON_FIELDS_PREFILL_VALUES = "prefill_fields_values";
    const EVENT_UPDATE_VALUES = "EVENT_UPDATE_VALUES";
    const EVENT_ON_LOAD_ACTION_ROWS = "load_action_rows";
    const EVENT_ON_LOAD_ACTION_VALUES = 'load_action_values';
    const EVENT_AFTER_UPDATE = "store_after_update";
    const EVENT_ON_REMOVE_INTEGRITY = "store_integrity";
    const EVENT_ON_FETCH_FIELD_FILTER = "store_fetch_field_filter";
    const EVENT_ON_AGGREGATIONS = "store_aggregation";
    const EVENT_ON_PROXY_PREPARE_FOREIGN_KEY_VALUES = "proxy_prepare_foreign_key_values";

    const PROXY_SQL = "sql";
    
    const CELL_VIEW_HANDLER_CUSTOM = "customView";
    
    const OPTION_MESSAGE_SUCCESS = "message_success";
    const OPTION_CURRENT_URL = "current_url";
    const OPTION_API_MODE = "storeApiMode";

    const ACTION_VIEW_MODE_DEFAULT  = "default";
    const ACTION_VIEW_MODE_RIGHT    = "right";
    const ACTION_VIEW_MODE_NEW      = "new";
    const ACTION_VIEW_MODE_TOP      = "top";

    /**
     * Current store action.
     * 
     * @var string
     */
    private $_action;
    
    /**
     * Current action.
     * 
     * @var AbstractAction
     */
    private $_actionInstance;
    
    /**
     * @var bool
     */
    private $_isForeignKeysLoaded;
    
    /**
     * Store options.
     * 
     * @var array
     */
    private $_options;
    
    /**
     * Store name.
     *
     * @var string
     */
    private $_name;
    
    /**
     * Model of storage.
     * 
     * @var StoreModel
     */
    protected $model;
    
    /**
     * Storage identifier. 
     * 
     * @var string
     */
    protected $ident;
    
    /**
     * Session storage.
     * 
     * @var mixed
     */
    protected $session;
    
    /**
     * @var mixed
     */
    protected $connection;
    
    /**
     * @var StoreView
     */
    protected $view;
    
    /**
     * @var mixed
     */
    protected $proxy;
    
    /**
     * @var int
     */
    protected $rowsPerPage;
    
    /**
     * @var int
     */
    protected $currentPage;
    
    /**
     * @var string
     */
    protected $parentFieldName;
    
    /**
     * @var string|null
     */
    protected $orderByFieldName;
    
    /**
     * @var string|null
     */
    protected $orderByDirection;
    
    /**
     * @var int
     */
    protected $totalRows;
    
    /**
     * Parameters in request for current store.
     * 
     * @var array
     */
    protected $request = array();
    
    /**
     * @var bool|mixed
     */
    protected $plugin;
    
    /**
     * @var string
     */
    private $_publicSslKey;
    /**
     * @var string
     */
    private $_privateSslKey;
    
    /**
     * Store constructor.
     * @param $connection
     * @param $ident
     * @param $options
     * @throws PermissionsException
     * @throws StoreException
     * @throws SystemException
     */
    public function __construct(&$connection, $ident, &$options)
    {
        $this->_options   = &$options;
        $this->ident      = $ident;
        $this->session    = &$this->_options['session_data'];
        $this->model      = $this->_loadModel();
        $this->connection = $this->_getConnectionInstance($connection);
        $this->view       = $this->_getViewInstance();
        $this->proxy      = $this->_getProxyInstance();
        $this->plugin     = $this->_getPluginInstance();
        $this->request    = $this->_getRequestValues();
        
        if (!$this->model) {
            throw new SystemException("Not found store model");
        }

        $this->_name = $this->getStoreName();

        if ($this->model->get('auditMode')) {
            $auditInstance = new core\dgs\StoreAudit();
            $auditInstance->apply($this);
        }
        
        Core::getInstance()->fireEvent(
            Store::EVENT_STORE_INIT,
            $this
        );

        $this->_doCheckUserPermission();
    } // end __construct
    
    /**
     * @return string
     */
    public function getDefaultErrorMessage(): string
    {
        $msg = $this->getModel()->get(
            StoreModel::TABLE_ATTRIBUTE_ERROR_MESSAGE
        );

        if (!$msg) {
            $msg = __l(
                'Something went wrong. Please, contact our free support.'
            );
        }

        return $msg;
    } // end getDefaultErrorMessage
    
    /**
     * @return bool
     * @throws PermissionsException
     * @throws SystemException
     */
    private function _doCheckUserPermission(): bool
    {
        $section = $this->getModel()->get(
            StoreModel::TABLE_ATTRIBUTE_PERMISSION
        );

        if (!$section) {
            return true;
        }

        $systemPlugin = Core::getInstance()->getSystemPlugin();

        if (!$systemPlugin->hasUserPermissionToSection($section)) {
            $exp = new PermissionsException();
            $exp->setDisplayMessage($this->getDefaultErrorMessage());
            $exp->setSource($this);
            throw $exp;
        }

        return true;
    } // end _doCheckUserPermission
    
    /**
     * @return array|bool|mixed
     */
    private function _getRequestValues()
    {
        $request = $this->getOption('request');
        if ($request) {
            return $request;
        }

        $request = array();
        if (array_key_exists($this->ident, $_REQUEST)) {
            $request = $_REQUEST[$this->ident];
        }

        if (array_key_exists($this->ident, $_GET)) {
            $request += $_GET[$this->ident];
        }

        return $request;           
    } // end getRequestValues
    
    /**
     * @return AbstractPlugin|null
     * @throws SystemException
     */
    private function _getPluginInstance(): ?AbstractPlugin
    {
        $pluginName = $this->getModel()->get('plugin');
        
        if (is_object($pluginName)) {
            return $pluginName;
        }
        
        if (!$pluginName) {
            return null;
        }
        
        return Core::getInstance()->getPluginInstance($pluginName);
    }// end _getPluginInstance
    
    /**
     * @param $options
     * @return mixed
     * @throws SystemException
     */
    public function fireForeignStoreCallback(array $options)
    {
        $plugin = Core::getInstance()->getPluginInstance(
            $options['plugin']
        );
        
        return $plugin->{$options['method']}($options, $this);
    } // end fireForeignStoreCallback

    public function getParamValueFromArray($key, &$request)
    {
        $storageName = $this->getIdent();
        
        if (
            array_key_exists($storageName, $request) 
            && array_key_exists($key, $request[$storageName])
        ) {
            return  $request[$storageName][$key];
        }
    
        return false;
    } // end getParamValueFromArray

    public function getRequestParam($key, &$request = false)
    {
        if ($request) {
            return $this->getParamValueFromArray($key, $request);
        }
        
        if (array_key_exists($key, $this->request)) {
            return $this->request[$key];
        }
    
        return false;
    } // end getRequestParam
    
    /**
     * @param $request
     */
    public function setRequest(&$request)
    {
        $this->request = $request;
    } // end setRequest

    /**
     * @param string $key
     * @param $value
     */
    public function setRequestParam(string $key, $value): void
    {
        $this->request[$key] = $value;
    } // end setRequestParam

    /**
     * @param $key
     * @return string
     */
    public function createRequestKey($key): string
    {
        return $this->getIdent().'['.$key.']';
    } // end createRequestKey
    
    
    /**
     * @param $key
     * @return bool
     */
    public function getPostParam($key)
    {
        if (!array_key_exists($this->getIdent(), $_POST)) {
            return false;
        }
        
        if (array_key_exists($key, $_POST[$this->getIdent()])) {
            return $_POST[$this->getIdent()][$key];
        }
        
        return false;
    } // end getPostParam
    
    /**
     * @param $amount
     */
    public function setRowsPerPageCount($amount)
    {
        $this->rowsPerPage = $amount;
    }

    public function getRowsPerPageCount()
    {
        if ($this->rowsPerPage) {
            return $this->rowsPerPage;
        }
        
        if (!empty($this->request[self::ROWS_PER_PAGE_KEY_IN_REQUEST])) {
            $rowsPerPage = $this->request[self::ROWS_PER_PAGE_KEY_IN_REQUEST];
            
            if (!is_numeric($rowsPerPage)) {
                $rowsPerPage = 1000;
            }
            
            $this->session['DB_PAGER'][$this->ident] = $rowsPerPage;
        } else if (!empty($this->session['DB_PAGER'][$this->ident])) {
            $rowsPerPage = $this->session['DB_PAGER'][$this->ident];
        } else {
            $rowsPerPage = $this->model->get('rowsForPage');
        }
        
        $this->rowsPerPage = $rowsPerPage;
        
        return $this->rowsPerPage;
    } // end getRowsPerPageCount

    public function getOption($name)
    {
        return array_key_exists($name, $this->_options) ? 
                    $this->_options[$name] : false;
    }
    
    /**
     * @return string
     * @throws SystemException
     */
    public function getCurrentUrl(): string
    {
        $url = $this->getOption(Store::OPTION_CURRENT_URL);

        return Core::getInstance()->getUrl($url);
    }
    
    /**
     * @param $key
     * @param $value
     */
    public function setOption($key, $value)
    {
        $this->_options[$key] = $value;
    }
    
    /**
     * @return array
     */
    public function &getOptions(): array
    {
        return $this->_options;
    }

    public function getParentValue($childTableName = false)
    {
        if (!$childTableName) {
            $childTableName = $this->getIdent();
        }
        
        if (!$this->hasSessionValue(self::PARENT_PRIMARY_KEY_IN_SESSION)) {
            return false;
        }
        
        return $this->getSessionValue(self::PARENT_PRIMARY_KEY_IN_SESSION);
    } // end getParentValue
    
    public function getParentValues($storeIdent = false)
    {
        if (!$storeIdent) {
            $storeIdent = $this->getIdent();
        }

        if (!$this->hasSessionValue(self::PARENT_ROW_VALUES_IN_SESSION)) {
            return false;
        }

        return $this->getSessionValue(self::PARENT_ROW_VALUES_IN_SESSION);
    } // end getParentValues

    /**
     * Returns executed DGS action name.
     *
     * @return string|null
     */
    public function getAction() : ?string
    {
        return $this->_action;
    } // end getAction
    
    /**
     * @param $key
     * @param bool $ident
     * @return bool
     */
    public function hasSessionValue($key, $ident = false)
    {
        $ident = $ident ? $ident : $this->getIdent();
        
        return !empty($this->session['DB_'.$ident."_".$key]);
    } // end hasSessionValue
    
    
    /**
     * @param $key
     * @param bool $ident
     * @return bool
     */
    public function getSessionValue($key, $ident = false)
    {
        $ident = $ident ? $ident : $this->getIdent();

        $key = 'DB_'.$ident."_".$key;

        if (!isset($this->session[$key])) {
            return false;
        }
        
        return $this->session[$key];
    } // end getSessionValue
    
    /**
     * @param $key
     * @param $value
     * @param bool $ident
     */
    public function setSessionValue($key, $value, $ident = false)
    {
        $ident = $ident ? $ident : $this->getIdent();
        
        $key = 'DB_'.$ident."_".$key;

        $this->session[$key] = $value;
    } // end setSessionValue
    
    /**
     * @return mixed
     */
    public function &getSession()
    {
        return $this->session;
    } // end getSession
    
    public function getTotalCount()
    {
        return isset($this->totalRows) ? $this->totalRows : false;
    } // end getTotalCount
    
    /**
     * Processing request to store.
     * 
     * @param Response &$response
     * @return bool
     * @throws SystemException
     */
    public function onRequest(Response &$response): bool
    {
        $action = $this->getActionNameFromRequest();

        $this->_actionInstance = $this->createActionInstance($action);

        return $this->_actionInstance->onStart($response);
    } // end onRequest

    /**
     * Create DGS Action instance.
     *
     * @param $action
     * @return AbstractAction
     * @phan-return mixed
     * @throws SystemException
     */
    public function createActionInstance(string $action)
    {
        $this->_action = $action;
        
        $actionInfo = $this->model->getAction($action);

        if ($actionInfo && isset($actionInfo['relation'])) {
            $action = 'relation';
        }
        
        if ($actionInfo && isset($actionInfo['method'])) {
            $action = 'plugin';
        }
        
        $className = ucfirst($action)."Action";
        
        if (!class_exists($className)) {
            $classPath = __DIR__.DIRECTORY_SEPARATOR.'action'.DIRECTORY_SEPARATOR.$className.'.php';
            if (!include_once($classPath)) {
                throw new SystemException("Not found action class: ".$className);
            }
        }
        
        return new $className($this);
    } // end createActionInstance

    /**
     * Returns DGS action name from request.
     *
     * @return string
     * @throws SystemException
     */
    public function getActionNameFromRequest() : string
    {
        $action = $this->model->get('defaultAction');
        if (!$action) {
            $action = static::ACTION_LIST;
        }

        if (!empty($this->request[static::ACTION_KEY_IN_REQUEST])) {
            $action = $this->request[static::ACTION_KEY_IN_REQUEST];
        }

        if ($this->isApiMode()) {
            $action = $this->model->getActionByRequestMethod();
        }

        return $action;
    } // end getActionNameFromRequest

    public function getActionPerformFromPost()
    {
        return $this->getPostParam(self::ACTION_PERFORM_KEY_IN_POST);
    } // end getActionPerformFromPost
    
    /**
     * Returns store model.
     * 
     * @throws SystemException
     * @return StoreModel
     */
    private function _loadModel(): StoreModel
    {
        $modelType = $this->getOption('model');
        if (!$modelType) {
            $modelType = "Xml";
        } else if (is_array($modelType)) {
            $modelType = "Array";
        }
        
        $className = $modelType.'StoreModel';
        
        if (!class_exists($className)) {
            require_once 'bundle/store/model/'.$className.'.php';
        }
        
        $model = new $className($this);
        
        $model->load();
        
        return $model;
    } // end _loadModel
    
    /**
     * Returns view decorator.
     * 
     * @return StoreView
     */
    private function _getViewInstance()
    {
        $view = $this->getOption('store_view');
        if ($view) {
            return $view;
        }
        
        $view = new StoreView($this);
        
        return $view;
    } // end _createViewInstance

    /**
     * Returns data provider for DGS.
     *
     * @return IProxy
     */
    private function _getProxyInstance(): IProxy
    {
        $proxy = $this->model->get('proxy');
        
        if ($proxy == static::PROXY_SQL) {
            $proxy = $this->connection->getDatabaseType();
        }
    
        $className = ucfirst($proxy).'Proxy';
        
        if (!class_exists($className)) {
            require_once 'bundle/store/proxy/'.$className.'.php';
        }
        
        return new $className($this);
    } // end _getProxyInstance
    
    /**
     * Returns real store name.
     * 
     * @return string
     */
    public function getStoreName()
    {
        $name = $this->model->get('alias');
        if (!$name) {
            $name = $this->model->getName();
        }
        
        return $name;
    } // end getStoreName
    
    /**
     * Returns identifier of store.
     * 
     * @return string
     */
    public function getIdent(): string
    {
        return $this->ident;
    } //end getIdent
    
    /**
     * @param string $name
     */
    public function setIdent(string $name): void
    {
        $this->ident = $name;
    } // end setIdent
    
    /**
     * Returns store name.
     * 
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    } // end getName
    
    /**
     * @param $name
     */
    public function setName(string $name): void
    {
        $this->_name = $name;
    } // end setName
    
    /**
     * @return StoreModel
     */
    public function &getModel(): StoreModel
    {
        return $this->model;
    } // end getModel
    
    /**
     * @return StoreView
     */
    public function &getView(): StoreView
    {
        return $this->view;
    } // end getView
    
    /**
     * @return IProxy
     */
    public function &getProxy() : IProxy
    {
        return $this->proxy;
    } // end getProxy

    public function getPrimaryKey()
    {
        return $this->model->getPrimaryKey();
    }
    
    /**
     * @param IDataAccessObject $connection
     * @return IDataAccessObject
     * @throws StoreException
     * @throws SystemException
     */
    private function _getConnectionInstance(IDataAccessObject &$connection): IDataAccessObject
    {
        if ($this->_isCustomConnectionHandler()) {
            $method = $this->model->get('onBeforeConnection');
            $plugin = $this->_getPluginInstance();
            
            if (!method_exists($plugin, $method)) {
                $msg = __('Method "%s" was not found in module".', $method);
                throw new StoreException($msg);
            }
            
            $args = array(
                'connection' => &$connection,
                'store'      => &$this
            );
            
            call_user_func_array(array($plugin, $method), array_values($args));
        }

        return $connection;
    } // end _getConnectionInstance
    
    /**
     * @return IDataAccessObject
     */
    public function &getConnection(): IDataAccessObject
    {
        return $this->connection;
    } // end getConnection
    
    /**
     * @return bool
     */
    private function _isCustomConnectionHandler(): bool
    {
        return $this->model->get('onBeforeConnection') &&
               $this->model->get('plugin');
    } // end _isCustomConnectionHandler
    
    /**
     * Return field name for parent relation.
     * 
     * @return string
     */
    public function getParentFieldName()
    {
        if (!is_null($this->parentFieldName)) {
            return $this->parentFieldName;
        }
        
        $action = $this->model->getAction(self::ACTION_PARENT);
        $relations = $this->model->getRelation($action['type']);
        
        if (!$relations || !isset($relations[$action['relation']]['field'])) {
            return false;
        }
        
        $this->parentFieldName = $relations[$action['relation']]['field'];
        
        return $this->parentFieldName;
    } // end getParentFieldName
    
    /**
     * @param $index
     */
    public function setCurrentPageIndex($index)
    {
        $this->currentPage = $index;
    } // end setCurrentPageIndex
    
    /**
     * Returns current page index from request
     * 
     * @return integer
     */
    public function getCurrentPageIndex()
    {
        if (!is_null($this->currentPage)) {
            return $this->currentPage;
        }

        $pageIndex = 1;
        if (!empty($this->request[self::PAGE_INDEX_KEY_IN_REQUEST])) {
            $pageIndex = $this->request[self::PAGE_INDEX_KEY_IN_REQUEST];
        } else if (isset($_GET[self::PAGE_INDEX_KEY_IN_REQUEST])) {
            $pageIndex = $_GET[self::PAGE_INDEX_KEY_IN_REQUEST];
        }

        $pageIndex = intval($pageIndex);

        $this->currentPage = $pageIndex > 0 ? $pageIndex : 1;

        return $this->currentPage;
    } // end getCurrentPageIndex
    
    /**
     * @return bool|mixed
     */
    public function getOrderByFieldName()
    {
        if (!is_null($this->orderByFieldName)) {
            return $this->orderByFieldName;
        }
        
        $orderFieldName = $this->model->get('defaultOrderField');
        
        if (!empty($this->request[self::ORDER_FIELD_NAME_IN_REQUEST])) {
            $orderFieldName = $this->request[self::ORDER_FIELD_NAME_IN_REQUEST];
        } else if ($this->hasSessionValue(self::ORDER_FIELD_NAME_IN_SESSION)) {
            $orderFieldName = $this->getSessionValue(
                self::ORDER_FIELD_NAME_IN_SESSION
            );
        }
        
        $this->setSessionValue(
            self::ORDER_FIELD_NAME_IN_SESSION, 
            $orderFieldName
        );
        
        $this->orderByFieldName = $orderFieldName;
        
        return $this->orderByFieldName;
    } // end getOrderByFieldName
    
    public function getOrderByDirection()
    {
        if (!is_null($this->orderByDirection)) {
            return $this->orderByDirection;
        }
        
        $orderByDirection = $this->model->get('defaultOrderDirection');
        
        $directionKey = self::ORDER_DIRECTION_IN_REQUEST;
        if (!empty($this->request[$directionKey])) {
            $orderByDirection = $this->request[$directionKey];
        } else if ($this->hasSessionValue(self::ORDER_DIRECTION_IN_SESSION)) {
            $orderByDirection = $this->getSessionValue(
                self::ORDER_DIRECTION_IN_SESSION
            );
        }
        
        if (!in_array($orderByDirection, array('ASC', 'DESC'))) {
            $orderByDirection = 'ASC';
        }

        $this->setSessionValue(
            self::ORDER_DIRECTION_IN_SESSION, 
            $orderByDirection
        );
        
        $this->orderByDirection = $orderByDirection;
        
        return $this->orderByDirection;
    } // end getOrderByDirection
    
     /**
     * Returns filed filter value from session. If value not fount return NULL.
     * 
     * @param AbstractField $field
     * @return mixed
     */
    public function getFieldFilterValueInSession(AbstractField $field)
    {
        $filterName = $field->getFilterKey();
        $tableName = $this->getIdent();
        
        if (!$this->_hasFieldFilterCondition($tableName, $filterName)) {
            return null;
        }
        
        $filtersStorage = &$this->session[self::FILTERS_KEY_IN_SESSION];
        return $filtersStorage[$tableName][$filterName];
    } // end getFilterValueInSession
    
    /**
     * @param $tableName
     * @param $filterName
     * @return bool
     */
    private function _hasFieldFilterCondition(
        string $tableName, string $filterName
    ): bool
    {
        $filtersStorage = &$this->session[self::FILTERS_KEY_IN_SESSION];
        return isset($filtersStorage[$tableName][$filterName]) && 
               $filtersStorage[$tableName][$filterName] !== "";
    } // end _hasFieldFilterCondition
    
    /**
     * Returns array with current field filters conditions (values).
     * 
     * @return array
     */
    public function getFieldFiltersConditions()
    {
        $result = array();
        
        foreach ($this->model->getFields() as $field) {
            $type = $field->getFilterType();
            if (!$type) {
                continue;
            }
            
            $key = $field->getFilterKey();
            $value = $this->getFieldFilterValueInSession($field);
            
            if (is_null($value)) {
                continue;
            }
            
            $result[$key] = array(
                'value'         => $value,
                'display_value' => $field->getFormattedFilterValue($value),
                'caption'       => $field->getCaption(),
                'name'          => $field->getName()
            );
        }
        
        return $result;
    } // end getFieldFiltersConditions
    
    /**
     * @return string
     * @throws Exception
     */
    public function createInsertToken()
    {
        $token = bin2hex(random_bytes(32));
        $tokenData = array();
        
        $parent = $this->getParentValue();
        
        if ($parent) {
            $parentRelations = $this->model->getRelation(self::ACTION_PARENT);
            $parentAction = $this->model->getAction(self::ACTION_PARENT);
            $relation = $parentRelations[$parentAction['relation']];
            $tokenData[$relation['field']] = $parent;
        }

        $filters = $this->model->getFilters();
        if ($filters) {
            foreach ($filters as $field => $value) {
                if (!in_array($field, $tokenData)) {
                    $tokenData[$field] = $value;
                }
            }
        }

        $this->session['insert'][$token] = $tokenData;
        
        return $token;
    } // end createInsertToken
    
    /**
     * @return bool|mixed
     */
    public function getInsertTokenFromRequest()
    {
        return $this->getRequestParam(
            static::REQUEST_KEY_INSERT_TOKEN
        );
    } // end getInsertTokenFromRequest
    
    /**
     * @return bool
     */
    private function _hasPrimaryKeyValueInRequest(): bool
    {
        return isset($this->request[self::PRIMARY_KEY_IN_REQUEST]);
    } // end _hasIdInRequest
    
    /**
     * Returns value for primary key from request.
     * 
     * @return string|NULL
     */
    public function getPrimaryKeyValueFromRequest()
    {
        if (!$this->_hasPrimaryKeyValueInRequest()) {
            return null;
        }
        
        return $this->request[self::PRIMARY_KEY_IN_REQUEST];
    } // end getPrimaryKeyValueFromRequest
    
    /**
     * @return bool
     */
    public function loadForeignKeys(): bool
    {
        if ($this->_isForeignKeysLoaded) {
            return true;
        }

        $fields = $this->model->getFields();
        foreach ($fields as $key => &$field) {
            if (!$field->isForeignKey() || $field->get('ajaxParent')) {
                continue;
            }

            // TODO:
            if (!$field->isCustomLoadValues()) {
                assert($field instanceof ForeignKeyField);
                $this->proxy->loadForeignKeyValues($field);
            }

        }
        unset($field);

        $this->_isForeignKeysLoaded = true;
        
        return true;
    } // end loadForeignKeys
    
    /**
     * Returns loaded record from store by primary key value.
     * 
     * @param mixed $primaryKeyValue
     * @param boolean $isCheckPermission = true
     * @throws PermissionsException
     * @return array
     */
    public function loadRowByPrimaryKey(
        $primaryKeyValue, $isCheckPermission = true
    )
    {
        $res = $this->hasPermissionToLoadRowByPrimaryKey($primaryKeyValue);
        if ($isCheckPermission && !$res) {
            $msg = __l(
                "Permission denied to editing record with primary key value %s",
                $primaryKeyValue
            );
            
            throw new PermissionsException($msg);
        }

        return $this->proxy->loadRowByPrimaryKey($primaryKeyValue);
    } // end loadRowByPrimaryKey
    
    /**
     * Adding table rows primary key values for permission validation
     * 
     * @param array $primaryValues
     * @see Store::hasPermissionToTableRow()
     */
    public function addAllowedTableRow($primaryValues)
    {
        if (is_scalar($primaryValues)) {
            $primaryValues = array($primaryValues);
        }
        
        if (!isset($this->session['DB_ALLOWED_IDS'])) {
            $this->session['DB_ALLOWED_IDS'] = array();
        }
        
        $realTableName = $this->getIdent();
        if (!isset($this->session['DB_ALLOWED_IDS'][$realTableName])) {
            $this->session['DB_ALLOWED_IDS'][$realTableName] = array();
        }
        
        $currentValues = $this->session['DB_ALLOWED_IDS'][$realTableName];
        
        $this->session['DB_ALLOWED_IDS'][$realTableName] = array_merge(
            $primaryValues, 
            $currentValues
        );
    } // end addAllowedTableRow
    
    /**
     * Returns reference to plugin instance for current Store.
     * 
     * @return AbstractPlugin
     */
    public function &getPlugin() : ?AbstractPlugin
    {
        return $this->plugin;
    } // end getPlugin

    /**
     * Override plugin instance
     * @param AbstractPlugin $plugin
     */
    public function setPlugin(AbstractPlugin &$plugin)
    {
        $this->plugin = &$plugin;
    } // end setPlugin

    /**
     * Returns true if user has permission for loading or editing record in 
     * store.
     * 
     * @param mixed $primaryKeyValue
     * @return boolean
     */
    public function hasPermissionToLoadRowByPrimaryKey($primaryKeyValue)
    {
        if (!isset($this->session['DB_ALLOWED_IDS'])) {
            return false;
        }
        
        $storeIdent = $this->getIdent();

        if (!isset($this->session['DB_ALLOWED_IDS'][$storeIdent])) {
            return false;
        }
        
        return in_array(
            $primaryKeyValue, 
            $this->session['DB_ALLOWED_IDS'][$storeIdent]
        );
    } // end hasPermissionToLoadRowByPrimaryKey
    
    /**
     * @param array $search
     * @return mixed
     */
    public function search(array $search = array())
    {
        return $this->proxy->search($search);
    } // end search
    
    /**
     * @param array $search
     * @return mixed
     */
    public function loadRow(array $search = array()): ?array
    {
        return $this->proxy->loadRow($search);
    } // end loadRow
    
    public function begin()
    {
        $this->proxy->begin();
    }
    
    public function commit()
    {
        $this->proxy->commit();
    }
    
    public function isBegin()
    {
        return $this->proxy->isBegin();
    } // end isBegin
    
    public function rollback()
    {
        $this->proxy->rollback();
    }
    
    /**
     * @return Store
     */
    public function cloneInstance(): Store
    {
        return clone $this;
        /*
        $cloneStore = new self(
            $this->connection, 
            $this->getIdent(), 
            $this->_options
        );
        
        return $cloneStore;*/
    } // end cloneInstance
    
    /**
     * @param $primaryKeyValue
     * @return bool
     * @throws PermissionsException
     * @throws StoreException
     * @throws SystemException
     */
    public function removeChildByPrimaryKey($primaryKeyValue): bool
    {
        $childAction = $this->model->getAction(Store::ACTION_CHILD);
        if (!$childAction) {
            return false;
        }
        
        $relations = $this->model->getRelation('child');
        $relation = $relations[$childAction['relation']];
        
        $childStore = new self(
            $this->connection, 
            $relation['foreignTable'], 
            $this->_options
        );
        
        if (!isset($relation['cascade']) || is_null($relation['cascade'])) {
            return false;
        }
            
        $childModel = &$childStore->getModel();
            
        $parentRelation = $childModel->getRelation(self::ACTION_PARENT);
            
        $parentAction = $childModel->getAction(Store::ACTION_PARENT);
        $storeName = $parentAction['relation'];
        
        $storeRelation = $parentRelation[$storeName];
        
        $search = array(
            $storeRelation['field'] => $primaryKeyValue
        );
        
        $rows = $childStore->search($search);
        
        $childPrimaryKey = $childStore->getPrimaryKey();
        foreach ($rows as $row) {
            $childPrimaryKeyValue = $row[$childPrimaryKey];
            $childStore->removeByPrimaryKey($childPrimaryKeyValue);
        }
        
        return true;
    } // end removeChildByPrimaryKey
    
    /**
     * @param $primaryKeyValue
     * @return bool
     * @throws PermissionsException
     * @throws StoreException
     * @throws SystemException
     */
    public function removeByPrimaryKey($primaryKeyValue): bool
    {
        $this->removeChildByPrimaryKey($primaryKeyValue);
        
        $this->proxy->removeAllManyToManyValuesByPrimaryKey($primaryKeyValue);

        $this->proxy->removeByPrimaryKey($primaryKeyValue);
        
        return true;
    } // end remove
    
    /**
     * @param $isUseLimit
     */
    public function setUseLimit(bool $isUseLimit): void
    {
        $this->proxy->setUseLimit($isUseLimit);
    } // end setUseLimit
    
    /**
     * @param bool $isAllColumns
     * @return array
     */
    public function load(bool $isAllColumns = false): array
    {
        $values = $this->proxy->loadListValues($isAllColumns);
        
        $this->totalRows = $this->proxy->getCount();

        return $values;
    } // end load
    
    /**
     * Returns aggregated totals.
     *
     * @param $sourceData reference to source data
     * @return array|null
     */
    public function aggregate(&$sourceData): ?array
    {
        $aggregations = $this->model->getAggregations();

        if (!$aggregations) {
            return null;
        }

        $result = $this->proxy->loadAggregations();

        $fields = array();
        $externalAggregations = array();
        foreach ($aggregations as $data) {

            $field = $this->model->getField($data['field']);
            $fields[$data['field']] = $field;

            if ($data['type'] == 'custom') {
                $externalAggregations[] = $data;
            }
        }

        $event = new StoreAggregationEvent($result, $sourceData);
        $this->dispatchEvent($event);

        foreach ($result as $ident => &$value) {
            if (array_key_exists($ident, $fields)) {
                $value = $fields[$ident]->getFormattedValue($value);
            }
        }

        return $result;
    } // end aggregate
    
    /**
     * @param $primaryKeyValue
     * @param $values
     * @return mixed
     */
    public function updateByPrimaryKey($primaryKeyValue, $values)
    {
        return $this->proxy->updateByPrimaryKey($primaryKeyValue, $values);
    } // end updateByPrimaryKey
    
    /**
     * Returns default path to DGS file folder.
     *
     * @return string
     */
    public function getDefaultUploadFilePath(): string
    {
        return $this->getOption("system_path").'storages'.DIRECTORY_SEPARATOR.
               $this->getModel()->getName().DIRECTORY_SEPARATOR;
    } // end getDefaultUploadFilePath
    
    /**
     * @return bool
     */
    public function isExceptionMode()
    {
        $globalExceptionMode = $this->getOption(Core::OPTION_GLOBAL_STORE_EXCEPTION_MODE);
        if ($globalExceptionMode) {
            return true;
        }
        
        $exceptionMode = $this->getModel()->get('exceptionMode');
        
        return ($exceptionMode && $exceptionMode === 'true') || 
               $exceptionMode === true;
    } // end isExceptionMode

    public function isApiMode()
    {
        $globalExceptionMode = $this->getOption(static::OPTION_API_MODE);
        if ($globalExceptionMode) {
            return true;
        }

        return $this->model->isApiMode();
    } // end isApiMode
    
    /**
     * Returns last executed action instance.
     *
     * @return AbstractAction
     */
    public function getActionInstance(): AbstractAction
    {
        return $this->_actionInstance;
    } // end getActionInstance
    
    /**
     * Returns public part of SSL key.
     * 
     * @throws SystemException
     * @return string
     */
    public function getPublicSslKey()
    {
        if (!empty($this->_publicSslKey)) {
            return $this->_publicSslKey;
        }
        
        $path = $this->getOption(Core::OPTION_SSL_PUBLIC_KEY);
        
        if (empty($path) || !file_exists($path)) {
            throw new SystemException("Not found public key");
        }
        
        $key = file_get_contents($path);
        
        $this->_publicSslKey = openssl_get_publickey($key);
        
        return $this->_publicSslKey;
    } // end getPublicSslKey
    
    /**
     * Returns private part of SSL key.
     *
     * @throws SystemException
     * @return string
     */
    public function getPrivateSslKey()
    {
        if (!empty($this->_privateSslKey)) {
            return $this->_privateSslKey;
        }

        $path = $this->getOption(Core::OPTION_SSL_PRIVATE_KEY);
        
        if (empty($path) || !file_exists($path)) {
            throw new SystemException("Not found private key");
        }
        
        $key = file_get_contents($path);
        
        $this->_privateSslKey = openssl_get_privatekey($key);

        return $this->_privateSslKey;
    } // end getPublicSslKey
    
    /**
     * @param AbstractField $field
     * @return mixed
     */
    public function loadForeignKeyValues(AbstractField &$field)
    {
        assert($field instanceof ForeignKeyField);
        return $this->proxy->loadForeignKeyValues($field);
    } // end loadForeignKeyValues
}
