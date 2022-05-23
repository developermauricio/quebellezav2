<?php

require_once 'bundle/store/model/FieldModel.php';

/**
 * Class StoreModel
 */
class StoreModel extends Entity
{
    const TABLE_ATTRIBUTE_ERROR_MESSAGE = "errorMessage";
    const TABLE_ATTRIBUTE_PERMISSION = "permission";
    
    const ACTION_ATTRIBUTE_REDIRECT_URL = "redirectUrl";

    const OPTION_ACTIONS_MODE = 'actions.mode';

    const OPTION_ACTIONS_MODE_LIST = "list";
    const OPTION_ACTIONS_MODE_BUTTONS = "buttons";

    const OPTION_FILTERS_MODE = 'filters.mode';

    const OPTION_FILTERS_MODE_AJAX = 'ajax';
    const OPTION_FILTERS_MODE_TOP = 'top';
    const OPTION_FILTERS_MODE_RIGHT = 'right';
    const OPTION_FILTERS_MODE_DEFAULT = 'default';

    const OPTION_SECTIONS_MODE = 'sections.mode';

    const OPTION_SECTIONS_MODE_TABS = 'tabs';
    const OPTION_SECTIONS_MODE_WIDGETS = 'widgets';

    const MODE_WEB = "web";
    const MODE_API = "api";
    
    /**
     * Store attributes.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Store fileds.
     *
     * @var array
     */
    protected $fields;

    /**
     * Store actions.
     *
     * @var array
     */
    protected $actions;

    /**
     * Store relations.
     *
     * @var array
     */
    protected $relations;

    /**
     * List of group actions.
     *
     * @var array
     */
    protected $grouped;

    /**
     * Condition filters.
     *
     * @var array
     */
    protected $filters;

    /**
     * Store sections.
     *
     * @var array
     */
    protected $sections;
    
    /**
     * @var array
     */
    protected $externalValues = array();
    
    /**
     * @var array
     */
    protected $routers;
    
    /**
     * @var array
     */
    protected $search = array();
    
    /**
     * @var array
     */
    protected $listeners = array();
    
    /**
     * @var array
     */
    protected $highlights = array();

    /**
     * List of a condition for a total.
     *
     * @var array
     */
    protected $aggregations = array();

    /**
     * Store name.
     *
     * @var string
     */
    private $_name;

    /**
     * Primary key name.
     *
     * @var string
     */
    protected $primaryKey;

    /**
     * Reference to store.
     *
     * @var Store
     */
    protected $store;

    /**
     * DGS configuration options.
     *
     * @var array
     */
    protected $options;
    
    /**
     * @var string
     */
    protected $stream = "php://input";
    
    /**
     * StoreModel constructor.
     * @param Store $store
     */
    public function __construct(Store &$store)
    {
        $this->store = &$store;
        $this->fields = array();

    } // end __construct

    /**
     * Returns name of table in database.
     * Table name in system not always the same real name of table in database.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    } // end getName
    
    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    } // end setName

    /**
     * Returns attribute value.
     *
     * @param string $name
     * @return bool|mixed
     */
    public function get(string $name)
    {
        $value = false;
        if (array_key_exists($name, $this->attributes)) {
            $value = $this->attributes[$name];
            if ($value === "false") {
                $value = false;
            }
        }

        return $value;
    } // end get

    /**
     * Set attribute value.
     *
     * @param string $key
     * @param $value
     */
    public function set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    } // end set
    
    /**
     * @param $actionName
     * @return bool
     */
    public function hasAction($actionName)
    {
        return isset($this->actions[$actionName]);
    }
    
    /**
     * @param $actionName
     * @return bool|mixed
     */
    public function getAction($actionName)
    {
        if (!$this->hasAction($actionName)) {
            return false;
        }

        return $this->actions[$actionName];
    } // end getAction
    
    /**
     * @param $actionName
     * @return bool|mixed
     */
    public function &getActionByRef($actionName)
    {
        if (!$this->hasAction($actionName)) {
            $res = false;
            return $res;
        }

        return $this->actions[$actionName];
    } // end getAction
    
    /**
     * @param $actionName
     */
    public function removeAction($actionName)
    {
        unset($this->actions[$actionName]);
    } // end removeAction
    
    /**
     * @return array
     */
    public function &getActions()
    {
        return $this->actions;
    } // end getActions
    
    /**
     * @return array
     */
    public function &getHighlights()
    {
        return $this->highlights;
    } // end getHighlights
    
    /**
     * @param $name
     * @return bool|mixed
     */
    public function &getRelation($name)
    {
        if (!isset($this->relations[$name])) {
            $res = false;
            return $res;
        }

        return $this->relations[$name];
    } // end getRelation
    
    /**
     * @return AbstractField[]
     */
    public function &getFields()
    {
        return $this->fields;
    }
    
    /**
     * @return array
     */
    public function &getSections()
    {
        return $this->sections;
    }

    /**
     * Returns reference to field by name.
     *
     * @param $name field name
     * @return AbstractField|null
     */
    public function &getFieldByName(string $name) : ?AbstractField
    {
        if (!array_key_exists($name, $this->fields) || !$this->fields[$name]) {
            // XXX: Cheat for "Only variable references should be returned by reference"
            $ref = null;
            return $ref;
        }

        return $this->fields[$name];
    } // end getFieldByName

    /**
     * Returns reference to field by option.
     *
     * @param string $optionName
     * @param $optionValue
     * @return AbstractField|null
     */
    public function &getFieldByOption(string $optionName, $optionValue) : ?AbstractField
    {
        $result = null;
        foreach ($this->fields as &$field) {
            $value = $field->get($optionName);
            if ($optionValue == $value) {
                $result = &$field;
                break;
            }
        }

        return $result;
    } // end getFieldByOption

    /**
     * Remove filed from DGS.
     *
     * @param string $name
     * @return bool
     */
    public function removeFieldByName(string $name): bool
    {
        if (!array_key_exists($name, $this->fields)) {
            return false;
        }

        unset($this->fields[$name]);
        return true;
    } // end removeFieldByName

    /**
     * Returns field
     *
     * @param string $name
     * @return AbstractField|bool
     */
    public function getField($name)
    {
        return isset($this->fields[$name]) ? $this->fields[$name] : false;
    } // end getField
    
    
    /**
     * @return string
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    
    /**
     * @return array
     */
    public function getGroupActions()
    {
        return $this->grouped;
    }
    
    /**
     * @throws SystemException
     */
    public function load()
    {
        throw new SystemException("Undefined load method");
    } // end load
    
    /**
     * @return array
     */
    protected function getAttributesOptions()
    {
        $attributesFields = array(
           'name' => static::FIELD_TYPE_STRING_NULL,

            'primaryKey' => array(
                'type' => static::FIELD_TYPE_STRING_NULL,
                'required' => 1
            ),

            'rowsForPage' => array(
                'type' => static::FIELD_TYPE_STRING_NULL,
                'default' => 50
            ),

            'paging' => array(
                'type' => static::FIELD_TYPE_STRING_NULL,
                'default' => 'normal'
            ),

            'proxy' => array(
                'type' => static::FIELD_TYPE_STRING_NULL,
                'default' => 'sql'
            ),

            'mode' => array(
                'type' => static::FIELD_TYPE_STRING_NULL,
                'default' => static::MODE_WEB
            ),

            'filter' => array(
                'type' => static::FIELD_TYPE_STRING_NULL,
                'default' => static::OPTION_FILTERS_MODE_DEFAULT
            ),

           'charset'               => static::FIELD_TYPE_STRING_NULL,
           'defaultOrderField'     => static::FIELD_TYPE_STRING_NULL,
           'defaultOrderDirection' => static::FIELD_TYPE_STRING_NULL,
           'plugin'                => static::FIELD_TYPE_STRING_NULL,
           'emptyMessage'          => static::FIELD_TYPE_STRING_NULL,
           'fastAdd'               => static::FIELD_TYPE_STRING_NULL,
           'join'                  => static::FIELD_TYPE_STRING_NULL,
           'additionalWhere'       => static::FIELD_TYPE_STRING_NULL,
           'grideditor'            => static::FIELD_TYPE_STRING_NULL,
           'exceptionMode'         => static::FIELD_TYPE_STRING_NULL,
           'permission'            => static::FIELD_TYPE_STRING_NULL,

           static::TABLE_ATTRIBUTE_ERROR_MESSAGE => 
                static::FIELD_TYPE_STRING_NULL,
           static::TABLE_ATTRIBUTE_PERMISSION => static::FIELD_TYPE_STRING_NULL,
        );

        return $attributesFields;
    } // end getAttributesFields

    /**
     * Returns field instance created by field information
     *
     * @param array $info
     * @return AbstractField
     */
    protected function createFieldInstance($info)
    {
        $className = ucfirst($info['type'])."Field";

        if (!class_exists($className)) {
            require_once 'bundle/store/field/'.$className.'.php';
        }

        return new $className($this->store);
    } // end createFieldInstance
    
    /**
     * @throws SystemException
     */
    protected function doPrepareActions()
    {
        $attributes = $this->getActionAttributesOptions();

        foreach ($this->actions as $key => &$action) {
            $action = $this->getExtendData($action, $attributes, $errors);
            if ($errors) {
                list($atrributeName, $message) = each($errors);
                throw new SystemException($message);
            }

            if ($action['permission']) {
                if (!$this->_hasUserActionPermission($action['permission'])) {
                    unset($this->actions[$key]);
                }
            }
        }
        unset($action);
    } // end doPrepareActions
    
    /**
     * @param $section
     * @return mixed
     * @throws SystemException
     */
    private function _hasUserActionPermission($section)
    {
        $systemPlugin = Core::getInstance()->getSystemPlugin();

        return $systemPlugin->hasUserPermissionToSection($section);
    }

    /**
     * Returns list of all known attributes for action
     *
     * @return array
     */
    protected function getActionAttributesOptions()
    {
        $attributes = array(
            'type' => array(
                'type'     => PARAM_STRING,
                'error'    => 'Undefined type in action',
                'required' => true
            ),
            'mode' => array(
                'type'    => PARAM_STRING,
                'default' => Store::ACTION_VIEW_MODE_DEFAULT
            ),
            'confirmDialog' => PARAM_STRING_NULL,
            'link'          => PARAM_STRING_NULL,
            'src'           => PARAM_STRING_NULL,
            'caption'       => PARAM_STRING_NULL,
            'js'            => PARAM_STRING_NULL,
            'view'          => PARAM_STRING_NULL,
            'addon'         => PARAM_STRING_NULL,
            'html'          => PARAM_STRING_NULL,
            'permission'    => static::FIELD_TYPE_STRING_NULL,
        );

        return $attributes;
    } // end getActionAttributesOptions

    /**
     * Returns list of all known attributes for group action
     *
     * @return array
     */
    protected function getGroupActionFields()
    {
        $attributes = array(
            'caption' => array(
                'type'     => PARAM_STRING,
                'error'    => 'Undefined caption in group action',
                'required' => true
            ),
            'type' => array(
                'type'     => PARAM_STRING,
                'error'    => 'Undefined type in group action',
                'required' => true
            ),
            'link'    => PARAM_STRING_NULL,
            'js'      => PARAM_STRING_NULL
        );

        return $attributes;
    } // end getGroupActionFields

    /**
     * Returns list of all known attributes for actions
     *
     * @return array
     */
    protected function getActionsAttributesOptions()
    {
        $attributes = array(
            'mode' => array(
                'type'    => PARAM_STRING,
                'default' => static::OPTION_ACTIONS_MODE_LIST
            )
        );

        return $attributes;
    } // end getActionsAttributesOptions

    /**
     * Returns list of all known attributes for sections
     *
     * @return array
     */
    protected function getSectionsAttributesOptions()
    {
        $attributes = array(
            'mode' => array(
                'type'    => PARAM_STRING,
                'default' => static::OPTION_SECTIONS_MODE_WIDGETS
            )
        );

        return $attributes;
    } // end getSectionsAttributesOptions
    
    /**
     * @return array
     */
    public function &getFilters()
    {
        return $this->filters;
    } // end getFilters
    
    /**
     * @return mixed
     */
    public function &getRouters()
    {
        return $this->routers;
    } // end getRouters
    
    /**
     * @return array
     */
    public function &getSearch(): array
    {
        return $this->search;
    } // end getSearch
    
    /**
     * @return array
     */
    public function &getExternalValues()
    {
        return $this->externalValues;
    } // end getExternalValue
    
    /**
     * @return bool
     */
    public function isApiMode(): bool
    {
        return $this->get('mode') == StoreModel::MODE_API;
    } // end isApiMode
    
    /**
     * @return bool
     */
    public function isWebMode(): bool
    {
        return $this->get('mode') == StoreModel::MODE_WEB;
    } // end isApiMode

    /**
     * Returns DGS action name from REQUEST_METHOD.
     *
     * @return string
     * @throws StoreException
     */
    public function getActionByRequestMethod(): string
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        switch ($requestMethod) {
            case 'GET':
                $action = $this->_getActionByRequestMethodGet();
                break;

            case 'POST':
                $action = $this->_getActionByRequestMethodPost();
                break;

            case 'PUT':
                $action = Store::ACTION_EDIT;
                break;

            case 'DELETE':
                $action = Store::ACTION_REMOVE;
                break;

            default:
                $action = Store::ACTION_LIST;
        }

        $actions = $this->getActions();

        if (!array_key_exists($action, $actions)) {
            throw new StoreException(__('The action "%s" is undefined in DGS model', $requestMethod));
        }

        return $action;
    } // end getActionByRequestMethod
    
    /**
     * @return string
     */
    private function _getActionByRequestMethodGet(): string
    {
        $action     = Store::ACTION_LIST;
        $primaryKey = $this->store->getPrimaryKeyValueFromRequest();

        if ($primaryKey) {
            $action = Store::ACTION_INFO;
        }

        return $action;
    } // end _getActionByRequestMethodGet
    
    /**
     * @return string
     */
    private function _getActionByRequestMethodPost(): string
    {
        $action = Store::ACTION_INSERT;

        $request = $this->getRequest();

        if (is_array($request) && is_array(array_pop($request))) {
            $action = Store::ACTION_BATCH_INSERT;
        }

        return  $action;
    } // end _getActionByRequestMethodPost
    
    /**
     * @return array|null
     */
    public function getRequest(): ?array
    {
        $request = $_POST;

        if (!$request) {
            $request = $_REQUEST;
        }

        if ($this->isApiMode()) {
            $stream = $this->getStream();

            $request = file_get_contents($stream);

            if (!$request) {
                return null;
            }
            
            $request = json_decode($request, true);
        }

        return $request;
    } // end getRequest
    
    /**
     * @return string
     */
    public function &getStream()
    {
        return $this->stream;
    } // getStream

    /**
     * Returns reference to aggregations conditions.
     *
     * @return array &
     */
    public function &getAggregations()
    {
        return $this->aggregations;
    } // end getAggregations

    /**
     * Returns model option value or NULL if undefined option.
     *
     * @param $key
     * @return mixed|null
     */
    public function getOption($key)
    {
        if (!array_key_exists($key, $this->options)) {
            return null;
        }

        return $this->options[$key];
    } // end getOption

    /**
     * Returns reference to DGS configuration options.
     *
     * @return array &
     */
    public function &getOptions()
    {
        return $this->options;
    }
    
    /**
     * @param $section
     * @param $options
     * @param $attributesOptions
     * @return bool
     */
    protected function loadSectionOptions(
        $section, $options, $attributesOptions
    )
    {
        if (empty($options) && !is_array($options)) {
            $options = array();
        }

        $preparedOptions = $this->getExtendData(
            $options,
            $attributesOptions,
            $errors
        );

        foreach ($preparedOptions as $key => $value) {
            $this->options[$section.'.'.$key] = $value;
        }

        return true;
    } // end loadSectionOptions
}
