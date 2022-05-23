<?php

define('PARAM_ARRAY', 100);
define('PARAM_STRING', 101);
define('PARAM_STRING_NULL', 104);
define('PARAM_FILE', 102);
define('PARAM_METHOD', 105);

class Entity extends EventDispatcher
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
    const FIELD_TYPE_BOOL        = 111;
    const FIELD_TYPE_SECURITY_STRING = 110;
    
    const OPTION_REQUIRED = "required";
    const OPTION_TYPE     = "type";
    const OPTION_REGEXP   = "regexp";
    const OPTION_DEFAULT  = "default";
    const OPTION_ERROR    = "error";
    const OPTION_FILTER   = "filter";
    
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
        $fields = array();

        foreach ($needles as $fieldName => $options) {
            $fields[$fieldName] = $this->_getDataItemValue(
                $fieldName,
                $request,
                $options,
                $errors
            );
        }

        return $fields;
    } // end getPreparedData
    
    /**
     * @param array $request
     * @param array $needles
     * @param array $errors
     * @return mixed
     */
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
            static::OPTION_REQUIRED,
            static::OPTION_REGEXP,
            static::OPTION_ERROR,
            static::OPTION_FILTER,
            static::OPTION_DEFAULT
        );

        if (is_numeric($options)) {
            $options = array(
                static::OPTION_TYPE => $options
            );
        }

        if (!isset($options[static::OPTION_TYPE])) {
            $options[static::OPTION_TYPE] = self::FIELD_TYPE_STRING_NULL;
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
     * @param int $type
     * @param array|null $request
     * @return mixed
     */
    private function _getDataItemValueByType($name, int $type, ?array &$request)
    {
        switch($type) {
            case self::FIELD_TYPE_ARRAY:
                $value = $this->_getArrayTypeValue($name, $request);
                break;

            case self::FIELD_TYPE_FILE:
                $value = $this->_getFileTypeValue($name);
                break;

            case self::FIELD_TYPE_METHOD:
                $value = $this->_getMethodTypeValue($name, $request);
                break;

            case self::FIELD_TYPE_JSON:
                $value = isset($request[$name]) ? 
                            json_decode($request[$name], true) : null;
                break;

            case self::FIELD_TYPE_OBJECT:
                $value = isset($request[$name]) ? $request[$name] : null;
                break;
                
            case self::FIELD_TYPE_SECURITY_STRING:
                $value = $this->_getSecurityStringTypeValue($name, $request);
                break;

            default:
                $value = $this->_getDefaultValue($name, $request, $type);
        }

        return $value;
    } // end _getDataItemValueByType
    
    /**
     * @param $name
     * @param array $request
     * @return string
     */
    private function _getSecurityStringTypeValue($name, array $request): ?string
    {
        $value = null;
        if (isset($request[$name])) {
            $value = filter_var($request[$name], FILTER_SANITIZE_STRING);
        }
        
        return $value;
    } // end _getSecurityStringType
    
    /**
     * @param $name
     * @param array $request
     * @return mixed|null
     */
    private function _getMethodTypeValue($name, array $request)
    {
        $value = null;
        if (isset($request[$name]) && is_callable($request[$name])) {
            $value = $request[$name];
        }
        
        return $value;
    } // end _getMethodTypeValue
    
    /**
     * @param $name
     * @return mixed|null
     */
    private function _getFileTypeValue($name)
    {
        $value = null;
        if (
            isset($_FILES[$name]) &&
            $_FILES[$name][static::OPTION_ERROR] == UPLOAD_ERR_OK
        ) {
            $value = $_FILES[$name];
        }
        
        return $value;
    } // end _getFileTypeValue
    
    /**
     * @param $name
     * @param array $request
     * @return array
     */
    private function _getArrayTypeValue($name, array $request): array
    {
        return isset($request[$name]) && is_array($request[$name]) ?
               $request[$name] : array();
    } // end _getArrayValue

    /**
     * @param $name
     * @param array|null $request
     * @param int $type
     * @return mixed|null
     */
    private function _getDefaultValue($name, ?array &$request, int $type)
    {
        if (!array_key_exists($name, $request) || !is_scalar($request[$name])) {
            return null;
        }

        $value = $request[$name];

        if ($type == static::FIELD_TYPE_INT) {
            $value = (int) $value;
        } else if ($type == static::FIELD_TYPE_FLOAT) {
            $value = (float) $value;
        } else if ($type == static::FIELD_TYPE_BOOL) {
            $value = filter_var(
                $value, 
                FILTER_VALIDATE_BOOLEAN, 
                FILTER_NULL_ON_FAILURE
            );
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
            $options[static::OPTION_TYPE],
            $request
        );

        $hasError = false;
        if (!$options[static::OPTION_REQUIRED] && $value) {
            $value = $this->_getFilterDataItemValue($value, $options, $hasError);
        } else if ($options[static::OPTION_REQUIRED]) {
            if (!$value) {
                $hasError = true;
            }
            $value = $this->_getFilterDataItemValue($value, $options, $hasError);
        }

        if ($hasError) {
            $errors[$name] = $options[static::OPTION_ERROR] ?? false;
        }

        if (!$value && $options[static::OPTION_DEFAULT]) {
            $value = $options[static::OPTION_DEFAULT];
        }

        return $value;
    } // end _getDataItemValue
    
    /**
     * @param $value
     * @param array $options
     * @param bool $hasError
     * @return mixed
     */
    private function _getFilterDataItemValue($value, array $options, bool &$hasError)
    {
        if ($options[static::OPTION_REGEXP] && !preg_match($options[static::OPTION_REGEXP], $value)) {
            $hasError = true;
        } else if ($options[static::OPTION_FILTER]) {
            $filterResult = filter_var($value, $options[static::OPTION_FILTER]);
            if ($filterResult === false) {
                $hasError = true;
            } else {
                $value = $filterResult;
            }
        }
        
        return $value;
    } // end _getFilterDataItemValue
    
    /**
     * @param string|null $string
     * @param bool|array $values
     * @return string
     */
    public static function fillString(?string $string, $values = false): string
    {
        $in = array();
        $to = array();
        if ($values) {
            foreach ($values as $key => $value) {
                $in[] = '%'.$key.'%';
                $to[] = $value;
            }
        }

        // TODO: add session varibles

        if ($in) {
            return str_replace($in, $to, $string);
        }

        return $string;
    } // end fillString

}
