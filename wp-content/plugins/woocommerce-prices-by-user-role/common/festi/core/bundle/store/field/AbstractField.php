<?php

/**
 * Class AbstractField
 */
class AbstractField extends Display
{
    const OPTION_AUTOCOMPLETE            = "autocomplete";
    const OPTION_AUTOCOMPLETE_MIN_LENGTH = "autocompleteMinLength";
    const OPTION_AUTOCOMPLETE_LIMIT      = "autocompleteLimit";
    const OPTION_CONTAINER_CSS           = "containerCss";
    const OPTION_ONLY_LIST               = "onlyList";
    const OPTION_STORE                   = "store";
    const OPTION_FILTER                  = "filter";
    const OPTION_EXPRESSION              = "expression";
    const OPTION_WHERE                   = "where";

    const FILTER_TYPE_RANGE    = "range";
    const FILTER_TYPE_SELECT   = "select";
    const FILTER_TYPE_MULTIPLE = "multiple";
    const FILTER_TYPE_EXACT    = "exact";

    /**
     * Reference to store.
     *
     * @var Store
     */
    protected $store;
    
    /**
     * Reference to current action.
     * 
     * @var AbstractAction
     */
    protected $action;

    /**
     * Attributes of field defined in xml
     *
     * @var array
     */
    protected $attributes;
    
    /**
     * @var string
     */
    private $_name;
    
    private $_selectorPostfix;
    
    protected $index;
    
    protected $itemValue;
    
    protected $filterValues = null;
    
    public $lastErrorMessage = false;
    
    /**
     * AbstractField constructor.
     * @param Store $store
     * @throws SystemException
     */
    public function __construct(Store &$store)
    {
        $this->store = &$store;

        $path = $this->getTemplatePath();

        parent::__construct($path);
    } // end __construct

    /**
     * Set reference to current DGS action.
     * 
     * @param AbstractAction $action
     */
    public function setAction(AbstractAction &$action)
    {
        $this->action = &$action;
    } // end setAction
    
    /**
     * Returns reference to current DGS action.
     * 
     * @return AbstractAction
     */
    public function &getAction(): AbstractAction
    {
        return $this->action;
    } // end getAction
    
    /**
     * @return Store
     */
    public function &getStore(): Store
    {
        return $this->store;
    } // end getStore
    
    /**
     * Returns path to template folders.
     *
     * @return string
     * @throws SystemException
     */
    protected function getTemplatePath(): string
    {
        return $this->getBaseTemplatePath();
    } // end getTemplatePath

    /**
     * Returns path to base template folders.
     *
     * @return string
     * @throws SystemException
     */
    protected function getBaseTemplatePath(): string
    {
        return Core::getInstance()->getOption(Core::OPTION_ENGINE_PATH).
            "templates".DIRECTORY_SEPARATOR."fields".DIRECTORY_SEPARATOR;
    } // end getBaseTemplatePath
    
    /**
     * Returns encrypted value.
     * @param $value
     * @return string
     * @throws SystemException
     */
    public function getEncryptValue($value): string
    {
        $publicKey = $this->store->getPublicSslKey();
        
        $cryptedValue = null;
        openssl_public_encrypt(
            $value,
            $cryptedValue,
            $publicKey
        );
        
        $value = base64_encode($cryptedValue);
        
        return $value;
    } // end getEncryptValue
    
    /**
     * Returns decrypted value.
     *
     * @param string $value
     * @return string
     * @throws SystemException
     */
    public function getDecryptedValue(string $value): ?string
    {
        $privateKey = $this->store->getPrivateSslKey();

        $cryptedValue = base64_decode($value);

        $decryptedValue = null;
        $res = openssl_private_decrypt(
            $cryptedValue,
            $decryptedValue,
            $privateKey
        );

        if ($res === false) {
            throw new FieldException(openssl_error_string());
        }

        return $decryptedValue;
    } // end getDecryptedValue
    
    /**
     * @param $value
     * @return string
     * @throws SystemException
     */
    protected function getViewValue($value)
    {
        if ($value && $this->get('crypt')) {
            $value = $this->getDecryptedValue($value);
        }
        
        return $value;
    } // end getViewValue

    /**
     * Init field attributes
     *
     * @param FieldModel $scheme
     * @throws SystemException
     */
    public function onInit(FieldModel $scheme)
    {
        $fields = $this->getAttributesFields();

        $attributes = $scheme->getAttributes();
        $this->attributes = $this->getExtendData($attributes, $fields, $errors);

        if ($errors) {
            $message = array_shift($errors);
            throw new SystemException($message);
        }

        $this->_name = $this->get('name');

        if (StoreProxy::isComplexField($this->_name)) {
            list($aggregateExpression, $fieldName) = array_map('trim', explode("as", $this->_name));
            $this->_name = $fieldName;
            $this->set(static::OPTION_EXPRESSION, $aggregateExpression);
        }

        $this->_selectorPostfix = str_replace(
            array("[", "]"), 
            array("-", ""), 
            $this->_name
        );
    } // end onInit

    /**
     * Returns list of attributes for field.
     *
     * @return array
     */
    protected function getAttributesFields(): array
    {
        $fields = array(
            'caption' => array(
                'type'     => static::FIELD_TYPE_STRING,
                'error'    => 'Undefined caption in field',
                'required' => true
            ),
            'type' => array(
                'type'     => static::FIELD_TYPE_STRING,
                'error'    => 'Undefined type in field',
                'required' => true
            ),
            'name' => array(
                'type'     => static::FIELD_TYPE_STRING,
                'error'    => 'Undefined name in field',
                'required' => true
            ),

            'link'       => static::FIELD_TYPE_STRING_NULL,
            'clicable'   => static::FIELD_TYPE_STRING_NULL,
            'width'      => static::FIELD_TYPE_STRING_NULL,
            'filter'     => static::FIELD_TYPE_STRING_NULL,
            'sorting'    => static::FIELD_TYPE_STRING_NULL,
            'table'      => static::FIELD_TYPE_STRING_NULL,
            'required'   => static::FIELD_TYPE_STRING_NULL,
            'format'     => static::FIELD_TYPE_STRING_NULL,
            'hide'       => static::FIELD_TYPE_STRING_NULL,
            'allowEmpty' => static::FIELD_TYPE_BOOL,
            static::OPTION_ONLY_LIST   => static::FIELD_TYPE_BOOL,
            static::OPTION_CONTAINER_CSS => static::FIELD_TYPE_STRING_NULL,
            'onValidateValue' => static::FIELD_TYPE_STRING_NULL,

            static::OPTION_AUTOCOMPLETE => static::FIELD_TYPE_STRING_NULL,
            static::OPTION_AUTOCOMPLETE_MIN_LENGTH => array(
                'type'    => static::FIELD_TYPE_INT,
                'default' => 2
            ),
            static::OPTION_AUTOCOMPLETE_LIMIT => array(
                'type'    => static::FIELD_TYPE_INT,
                'default' => 10
            )
        );

        return $fields;
    } // end getAttributesFields

    /**
     * Returns attribute value or false if that not found.
     *
     * @param string $name
     * @return bool|string
     */
    public function get($name)
    {
        $result = false;
        if (array_key_exists($name, $this->attributes)) {
            $value = $this->attributes[$name];
            if ($value === "true") {
                $result = true;
            } else if ($value !== "false") {
                $result = $value;
            }
        }

        return $result;
    } // end get
    
    /**
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
    } // end set
    
    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    } // end getAttributes
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    } // end getName
    
    public function getSelectorPostfix()
    {
        return $this->_selectorPostfix;
    }
    
    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    } // end getName
    
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->get('type');
    } // end getType
    
    public function setIndex($index)
    {
        $this->index = $index;
    } // end setIndex
    
    public function getIndex()
    {
        return $this->index;
    } // end getIndex
    
    // FIXME:
    /**
     * @param $value
     * @return string
     */
    public function getFilter($value): string
    {
        return "= '".$value."'";
    }
    
    /**
     * Returns edit html input for field.
     *
     * @param string|null $value
     * @param array|null $inline
     * @return string|null
     * @throws SystemException
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $value = $this->getViewValue($value);

        $this->value = htmlspecialchars($value);

        return $this->fetch('edit.phtml');
    } // end getEditInput

    /**
     * Returns true if field must be ignored in update table logic.
     *
     * @param $actionName DGS Action Name
     * @return boolean
     */
    public function isVirtualField($actionName = false)
    {
        return false;
    } // end isVirtualField
    
    /**
     * Returns true if field must be ignored in queries logic.
     *
     * @return boolean
     */
    public function isCustom($section = false)
    {
        return $this->get('isCustom');
    } // end isCustom
    
    
    /**
     * Returns true if field is updated store logic.
     *
     * @return boolean
     */
    public function isEditable()
    {
        return !$this->isVirtualField();
    } // end isVirtualField
    
    /**
     * @return bool
     */
    public function isForeignKey()
    {
        return false;
    }
    
    /**
     * @param string|null $value
     * @param array $row
     * @return string|null
     * @throws SystemException
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        return $this->getDefaultCellValue($value, $row);
    } // end displayValue
    
    /**
     * @param string|null $value
     * @param array $row
     * @return string
     * @throws SystemException
     */
    protected function getDefaultCellValue(?string $value, array $row = array()): string
    {
        if ($value && $this->get('crypt')) {
            $value = $this->getDecryptedValue($value);
        }
        
        if (
            isset($this->attributes['trim']) && 
            ($this->attributes['trim'] != 'false')
        ) {
            $trimValue = $this->get('trim');
            $trimMode = $this->get('trimMode');
            // need to trim
            if (is_numeric($trimValue) || $trimValue == "button") {
                $key = $this->store->getPrimaryKey();
        
                $this->assign('trimValue', $trimValue);
                $this->assign('trimMode', $trimMode);
                $this->assign('value', $value);
                $this->assign('primaryKeyValue', $row[$key]);
        
                $value = $this->fetch('cell_value_trim.phtml');
            } elseif ($this->attributes['trim'] == 'link') {
                if (strlen($value) > 36) {
                    $value = '<a href="'.$value.'" target="_blank">'.
                    htmlspecialchars(substr($value, 0, 32)) . '...' . 
                    substr($value, -4).'</a>';
                } else {
                    $value = '<a href="'.$value.'" target="_blank">'.
                    htmlspecialchars($value).'</a>';
                }
            }
        }
        
        $value = $this->getFormattedValue($value);
        
        $this->rowValues = $row;
        
        $this->currentValue = $value;
        $value = $this->fetch('cell_value.phtml');
        
        return $value;
    } // end getDefaultCellValue
    
    /**
     * Returns formatted filter value. 
     * Formatted value uses for display to a user.
     * 
     * @param string $value
     * @return string|null
     * @throws FieldException
     */
    public function getFormattedFilterValue($value)
    {
        $filterType = $this->getFilterType();
    
        if (!$value || !$filterType) {
            return null;
        }
    
        if ($filterType != static::FILTER_TYPE_RANGE) {
            
            if (is_array($value)) {
                return join(", ", $value);
            }
            
            return $this->getFormattedValue($value);
        }
    
        if (!is_array($value)) {
            throw new FieldException("Range filter value has type error.");
        }
    
        if (count($value) == 1) {
            if (array_key_exists(0, $value)) {
                $operation = ' >= ';
                $value = $value[0];
            } else if (array_key_exists(1, $value)) {
                $operation = ' <= ';
                $value = $value[1];
            } else {
                throw new FieldException(
                    "Range filter value has structure error."
                );
            }
    
            return $operation.' '.$this->getFormattedValue($value);
        }
    
        return __l(
            'from %s to %s',
            $this->getFormattedValue($value[0]),
            $this->getFormattedValue($value[1])
        );
    } // end getFormattedFilterValue
    
    /**
     * @param $value
     * @return string|null
     */
    public function getFormattedValue($value): ?string
    {
        $format = $this->get('format');
        if (!$format) {
            return $value;
        }

        $methodName = 'getFormatValue'.ucfirst($format);

        if (is_callable(array($this, $methodName))) {
            return $this->$methodName($value);
        }

        if (is_null($value)) {
            return $value;
        }

        return sprintf($format, $value);
    } // end getFormattedValue

    // FIXME:
    /**
     * @param $value
     * @return string
     */
    public function displayRow(string $value): string
    {
        return '<input type="text" readonly disabled name="'.
                $this->_name.'" value="'.$value.'" class="'.
                $this->getCssClassName().'">';
    }
    
    /**
     * @param string $value
     * @return string
     * @throws SystemException
     */
    public function displayRO($value)
    {
        // FIXME:
        if (empty($value)) {
            $attrValue = $this->get('value');
            
            if ($attrValue !== false) {
                $value = $attrValue;
            }
        } else {
            $value = $this->getViewValue($value);
        }
        
        $format = $this->get('format');
        if ($format) {
            $methodName = 'getFormatValue'.ucfirst($format);
            
            if (method_exists($this, $methodName)) {
                $value = $this->$methodName($value);
            } else {
                $value = $this->getFormatValue($value, $format);
            }
        }

        return nl2br($value);
    }

    /**
     * @param string $value
     * @param array $row
     * @return string
     */
    public function getInfoValue($value, $row = null)
    {
        return $this->displayRO($value);
    }
    
    /**
     * @param $value
     * @param $format
     * @return string
     */
    protected function getFormatValue($value, $format)
    {
        return sprintf($format, $value);
    }
    
    /**
     * @param $value
     * @return string
     */
    protected function getFormatValuePrice($value)
    {
        if (is_null($value) || !is_numeric($value)) {
            return $value;
        }
        
        $locale = $this->get('locale');
        if (!$locale) {
            $locale = 'en_US.UTF-8';
        }
        setlocale(LC_MONETARY, $locale);
        
        return money_format('%.2n', $value);
    } // end getFormatValuePrice
    
    /**
     * @param $value
     * @return string
     */
    protected function getFormatValuePercent($value)
    {
        if (is_null($value)) {
            return $value;
        }

        // Move $,2 to attr
        return number_format($value, 2).'%';
    } // end getFormatValuePercent
    
    /**
     * @param $value
     * @return string
     */
    protected function getFormatValueNumber($value)
    {
        if (is_null($value)) {
            return $value;
        }

        $decimals = (int) $this->get('decimals');
    
        return number_format($value, $decimals);
    } // end getFormatValueNumber

    // FIXME:
    /**
     * @param $string
     * @param $from
     * @param $to
     * @return bool|string
     */
    function substr($string, $from, $to)
    {
        if (function_exists('mb_substr')) {
            return mb_substr($string, $from, $to, 'UTF-8');
        } else {
            return substr($string, $from, $to);
        }
    }
    
    /**
     * @param array $requests
     * @return bool|string|mixed|null
     * @throws SystemException
     */
    public function getValue($requests = array())
    {
        $value = array_key_exists($this->_name, $requests) 
            ? $requests[$this->_name] 
            : '';

        if (!$value && $this->get('isnull')) {
            $value = null;
        }

        if (!$this->isValidValue($value)) {
            return false;
        }
        
        if ($this->get('crypt')) {
            $value = $this->getEncryptValue($value);
        }
        
        if ($value) {
            // XXX: XSS Protection
            $this->onPrepareValue($value);
        }

        return $value;
    } // end getValue
    
    /**
     * @return mixed
     * @throws SystemException
     */
    protected function doEventCallback()
    {
        $params = func_get_args();
        $callback = array_shift($params);

        if (is_string($callback)) {
            return $this->_doEventCallbackByString($callback, $params);
        }

        throw new SystemException("Undefined callback type.");
    } // end doEventCallback
    
    /**
     * @param $callback
     * @param $params
     * @return mixed
     * @throws SystemException
     */
    private function _doEventCallbackByString($callback, array $params)
    {
        $data = explode("::", $callback);
        if (empty($data[0])) {
            throw new SystemException("Undefined plugin name for callback.");
        }

        if (empty($data[1])) {
            throw new SystemException("Undefined plugin method for callback.");
        }

        $plugin = Core::getInstance()->getPluginInstance($data[0]);

        $method = array(
            $plugin,
            $data[1]
        );

        if (!is_callable($method)) {
            throw new SystemException("Undefined callback: ".$callback);
        }

        // XXX: We could NOT use array_merge, array_unshift, etc to be compatibility with reference processing PHP 7+
        $callbackParams = array(&$this);
        foreach ($params as $param) {
            $callbackParams[] = $param;
        }

        return call_user_func_array($method, $callbackParams);
    } // end _doEventCallbackByString
    
    /**
     * @param $value
     * @return bool|mixed
     * @throws SystemException
     */
    public function isValidValue($value)
    {
        $onValidateValue = $this->get('onValidateValue');
        if ($onValidateValue) {
            return $this->doEventCallback($onValidateValue, $value);
        }

        $isRequired = $this->get('required');
        if ($isRequired && empty($value)) {
            $this->lastErrorMessage = __l(
                '%s is required field', 
                $this->attributes['caption']
            );
            return false;
        }

        $regExp = $this->get('regexp');
        if ($regExp && !preg_match('#'.$regExp.'#', $value)) {
            
            $allowEmpty = $this->get('allowEmpty');
            $isNull = $this->get('isnull');

            if (!$value && ($allowEmpty || $isNull)) {
                return true;
            }

            $this->lastErrorMessage = __l(
                'Value is not valid in "%s"',
                $this->attributes['caption'],
                $regExp
            );

            return false;
        }
        
        $valueFilter = $this->get('valueFilter');
        if ($valueFilter && !empty($value)) {
            $res = filter_var($value, constant($valueFilter));
            if ($res === false) {
                $this->lastErrorMessage = __l(
                    'Value is not valid in "%s"',
                    $this->attributes['caption'],
                    $regExp
                );
                
                return false;
            }
        }

        return true;
    } // end isValidValue
    
    /**
     * Returns filter type. Returns false if the filter does not set.
     * 
     * @return string|boolean
     */
    public function getFilterType()
    {
        $type = $this->get('filter');
        
        return $type ? strtolower($type) : false;
    } // end getFilterType

    /**
     * Returns a CSS names for DGS Filter HTML elements.
     * 
     * @return string
     * @return string
     */
    public function getFilterCssClassName()
    {
        $cssNames = $this->getCssClassName('filter');
        
        return $cssNames.' e-dgs-field-filter-'.$this->getFilterType();
    } // end getFilterCssClassName

    /**
     * Returns a CSS names for UI element.
     *
     * @param bool|string $postfix = false
     * @return string
     */
    public function getCssClassName($postfix = false): string
    {
        $basePostfix = $postfix ? '-'.$postfix : '-input';
        
        $classes = array(
            'e-dgs-field'.$basePostfix,
            'e-dgs-field'.$basePostfix.'-'.$this->getType(),
            'e-dgs-field-element-'.$this->getElementName(),
            'e-dgs-field'.$basePostfix.'-'.$this->getElementName()
        );
        
        $externalClasses = $this->getExternalCssClassName($postfix);
        
        $classes = array_merge($classes, $externalClasses);
        
        return implode(' ', $classes);
    } // end getCssClassName

    /**
     * Returns additional a CSS names.
     *
     * return array
     */
    protected function getExternalCssClassName($postfix = false)
    {
        return array();
    } // end getExternalCssClassName

    /**
     * Returns css selector for JS.
     *
     * @return string
     */
    public function getCssSelector()
    {
        return '.e-dgs-field-element-'.$this->getName();
    } // end getCssSelector
    
    /**
     * Returns string with attributes of UI element.
     *
     * @return string
     */
    public function getElementAttributes(): string
    {
        $attributes = "";
        
        $readonly = $this->get('readonly');
        if ($readonly == "true") {
            $attributes .= ' readonly="readonly"';
        }
        
        if ($this->get('required')) {
            $attributes .= ' required';
        }
        
        $errorMessage = $this->get('error');
        if ($errorMessage) {
            $errorMessage = htmlspecialchars($errorMessage);
            $attributes .= ' data-error-message="'.$errorMessage.'"';
        }
        
        $regExp = $this->get('regexp');
        if ($regExp) {
            $attributes .= ' pattern="'.$regExp.'"';
        }
        
        $autocomplete = $this->get('autocomplete');
        if ($autocomplete == "off") {
            $attributes .= ' autocomplete="off"';
        }
        
        $mask = $this->get('mask');
        if (!empty($mask)) {
            // XXX: https://github.com/RobinHerbots/jquery.inputmask
            $attributes .= ' data-inputmask="'.htmlspecialchars($mask).'"';
        }
        
        return $attributes;
    } // end getElementAttributes
    
    /**
     * @return bool
     */
    public function getLastErrorMessage()
    {
        return $this->lastErrorMessage;
    } // end getLastErrorMessage
    
    /**
     * @param $message
     */
    public function setErrorMessage($message)
    {
        $this->lastErrorMessage = $message;
    } // end setErrorMessage
    
    /**
     * @return string
     */
    public function getQueryWhere()
    {
        return $this->get(static::OPTION_WHERE);
    } // end getQueryWhere

    /**
     * Return table name for current field
     *
     * @return string
     */
    public function getStoreName(): string
    {
        $storeName = $this->get('store');
        if (!$storeName) {
            $storeName = $this->store->getName();
        }

        return $storeName;
    } // end getStoreName
    
    /**
     * @return bool
     */
    public function isShow()
    {
        $action = $this->store->getAction();
        if ($action != Store::ACTION_LIST && $this->getName()) {
            return true;
        }

        if ($this->get('hide') || !$this->getName()) {
            return false;
        }

        return true;
    } // end isShow

    /**
     * Returns true if field can be sorting on data list.
     *
     * @return boolean
     */
    public function isSorting(): bool
    {
        if (!$this->get('sorting')) {
            return false;
        }

        return !!$this->getName();
    } // end isSorting

    /**
     * Returns filter key for request parameter and session key.
     *
     * @return string|boolean
     */
    public function getFilterKey()
    {
        $fieldName = $this->getName();
        if (!$fieldName) {
            $fieldName = $this->getIndex();
        }

        return $this->store->getIdent().'_'.$fieldName;
    } // end getFilterKey

    /**
     * Returns template name for filter
     *
     * @return string
     */
    protected function getFilterTemplateName()
    {
        $filterType = $this->get('filter');
        $teplateName = strtolower($filterType);

        return $teplateName.'.phtml';
    } // end getFilterTemplateName

    /**
     * Returns path to template folder
     *
     * @return string
     */
    protected function getFilterTemplatePath()
    {
        $path = $this->store->getOption('filter_template_path')."filters";

        return $path.DIRECTORY_SEPARATOR;
    } // end getFilterTemplatePath
    
    /**
     * Returns filter template
     * @return string
     * @throws SystemException
     */
    protected function fetchFilterTemplate()
    {
        $view = new Display($this->getFilterTemplatePath());

        $view->assign("field", $this);

        return $view->fetch($this->getFilterTemplateName());
    } // end fetchFilterTemplate

    /**
     * Override this method if you need add custom logic into filter template.
     */
    protected function onFilterFetch()
    {
        $type = $this->getFilterType();
        $typesForLoadValues = array("multiple", "select");
        if (in_array($type, $typesForLoadValues)) {
            $options = array(
                'unique' => true,
                'foreignKeyField' => 'keyValue',
                'foreignValueField' => $this->getName(),
                'foreignTable' => $this->getStoreName()
            );
            
            $this->filterValues = $this->store->getProxy()->loadForeignValues(
                $options
            );
        }
    } // end onFilterFetch

    /**
     * Returns html template of filed filter.
     *
     * @return string|boolean
     */
    public function fetchFilter()
    {
        $filterType = $this->getFilterType();
        if (!$filterType) {
            return false;
        }

        $value = $this->store->getFieldFilterValueInSession($this);
        $this->filterValue = $value;

        $this->onFilterFetch();

        $store = $this->getStore();
        $target = array(
            'instance' => &$this,
            'field'    => $this->getName(),
            'value'    => &$this->filterValue,
            'values'   => &$this->filterValues,
            'action'   => $store->getAction()
        );

        $event = new FestiEvent(Store::EVENT_ON_FETCH_FIELD_FILTER, $target);
        $store->dispatchEvent($event);

        return $this->fetchFilterTemplate();
    } // end fetchFilter
    
    /**
     * @param $value
     */
    public function setItemValue($value)
    {
        $this->itemValue = $value;
    }
    
    /**
     * @return mixed
     */
    public function getItemValue()
    {
        return $this->itemValue;
    }
    
    /**
     * Returns field input type.
     * 
     * @return string
     */
    public function getInputType()
    {
        $inputType = 'text';
        
        $numberTypes = array("price", "number");
        if (in_array($this->getType(), $numberTypes)) {
            $inputType = 'number';
        }
        
        $format = $this->get('format');
        if (in_array($format, $numberTypes)) {
            $inputType = 'number';
        }
        
        return $inputType;
    } // end getInputType
    
    /**
     * Returns values to field filter.
     * 
     * @return array
     */
    public function getFilterValues()
    {
        return $this->filterValues;
    } // end getFilterValues
    
    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->get('caption');
    } // end getCaption
    
    /**
     * @param $value
     * @return bool
     */
    protected function onPrepareValue(&$value)
    {
        $value = filter_var(
            $value, 
            FILTER_SANITIZE_STRING, 
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
        
        return true;
    } // end onPrepareValue

    /**
     * Returns true if user has permission to the field.
     *
     * @return bool
     * @throws SystemException
     */
    public function hasUserPermission()
    {
        $sectionName = $this->get('permission');

        if (!$sectionName) {
            return true;
        }

        $systemPlugin = Core::getInstance()->getSystemPlugin();

        return $systemPlugin->hasUserPermissionToSection($sectionName);
    } // end hasUserPermission
    
    /**
     * Returns the external value of this field.
     *
     * @return string
     */
    protected function getExternalValue()
    {
        $externalValues = $this->getStore()->getModel()->getExternalValues();
        if (!array_key_exists($this->getName(), $externalValues)) {
            return false;
        }
        
        return $externalValues[$this->getName()];
    }
    
    /**
     * @throws SystemException
     */
    public function isUniqueValues()
    {
        throw new SystemException("Unsupportable method.");
    } // end isUniqueValues
    
    /**
     * @throws SystemException
     */
    public function getForeignStoreName()
    {
        throw new SystemException("Unsupportable method.");
    } // end getForeignStoreName
    
    /**
     * @param $params
     * @param $methodPostfix
     * @throws SystemException
     */
    public function callHandlerMethod($params, $methodPostfix)
    {
        throw new SystemException("Unsupportable method.");
    } // end callHandlerMethod
    
    /**
     * @throws SystemException
     */
    public function getOptions()
    {
        throw new SystemException("Unsupportable method.");
    } // end getOptions
    
    public function isMultiple()
    {
        throw new SystemException("Unsupportable method.");
    } // end isMultiple
    
    public function getPreparedValuesForMultipleSelect(array $values): array
    {
        throw new SystemException("Unsupportable method.");
    } // end getPreparedValuesForMultipleSelect

    /**
     * Returns true if we have to ignore a default logic to load values.
     *
     * @override
     * @return bool
     */
    public function isCustomLoadValues(): bool
    {
        return false;
    } // end isCustomLoadValues

    /**
     * Returns true if field has autocomplete.
     *
     * @return bool
     */
    public function hasAutocomplete(): bool
    {
        return (bool) $this->get(static::OPTION_AUTOCOMPLETE);
    } // end hasAutocomplete

    /**
     * Returns autocomplete url.
     *
     * @return string
     * @throws FieldException
     * @throws SystemException
     */
    public function getAutocompleteUrl(): string
    {
        $autocomplete = $this->get('autocomplete');
        if (!$autocomplete) {
            throw new FieldException("Undefined autocomplete attribute in ".$this->getName());
        }

        if ($autocomplete !== true && is_string($autocomplete)) {
            return $autocomplete;
        }

        $params = array(
            Store::ACTION_KEY_IN_REQUEST => Store::ACTION_FOREIGN_KEY_LOAD,
            'fieldName' => $this->getElementName()
        );

        $params = array(
            $this->getStore()->getIdent() => $params
        );

        $url = $this->getStore()->getCurrentUrl();

        $event = new \core\dgs\event\UrlStoreActionEvent($url, $params, $this->store);
        $this->store->dispatchEvent($event);

        return Core::getInstance()->getUrl($url, $params);
    } // end getAutocompleteUrl

    /**
     * Returns field name for UI.
     *
     * @return string
     */
    public function getElementName(): string
    {
        $fieldName = $this->getName();
        if (!$fieldName) {
            $fieldName = "__".$this->getIndex();
        }

        return $fieldName;
    } // end getElementName

    /**
     * Returns field key in requests.
     *
     * @return string
     */
    public function getKeyInRequest(): string
    {
        return $this->getName();
    } // end getElementName

    public function isRequired(): bool
    {
        return $this->get('required') === true;
    }
}
