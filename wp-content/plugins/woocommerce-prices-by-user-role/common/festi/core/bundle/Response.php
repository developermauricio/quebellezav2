<?php

/**
 * Class Response
 *
 * @property string $content
 */
class Response extends ArrayObject
{
    const ACTION_REDIRECT     = "redirect";
    const ACTION_ALERT        = "alert";
    const ACTION_FORM_ERROR   = "formError";
    const ACTION_RELOAD       = "reload";
    const ACTION_CALLBACK     = "callback";
    const ACTION_NOTIFICATION = "notifications";
    const ACTION_DIALOG       = "dialog";
    const ACTION_CONTENT      = "content";
    const ACTION_FILE         = "file";
    const ACTION_LAMBDA       = "lambda";

    const NORMAL      = 'normal';
    const JSON        = 'json';
    const JSON_JS     = 'json_js';
    const JSON_IFRAME = 'json_iframe';
    const JSON_P      = 'jsonp';

    const STATUS_OK          = 200;
    const STATUS_BAD_REQUEST = 400;
    const STATUS_NOT_FOUND   = 404;
    
    const REQUEST_KEY_AJAX_MODE = "popup";
    
    const PARAM_JIMBO_CALLBACKS = "jimbo_callbacks";
    
    /**
     * @var int
     */
    private $_status;
    
    /**
     * @var string
     */
    protected $type;
    
    /**
     * @var string|null
     */
    protected $action   = null;
    protected $__plugin = null;

    /**
     * Returns true if a system gets ajax request.
     * 
     * @return boolean
     */
    public static function isAjaxRequest(): bool
    {
        return array_key_exists(static::REQUEST_KEY_AJAX_MODE, $_REQUEST) &&
               $_REQUEST[static::REQUEST_KEY_AJAX_MODE] == "true";
    } // end isAjaxRequest
    
    /**
     * Response constructor.
     * @param string $type
     * @param string|null $actionType
     */
    public function __construct(string $type = self::NORMAL, string $actionType = null)
    {
        parent::__construct(array(), ArrayObject::ARRAY_AS_PROPS);
        
        $this->messages = array();
        $this->type     = $type;
        $this->action   = $actionType;
        $this->_status  = static::STATUS_OK;
    } // end __construct
    
    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }
    
    /**
     * @param string $type
     */
    public function setAction(string $type)
    {
        $this->action = $type;
    }
    
    /**
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    /**
     * @param AbstractPlugin $plugin
     */
    public function setPlugin(AbstractPlugin &$plugin)
    {
        $this->__plugin = &$plugin;
    }
    
    /**
     * @param $name
     * @param $value
     */
    public function addParam($name, $value = false)
    {
        if (is_array($name)) {
            foreach ($name as $n => $value) {
                $this->$n = $value;
            }
        } else {
            $this->$name = $value;
        }
    } // end addParam
    
    /**
     * @param $message
     */
    public function addMessage($message)
    {
        if (!isset($this->messages)) {
            $this->messages = array();
        }

        if (is_array($message)) {
            foreach ($message as $item) {
                $this->messages[] = htmlentities($item, ENT_QUOTES, 'UTF-8');
            }
        } else {
            $this->messages[] = htmlentities($message, ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * @param $message
     */
    public function addNotification($message)
    {
        if (!isset($this->notifications)) {
            $this->notifications = array();
        }
        
        if (is_array($message)) {
            $this->notifications = array_merge($this->notifications, $message);
        } else {
            $this->notifications[] = $message;
        }
    } // end addNotification
    
    /**
     * @return int
     */
    public function getMessageCount(): int
    {
        return count($this->messages);
    }
    
    /**
     * @return bool
     */
    public function hasMessages(): bool
    {
        return !empty($this->messages);
    }
    
    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
    
    /**
     * @param $type
     * @param $command
     * @throws SystemException
     */
    public function setAfter($type, $command)
    {
        $core = Core::getInstance();

        $core->addParam('after_type', $type);
        $core->addParam('after_command', $command);
    } // end setAfter
    
    /**
     * @param $pluginName
     * @param $method
     * @throws SystemException
     */
    public function setAfterCallback($pluginName, $method)
    {
        $core = Core::getInstance();
        
        $args = func_get_args();
        $params = array_slice($args, 2);
        
        $callback = array(
            'plugin' => $pluginName,
            'method' => $method,
            'params' => $params
        );

        $callbacks = $core->popParam(static::PARAM_JIMBO_CALLBACKS);
        if (!$callbacks) {
            $callbacks = array();
        } else {
            $callbacks = json_decode($callbacks);
        }
        
        $callbacks[] = $callback;

        $core->addParam(static::PARAM_JIMBO_CALLBACKS, json_encode($callbacks));
    } // end setAfterCallback
    
    /**
     * @return array|null
     * @throws SystemException
     */
    public static function callAfter(): ?array
    {
        $core = Core::getInstance();

        $callbacks = $core->popParam(static::PARAM_JIMBO_CALLBACKS);
        if (!$callbacks) {
            return null;
        }

        $callbacks = json_decode($callbacks, true);
       
        $notifications = array();
        foreach ($callbacks as $options) {
            $res = $core->call(
                $options['plugin'], 
                $options['method'], 
                $options['params']
            );
            if ($res) {
                $notifications = array_merge($notifications, $res);
            }
        }
        
        return $notifications;
    }

    /**
     * Returns true if after response page will be reloaded.
     * 
     * @return boolean
     */
    private function _isRefreshPageAction(): bool
    {
        return $this->type != self::NORMAL &&
               ($this->action == self::ACTION_REDIRECT 
               || $this->action == self::ACTION_RELOAD);
    } // end _isRefreshPageAction
    
    /**
     * @param DisplayPlugin|null $plugin
     * @return bool
     * @throws SystemException
     */
    public function send(DisplayPlugin $plugin = null): bool
    {
        $response = $this->getArrayCopy();
    
        $response = $this->_getPreparedResponse($response);
        
        // FIXME:
        if (defined('PHPUnit')) {
            $GLOBALS['outputDisplay'] = $this;
            return true;
        }

        switch ($this->type) {
            case self::JSON:
                $json = json_encode($response);
    
                $this->_setHeaders();
    
                echo $json;
                break;
            case self::NORMAL:
                if (!$plugin) {
                    $plugin = $this->__plugin;
                }
    
                $this->_processing($plugin, $response);
                break;
            case self::JSON_IFRAME:
                $response = $this->_encodeJson($response);
                echo "<script>parent.Jimbo.response(".$response.");</script>";
                break;
            case self::JSON_JS:
                $response = $this->_encodeJson($response);
                echo "<script>Jimbo.response(".$response.");</script>";
                break;

            case self::JSON_P:
                $callbakFunctionName = 'Jimbo.responseIframe';
                if (isset($_REQUEST[static::ACTION_CALLBACK])) {
                    $callbakFunctionName = $_REQUEST[static::ACTION_CALLBACK];
                }
                $this->_setHeaders();
    
                echo $callbakFunctionName . '('.$this->_encodeJson($response).')';
                break;

            default:
                throw new SystemException("Undefined Response Type");
        }
        
        if ($this->action == static::ACTION_FILE) {
            $this->_sendFile();
        }
        
        return true;
    } // end send

    private function _execLambda(): void
    {
        if (!isset($this->lambda) || !is_callable($this->lambda)) {
            throw new SystemException("Undefined lambda function");
        }

        call_user_func($this->lambda);
    } // end _execLambda
    
    /**
     * @param array $response
     * @return array
     * @throws SystemException
     */
    private function _getPreparedResponse(array $response): array
    {
        if ($this->action) {
            $response['type'] = $this->action;
        }
    
        if (empty($response['messages'])) {
            unset($response['messages']);
        }
    
        if ($this->_isRefreshPageAction() && !empty($this->notifications)) {
            $core = Core::getInstance();
            $core->addParam(static::ACTION_NOTIFICATION, $this->notifications);
            unset($response[static::ACTION_NOTIFICATION]);
        }
        
        return $response;
    }
    
    private function _sendFile()
    {
        if (empty($this->path)) {
            throw new SystemException("Undefined file path.");
        }
        
        $fileName = empty($this->fileName) ? basename($this->path) : $this->fileName;
        $contentType = !empty($this->contentType) ?  $this->contentType : "application/octet-stream";


        header('Content-Description: File Transfer');
        header('Content-Type: '.$contentType);
        header('Content-Disposition: attachment; filename="'.$fileName.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($this->path));
        readfile($this->path);
    } // end _sendFile
    
    /**
     * @param array $response
     * @return string
     */
    private function _encodeJson(array $response): string
    {
        $json = json_encode($response);
        return str_replace(array("\\n", "'"), array("", "\'"), $json);
    } // end _encodeJson
    
    /**
     * @param DisplayPlugin|null $plugin
     * @param array $response
     * @return bool
     * @throws SystemException
     */
    private function _processing(DisplayPlugin $plugin = null, array $response = array())
    {
        $core = Core::getInstance();
        
        if (!isset($response['type'])) {
            $response['type'] = false;
        }

        if ($response['type'] == self::ACTION_REDIRECT) {

            if (!empty($this->notifications)) {
                $core->addParam("notifications", $this->notifications);
            }
            
            $core->redirect($response['url'], false);
            return true;
        } else if ($response['type'] == self::ACTION_LAMBDA) {
            $this->_execLambda();
        } else if (isset($response[static::ACTION_CONTENT])) {
            if ($plugin && empty($this->isFlush)) {
                
                $mainTemplaet = 'main.phtml';
                if (!empty($this->mainTemplate)) {
                    $mainTemplaet = $this->mainTemplate;
                }
                
                $plugin->display($response[static::ACTION_CONTENT], $mainTemplaet);
            } else {
                echo $response[static::ACTION_CONTENT];
            }
        }
    } // end _processing
    
    /**
     * Set true if you would like display content without main theme.
     *
     * @param bool $status
     */
    public function flush(bool $status = true): void
    {
        $this->isFlush = $status;
    } // end flush
    
    /**
     * @return bool
     */
    public function isFlush(): bool
    {
        return !empty($this->isFlush);
    } // end isFlush
    
    public function getContent()
    {
        return $this->content ?? false;
    } // end getContent
    
    /**
     * @param int $status
     */
    public function setStatus(int $status)
    {
        $this->_status = $status;
    } // end setStatus

    private function _setHeaders()
    {
        if ($this->_status) {
            http_response_code($this->_status);
        }

        if ($this->type == self::JSON) {
            header('Content-type: application/json');
        } else if ($this->type == self::JSON_P) {
            header('Content-Type: text/javascript');
        }

        return true;
    } // end _setHeaders

}
