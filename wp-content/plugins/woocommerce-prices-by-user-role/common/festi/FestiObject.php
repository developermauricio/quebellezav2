<?php

abstract class FestiObject
{
    const FIELD_TYPE_ARRAY       = 100;
    const FIELD_TYPE_STRING      = 101;
    const FIELD_TYPE_STRING_NULL = 104;
    const FIELD_TYPE_FILE        = 102;
    const FIELD_TYPE_METHOD      = 105;
    const FIELD_TYPE_JSON        = 106;
    const FIELD_TYPE_OBJECT      = 107;
    const FIELD_TYPE_INT         = 108;
    const FIELD_TYPE_FLOAT       = 109;
    const FIELD_TYPE_SECURITY_STRING = 110;
    
    private static $_databaseInstance;
    
    /**
     * Returns valid data values. All errors are written to the $errors.
     *
     * @param array $request
     * @param mixed $needles
     * @param array $errors
     * @return array
     */
    public function getPreparedData($request, $needles, &$errors = array())
    {
        $fileds = array();
    
        foreach ($needles as $fieldName => $options) {
            $fileds[$fieldName] = $this->_getDataItemValue(
                $fieldName,
                $request,
                $options,
                $errors
            );
        }
    
        return $fileds;
    } // end getPreparedData
    
    public function getExtendData($request, $needles, &$errors = array())
    {
        foreach ($needles as $fieldName => $options) {
            $request[$fieldName] = $this->_getDataItemValue(
                $fieldName,
                $request,
                $options,
                $errors
            );
        }
    
        return $request;
    } // end getExtendData
    
    
    /**
     * Returns default options for validation request field
     *
     * @param array $options
     * @return array
     */
    private function _getFieldOptions($options)
    {
        $optionsAttributes = array(
            'required', 'regexp', 'error', 'filter', 'default'
        );
    
        if (is_numeric($options)) {
            $options = array(
                'type' => $options
            );
        }
    
        if (!isset($options['type'])) {
            $options['type'] = self::FIELD_TYPE_STRING_NULL;
        }
    
        foreach ($optionsAttributes as $attribute) {
            if (!isset($options[$attribute])) {
                $options[$attribute] = false;
            }
        }
    
        return $options;
    } // end _getFieldOptions
    
    /**
     * Returns field value by type
     *
     * @param string $name
     * @param string $type
     * @param array $request
     * @return mixed
     */
    private function _getDataItemValueByType($name, $type, &$request)
    {
        switch($type) {
            case self::FIELD_TYPE_ARRAY:
                $value = isset($request[$name]) && is_array($request[$name]) ?
                $request[$name] : array();
                break;
    
            case self::FIELD_TYPE_FILE:
                $value = isset($_FILES[$name]) ?
                $_FILES[$name]['error'] : UPLOAD_ERR_NO_FILE;
                break;
    
            case self::FIELD_TYPE_METHOD:
                $value = null;
                if (isset($request[$name]) && is_callable($request[$name])) {
                    $value = $request[$name];
                }
                break;
    
            case self::FIELD_TYPE_JSON:
                $value = isset($request[$name]) ?
                json_decode($request[$name], true) : null;
                break;
    
            case self::FIELD_TYPE_OBJECT:
                $value = isset($request[$name]) ? $request[$name] : null;
                break;
    
            case self::FIELD_TYPE_SECURITY_STRING:
                $value = null;
                if (isset($request[$name])) {
                    $value = filter_var(
                        $request[$name],
                        FILTER_SANITIZE_STRING
                    );
                }
                break;
    
            default:
                $value = $this->_getDefaultValue($name, $request, $type);
        }
    
        return $value;
    } // end _getDataItemValueByType
    
    private function _getDefaultValue($name, &$request, $type)
    {
        if (
            !is_array($request) ||
            !array_key_exists($name, $request) ||
            !is_scalar($request[$name])
        ) {
            return null;
        }
    
        $value = $request[$name];
    
        if ($type == self::FIELD_TYPE_INT) {
            $value = (int) $value;
        } else if ($type == self::FIELD_TYPE_FLOAT) {
            $value = (float) $value;
        } else if ($value === '') {
            $value = null;
        }
    
        return $value;
    } // end _getDefaultValue
    
    /**
     * Returns valid field value. If field invalid will be written error to
     * array $errors
     *
     * @param string $name
     * @param array $request
     * @param array $options
     * @param array $errors
     * @return mixed
     */
    private function _getDataItemValue($name, $request, $options, &$errors)
    {
        $options = $this->_getFieldOptions($options);
    
        $value = $this->_getDataItemValueByType(
            $name,
            $options['type'],
            $request
        );
    
        $hasError = false;
        if (!$options['required'] && $value) {
            if ($options['regexp'] && !preg_match($options['regexp'], $value)) {
                $hasError = true;
            } else if ($options['filter']) {
                $filterResult = filter_var($value, $options['filter']);
                if ($filterResult === false) {
                    $hasError = true;
                } else {
                    $value = $filterResult;
                }
            }
        } else if ($options['required']) {
            if (!$value) {
                $hasError = true;
            } if (
                $options['regexp'] && !preg_match($options['regexp'], $value)
            ) {
                $hasError = true;
            } else if ($options['filter']) {
                $filterResult = filter_var($value, $options['filter']);
                if ($filterResult === false) {
                    $hasError = true;
                } else {
                    $value = $filterResult;
                }
            }
        }
    
        if ($hasError) {
            $errors[$name] = $options['error'] ? $options['error'] : false;
        }
    
        if (!$value && $options['default']) {
            $value = $options['default'];
        }
    
        return $value;
    } // end _getDataItemValue
    
    public static function convertToCamelCase($str)
    {
        return join("", array_map('ucfirst', explode("_", $str)));
    } // end convertToCamelCase
    
    public static function &getDatabaseInstance()
    {
        if (static::$_databaseInstance) {
            return static::$_databaseInstance;
        }
        
        static::$_databaseInstance =
            DataAccessObject::factory($GLOBALS['wpdb']);
        
        return static::$_databaseInstance;
    } // end getDatabaseInstance
}