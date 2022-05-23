<?php

class ApiPlugin extends ObjectPlugin implements ISystemPlugin
{
    const OPTION_ON_EXCEPTION = "onException";
    const OPTION_ON_RESPONSE  = "onResponse";
    const OPTION_ON_BIND      = "onBind";
    
    protected $systemObject;
    
    /**
     * List of route for http requests
     * @var array
     */
    private $_urlRules;
    
    /**
     * @param array $options
     * @return bool|mixed
     * @throws SystemException
     */
    public function bindRequest($options = array())
    {
        $this->systemObject = $this->getSystemObject();
        
        $options = $this->getExtendData($options, $this->__getRequestOptions());
        
        $this->onInitRequest($options);
        
        if ($options['url']) {
            $currentUrl = $options['url'];
        } else {
            $currentUrl = $this->core->getCurrentURL();
        }
        
        $systemRules = $this->__getDefaultUrlRules();
        
        $rules = $this->_urlRules + $systemRules;

        if (!isset($options['path'])) {
            $options['path'] = $this->core->getOption("plugins_path");
        }
        
        //
        $this->core->addEventListener(
            Core::EVENT_PLUGIN_CALL,
            array($this, 'onBeforeCallPluginMethod')
        );
        
        foreach ($rules as $regExp => $call) {
            if (preg_match($regExp, $currentUrl, $regs)) {
                array_shift($regs);
                $response = $this->getResponseModel($call, $regs);
                
                return $this->doPluginRequest(
                    $call, 
                    $regs, 
                    $options, 
                    $response
                );
            }
        }
        
        return false;
    } // end bindRequest
    
    /**
     * @param array $call
     * @param array $regs
     * @param array $options
     * @param Response $response
     * @return bool|mixed
     * @throws SystemException
     */
    protected function doPluginRequest(array $call, array $regs, array $options, Response $response)
    {
        $params = array();

        if ($response) {
            $pluginInstance = $this->core->getPluginInstance($call[0]);
            $response->setPlugin($pluginInstance);
            $params[] = &$response;
        }

        $params = array_merge($params, $regs);

        if (!empty($options[static::OPTION_ON_EXCEPTION])) {
            try {
                $res = $this->core->call(
                    $call[0], 
                    $call[1], 
                    $params, 
                    $options
                );
            } catch (Exception $exp) {
                $event = array(
                    'call'     => $call,
                    'options'  => $options,
                    'params'   => $params,
                    'exp'      => &$exp,
                    'response' => &$response
                );
                
                call_user_func_array($options[static::OPTION_ON_EXCEPTION], array($event));

                return true;
            }
        } else {
            $res = $this->core->call(
                $call[0], 
                $call[1], 
                $params, 
                $options
            );
        }

        if ($response) {
            if ($options[static::OPTION_ON_RESPONSE]) {
                call_user_func_array($options[static::OPTION_ON_RESPONSE], array(&$response));
            }

            $event = new FestiEvent(Core::EVENT_ON_RESPONSE, $response);
            $this->core->dispatchEvent($event);
            $res = $response->send();
        }

        return $res;
    } // end doPluginRequest
    
    /**
     * Callback method for processing throwing exceptions
     * 
     * @param array $event
     */
    public function onException($event) 
    {
        $response = &$event['response'];

        if ($event['exp'] instanceof SystemException) {
            $message = $event['exp']->getDisplayMessage();
        } else {
            $message = $event['exp']->getMessage();
        }

        $code = $event['exp']->getCode();

        if ($code >= 400) {
            $response->setStatus($code);
        }

        $response->error = array(
            'code'    => $code,
            'message' => $message
        );
        
        $response->send();
    } // end onException
    
    public function getResponseModel($call, $regs = array())
    {
        return new Response(Response::JSON);
    } // end getResponseModel
    
    /**
     * @param array $options
     */
    public function onInitRequest($options)
    {
        $this->__settings = $this->getSystemObject()->getSettings();

        if (!empty($options[static::OPTION_ON_BIND])) {
            call_user_func_array($options[static::OPTION_ON_BIND], array());
        }
        
        // FIXME: Add cache
        $this->_urlRules = $this->loadUrlRulesByArea($options['area']);
        
        $event = new FestiEvent(Core::EVENT_ON_REQUEST, $this);
        $this->core->dispatchEvent($event);
    } // end onInitRequest
    
    /**
     * @param string $areaIdent
     * @return array
     */
    public function loadUrlRulesByArea(string $areaIdent): array
    {
        $search = array(
            'areas.ident' => $areaIdent
        );
        
        $rows =  $this->getSystemObject()->getUrlRules($search);
        
        $result = array();
        
        foreach ($rows as $row) {
            $result[$row['pattern']] = array(
                $row['plugin'],
                $row['method']
            );
        }
        
        return $result;
    } // end loadUrlRulesByArea
    
    /**
     * @return array
     */
    protected function __getDefaultUrlRules(): array
    {
        return array(
            '~^/rpc/([^/]+)/([^/]+)/$~' => array('Api', 'onRpcRequest'),
        );
    } // end __getDefaultUrlRules
    
    /**
     * Returns list of allowed options for processing request
     * @return array
     */
    protected function __getRequestOptions(): array
    {
        return array(
            'area' => array(
                'type'     => Entity::FIELD_TYPE_STRING_NULL,
                'default'  => 'api'
            ),
            
            static::OPTION_ON_EXCEPTION => array (
                'type' => Entity::FIELD_TYPE_METHOD,
                'default' => array($this, static::OPTION_ON_EXCEPTION)
            ),
            
            static::OPTION_ON_BIND => array (
                'type' => Entity::FIELD_TYPE_METHOD
            ),
            
            static::OPTION_ON_RESPONSE => array (
                'type' => Entity::FIELD_TYPE_METHOD
            ),
        
            'url' => Entity::FIELD_TYPE_STRING_NULL
        );
    } // end __getRequestOptions
    
    public function install()
    {
        throw new SystemException("Undefined method install.");
    }
    
    public function onDisplayMain(
        Response &$response,
        $storeName = false,
        $pluginName = false,
        $params = array()
    )
    {
        throw new SystemException("Unsupporteble method onDisplayMain");
    }
    
    public function setActiveMenu($ident)
    {
        throw new SystemException("Unsupporteble method setActiveMenu");
    }
    
    public function getActiveMenu()
    {
        throw new SystemException("Unsupporteble method getActiveMenu");
    }

    public function hasUserPermissionToSection($sectionName, $user = null)
    {
        throw new SystemException("Unsupporteble method hasUserPermissionToSection");
    }
    
    /**
     * Returns value of system setting
     *
     * @param string $key
     * @throws SystemException
     * @return string
     */
    public function getSetting($key)
    {
        if (
            !isset($this->__settings) ||
            !array_key_exists($key, $this->__settings)
        ) {
            throw new SystemException("Undefined setting with key: ".$key);
        }

        return $this->__settings[$key];
    } // end getSetting

    public function hasSetting($key)
    {
        return isset($this->__settings) &&
               array_key_exists($key, $this->__settings);
    }

    public function onBind()
    {
    }

    public function setPermissionSection(string $name, int $mask, int $userType = null, array $users = null): int
    {
        return 0;
    }

    public function refreshPermissionSections(): void
    {
    }
}
