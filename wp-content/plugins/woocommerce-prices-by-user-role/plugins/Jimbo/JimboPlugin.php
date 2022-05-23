<?php
define('EXCEPTION_CODE_FOR_ASYNC_REQUEST', -334332);
define('EXCEPTION_CODE_FOR_AJAX_REQUEST', -334333);
define('EXCEPTION_CODE_FOR_JSON_REQUEST', -334334);

class JimboPlugin extends DisplayPlugin implements ISystemPlugin
{
    const HOOK_SIGNUP_VALUES = "onHookSignupValues";

    /**
     * List of allowed options for processing http requests
     * @var array
     */
    private $_requestOptions = array(
        'area' => array(
            'type'     => Entity::FIELD_TYPE_STRING_NULL,
            'default'  => 'default'
        ),
        
        'onException' => array (
            'type' => Entity::FIELD_TYPE_METHOD
        ),
        
        'onBind' => array (
            'type' => Entity::FIELD_TYPE_METHOD
        ),
        
        'onResponse' => array (
            'type' => Entity::FIELD_TYPE_METHOD
        ),
    
        'mode' => array(
            'type'     => Entity::FIELD_TYPE_STRING_NULL,
            'default'  => 'default'
        ),
    
        'url' => Entity::FIELD_TYPE_STRING_NULL
    );
    
    /**
     * List of route for http requests
     * @var array
     */
    private $_urlRules;
    
    private $_listeners;
    
    private $_activeMenuIdent;

    /**
     * List of actions to check permission.
     * @var array
     */
    private $_sectionsActions;

    private $_sections;

    /**
     * List of actions granted to the user.
     * @var array
     */
    private $_userSectionsActions;

    private $_userSections;
    
    public function onInit()
    {
        parent::onInit();

        $this->_requestOptions['onException']['default'] = array(
            $this, 
            'onException'
        );
        
        $callbackMethod = array($this, 'onInitPluginInstance');
        
        $this->core->addEventListener(
            Core::EVENT_PLUGIN_INIT, 
            $callbackMethod
        );
        
    } // end onInit
    
    public function onInitPluginInstance($event)
    {
        $plugin = &$event->currentTarget['plugin'];
        
        if (isset($this->__settings)) {
            $plugin->__settings = $this->__settings;
        }
    } // end onInitPluginInstance
    
    private function _doInstall($options)
    {
        $call = array($this->getOption('name'), 'onDisplayInstallSystem');
        $response = $this->_getResponseForCallingMethod($call);
                
        return $this->doPluginRequest(
            $call, 
            array(), 
            $options, 
            $response
        );
    } // end _doInstall
    
    public function bindRequest($options = array(), &$returnResponse = null)
    {
        $options = $this->getExtendData($options, $this->_requestOptions);
        
        if ($options['mode'] == "install") {
            return $this->_doInstall($options);
        }
        
        $this->onInitRequest($options);
        
        if ($options['url']) {
            $currentUrl = $options['url'];
        } else {
            $currentUrl = $this->core->getCurrentURL();
        }
        
        $pluginName = $this->getOption('name');
        
        $systemRules = array(
            '~^/jimbo/$~' => array($pluginName, 'onDisplayMain'),
            '~^/jimbo/([^/]+)/$~' => array($pluginName, 'onDisplayMain'),
            '~^/jimbo/([^/]+)/([^/]+)/$~' => array(
                $pluginName, 
                'onDisplayMain'
            ),
            '~^/festi/([^/]+)/([^/]+)/$~' => array(
                $pluginName, 
                'onDisplayMain'
            ),
            '~^/festi/([^/]+)/$~' => array($pluginName, 'onDisplayMain')
        );

        $rules = $this->_urlRules + $systemRules;

        if (!empty($GLOBALS['urlRules'])) {
            $rules = $GLOBALS['urlRules'] + $rules;
        }

        if (!isset($options['path'])) {
            $options['path'] = $this->core->getOption("plugins_path");
        }

        foreach ($rules as $regExp => $call) {
            if (preg_match($regExp, $currentUrl, $regs)) {
                array_shift($regs);
                $response = $this->_getResponseForCallingMethod($call);

                return $this->doPluginRequest(
                    $call, 
                    $regs, 
                    $options, 
                    $response
                );
            }
        }

        throw new NotFoundException();
    } // end onBindRequest
    
    /**
     * Returns response instance for calling method.
     * 
     * @param array $call
     * @return Response
     */
    private function _getResponseForCallingMethod($call): ?Response
    {
        $methodRules = array(
            'onDisplay'      => Response::NORMAL,
            'onUpdate'       => Response::NORMAL,
            'doAjaxResponse' => Response::JSON_IFRAME,
            'onAjax'         => Response::JSON_IFRAME,
            'doJsonResponse' => Response::JSON,
            'onJson'         => Response::JSON,
        );
        
        foreach ($methodRules as $rule => $responseType) {
            if (strpos($call[1], $rule) !== false) {
                return new Response($responseType);
            }
        }
        
        return null;
    } // end _getResponseForCallingMethod
    
    protected function doPluginRequest($call, $regs, $options, $response)
    {
        $this->onBeforeCallPluginMethod($call[0], $call[1]);

        $params = array();
        
        if ($response) {
            $pluginInstance = $this->core->getPluginInstance($call[0]);
            $response->setPlugin($pluginInstance);
            $params[] = &$response;
        }
        
        $params = array_merge($params, $regs);


        // Dispatch system event for interceptors
        $target = array(
            'params'   => &$params,
            'response' => &$response,
            'plugin'   => $call[0],
            'method'   => $call[1]
        );

        $event = new FestiEvent(ISystemPlugin::EVENT_ON_BEFORE_REQUEST_PLUGIN_METHOD, $target);
        $this->core->dispatchEvent($event);

        if (!empty($options['onException'])) {
            try {
                $res = $this->core->call(
                    $call[0],
                    $call[1],
                    $params,
                    $options
                );
            } catch (Exception $exp) {
                $event = array(
                    'call'    => $call,
                    'options' => $options,
                    'params'  => $params,
                    'exp'     => &$exp
                );
                
                call_user_func_array($options['onException'], array($event));
                return false;
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
            if ($options['onResponse']) {
                call_user_func_array($options['onResponse'], array(&$response));
            }
            
            $event = new FestiEvent(Core::EVENT_ON_RESPONSE, $response);
            $this->core->dispatchEvent($event);
            
            $res = $response->send();
            exit();
        }
        
        return $res;
    } // end doPluginRequest


    public function onBeforeCallPluginMethod(
        string $pluginName, string $method
    ): void
    {
        $this->_doCheckUserPermission($pluginName, $method);
    } // end onBeforeCallPluginMethod

    private function _doCheckUserPermission(
        string $plugin, string $method
    ): bool
    {
        if (empty($this->_sectionsActions[$plugin][$method])) {
            // XXX: if action not included then ignore validation process
            return false;
        }

        $expectedMask = $this->_sectionsActions[$plugin][$method];

        $grantedMask = 0;
        if (!empty($this->_userSectionsActions[$plugin][$method])) {
            $grantedMask = $this->_userSectionsActions[$plugin][$method];
        }

        $hasPermission = intval($grantedMask) >= intval($expectedMask);

        if (!$hasPermission) {
            $msg = "Permission denied to ".$plugin."::".$method;
            $exception = new PermissionsException($msg);
            $exception->setDisplayMessage(__("Permission denied."));

            throw $exception;
        }

        return true;
    } // end _doCheckUserPermission

    public function loadSettings()
    {
        $this->__settings = $this->object->getSettings();
    }

    public function onInitRequest($options = array())
    {
        $this->loadSettings();
        
        $isMobile = $this->core->isMobileRequest();
        
        $this->core->isMobile = $isMobile;
        
        if (!empty($options['onBind'])) {
            call_user_func_array($options['onBind'], array());
        }

        $area = 'default';
        if (array_key_exists('area', $options)) {
            $area = $options['area'];
        }        
        
        // FIXME: Add cache
        $this->_urlRules = $this->loadUrlRulesByArea($area);
        
        $this->_listeners = $this->loadListenersByArea($area);

        $this->_prepareSections();

        $this->_prepareUserSections();
        
        $themePath = $this->core->getOption('theme_path');
        $themePath .= "include.php";
        
        if (file_exists($themePath)) {
            require_once $themePath;
        }
        
        $event = new FestiEvent(Core::EVENT_ON_REQUEST, $this);
        $this->core->dispatchEvent($event);
        
    } // end onInitRequest

    private function _prepareSections()
    {
        $rows = $this->object->getAllSectionsActions();

        $actions = array();
        $sections = array();
        foreach ($rows as $row) {
            $actions[$row['plugin']][$row['method']] = (int) $row['mask'];

            $sections[$row['ident']] = (int) $row['section_mask'];
        }

        $this->_sectionsActions = $actions;
        $this->_sections = $sections;

        return true;
    } // end _loadSectionsActions

    public function refreshPermissionSections(): void
    {
        $this->_prepareSections();
        $this->_prepareUserSections();
    } // end refreshSections

    private function _prepareUserSections()
    {
        if (empty($this->core->user)) {
            return false;
        }

        $rows = $this->object->getSectionsActionsByUser(
            $this->core->user
        );

        $actions = array();
        $sections = array();

        foreach ($rows as $row) {

            // XXX: User mask has higher priorities
            $mask = $row['user_mask'] ? $row['user_mask'] : $row['type_mask'];

            if ($row['method']) {
                $actions[$row['plugin']][$row['method']] = (int) $mask;
            }

            $sections[$row['ident']] = (int) $mask;
        }

        $this->_userSectionsActions = $actions;
        $this->_userSections = $sections;

        return true;
    } // end _prepareUserSections

    
    public function loadListenersByArea($areaIdent)
    {
        $search = array(
            'url_area' => $areaIdent
        );
        
        $listeners = array();
        
        $rows =  $this->object->getListeners($search);
        foreach ($rows as $row) {
            $listeners[$row['plugin']][$row['method']][] = array(
                'plugin' => $row['callback_plugin'],
                'method' => $row['callback_method']
            );
        }

        return $listeners;
    } // end loadListeners
    
    
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

    /**
     * Callback method for processing throwing exceptions
     *
     * @param array $event
     * @throws SystemException
     */
    public function onException($event) 
    {
        $code = $this->getExceptionCodeByMethodName(
            $event['call'][1],
            $event['exp']
        );

        $responseType = array(
            EXCEPTION_CODE_FOR_JSON_REQUEST => Response::JSON,
            EXCEPTION_CODE_FOR_AJAX_REQUEST  => Response::JSON_IFRAME,
            EXCEPTION_CODE_FOR_ASYNC_REQUEST => Response::JSON_JS
        );

        if (isset($responseType[$code])) {
            $response = new Response(
                $responseType[$code],
                Response::ACTION_ALERT
            );
            
            $exp = $event['exp'];
            
            if ($exp instanceof FieldException) {
                $response->setAction(Response::ACTION_FORM_ERROR);
                
                $response->fields = array(
                    $exp->getSelector() => $exp->getMessage()
                );
            }
            
            $title = false;
            if ($exp instanceof SystemException) {
                
                $params = $exp->getData();
                if ($params) {
                    $response->addParam($params);
                }
                
                $title = $exp->getLabel();
                $message = $exp->getDisplayMessage();
            } else {
                $message = __(
                    "We are sorry... Something went wrong and administration ".
                    "has been notified of a problem. We are investigating ".
                    "and fixing the issue."
                );
            }
            
            $response->title = $title ? $title : __l('Notification');
            $response->addMessage($message);
            $response->send();
            exit();
        }

        throw $event['exp'];
    } // end onException
    
    
    public function getExceptionCodeByMethodName($methodName, $exp)
    {

        if (($exp instanceof SystemException) && $exp->hasSource()) {
            $source = $exp->getSource();
            if ($source instanceof AbstractDisplayAction && $source->isExec()) {
                return EXCEPTION_CODE_FOR_AJAX_REQUEST;
            }
        }

        $regExp = "#^doAjax#Umis";
        $isAjaxRequest = preg_match($regExp, $methodName);
        if ($isAjaxRequest) {
            return EXCEPTION_CODE_FOR_AJAX_REQUEST;
        }
        
        $regExp = "#^onAjax#Umis";
        $isAjaxRequest = preg_match($regExp, $methodName);
        if ($isAjaxRequest) {
            return EXCEPTION_CODE_FOR_AJAX_REQUEST;
        }
            
        //
        $regExp = "#^doJson#Umis";
        $isAjaxRequest = preg_match($regExp, $methodName);
        if ($isAjaxRequest) {
            return EXCEPTION_CODE_FOR_JSON_REQUEST;
        }
        
        //
        $regExp = "#^onJson#Umis";
        $isAjaxRequest = preg_match($regExp, $methodName);
        if ($isAjaxRequest) {
            return EXCEPTION_CODE_FOR_JSON_REQUEST;
        }
          
        return $exp->getCode();  
    } // end getExceptionCodeByMethodName
    
    
    public function loadUrlRulesByArea($areaIdent)
    {
        $search = array(
            'areas.ident' => $areaIdent
        );
        
        $rows =  $this->object->getUrlRules($search);
        
        $result = array();
        
        foreach ($rows as $row) {
            $result[$row['pattern']] = array(
                $row['plugin'],
                $row['method']
            );
        }
        
        return $result;
    } // end loadUrlRulesByArea
    
    
    public function onDisplayMain(
        Response &$response, 
        $table = false, 
        $pluginName = false, 
        $params = array()
    )
    {
        $themeUrl = $this->core->getOption('theme_url');
        $engineUrl = $this->core->getOption('engine_url');
        
        $this->core->includeCss($themeUrl."css/db.css", false);
        
        /*
        $calendarUrl = $engineUrl."js/calendar/";
        $this->core->includeJs($calendarUrl."calendar.js", false);
        $this->core->includeJs($calendarUrl."lang/calendar-en.js", false);
        $this->core->includeJs($calendarUrl."calendar_add.js", false);
        $this->core->includeCss($calendarUrl."calendar.css", false);
        */
        
        $sessionData = &$this->core->getSessionData();

        $authData = $this->core->user->get('auth_data');

        if ($pluginName) {
            $params['plugin'] = $pluginName;
            $pluginPath = $this->getOption('plugins_path');
            $path = $pluginPath.$pluginName.'/tblDefs/';
            $handlersPath = $pluginPath.$pluginName.'/tblHandlers/';
            $this->core->setOption('defs_path', $path);
            $this->core->setOption('handlers_path', $handlersPath);
        }

        $template = $this->getTemplateName();

        //try {
            $this->core->getView(
                $this->core->db, 
                $table, 
                $params,
                $response
            );
            /*
        } catch (Exception $exp) {
            
            $exceptionCode = $exp->getCode();
            if (!$template) {
                $exceptionCode = EXCEPTION_CODE_FOR_ASYNC_REQUEST;
            }

            $source = null;
            if ($exp instanceof SystemException) {
                $source = &$exp->getSource();
            }

            $source = "asdasdsa";

            //throw $exp;
            throw new SystemException($exp->getMessage(),
(int) $exceptionCode, false,$source );
        }
            */

        if (!$template) {
            $response->isFlush = true;
        }
        
        return true;
    } // end main

    public function handleDisplayList($event)
    {
        $this->core->setTitle($event->currentTarget['info']['caption']);
    } // end handleDisplayList


    /**
     * Returns the name of the main template
     */
    private function getTemplateName()
    {
        return isset($_GET['popup']) ? false : 'main.phtml';
    } // end getTemplateName

    public function fetchMenu($area = false, $template = false)
    {
        $res = $this->doThemeEvent('ThemeFetchMenu');
        if ($res) {
            return join("", $res);
        }
        
        $structureMenu = $this->getStructureMenu($area);

        if (!$area) {
            $area = 'default';
        }

        $currentItem = $_SERVER['REQUEST_URI'];
        
        $vars = array(
            'currentItem'  => $currentItem,
            'name'         => $area.'-menu',
            'items'        => array_values($structureMenu),
            'activeItem'   => $this->getActiveMenu()
        );
    
        $target = &$vars;
        
        $event = new FestiEvent(static::EVENT_ON_PREPARE_MENU_ITEMS, $target);
        $this->dispatchEvent($event);
        
        if ($template) {
            $this->assign($vars);
            return $this->fetch($template);
        }
        
        return $this->core->systemFetch('menu.php', $vars);
    } // end fetchMenu

    /**
     * Returns array of menu items.
     *
     * @rpc
     * @param bool $area virtual group
     * @return array
     */
    public function getStructureMenu($area = false)
    {
        $idUser   = $this->core->user->getID();
        $userRole = $this->core->user->getRole();
        $search   = array();
        
        if ($idUser) {
            $search['id_user'] = $idUser;
        }

        if ($userRole) {
            $search['id_user_type'] = $userRole;
        }

        if (!$area) {
            $search['area&IS'] = 'NULL';
        } else {
            $search['area'] = $area;
        }

        // XXX: Menu items without a section permissions
        $search['sql_or'][] = array(
            'm.id_section&IS' => 'NULL',
            'p.id_role'       => $userRole
        );
        
        $search['sql_or'][] = array(
            'sql_or' => array(
                0 => array(
                    'user_sections.value&IS' => 'NULL',
                    'sections.value&>'       => 0,
                    'sections.id_user_type'  => $userRole
                ),
                1 => array(
                    'user_sections.value&>'  => 0
                )
            )
        );
        
        $orderBy = array(
            'm.id_parent', 'm.order_n'
        );

        $tmp = $this->object->getMenu($search, $orderBy);

        $result = $this->_getMenuItemsTree($tmp);

        return $result;
    } // end getStructureMenu
    
    private function _getMenuItemsTree($rows, $idParent = false, $level = 0)
    {
        $result = array();
        $level++;
        foreach ($rows as $index => $row) {
            if ($row['id_parent'] != $idParent) {
                continue;
            }
            
            $menuItem = array(
                'caption'   => $row['caption'],
                'href'      => $this->getMenuItemUrl($row['url']),
                'level'     => $level,
                'id_parent' => $row['id_parent'],
                'items'     => null,
                'ident'     => empty($row['ident']) ? null : $row['ident']
            );
            
            $childs = $this->_getMenuItemsTree($rows, $row['id'], $level);
            
            $menuItem['items'] = $childs ? $childs : null;
            
            $result[$row['id']] = $menuItem;
            
            unset($rows[$index]);
        }
    
        return $result;
    } // end _getMenuItemsTree
    
    protected function getMenuItemUrl($url)
    {
        
        return $this->core->getUrl($url);
    } // end getMenuItemUrl
    
    public function setActiveMenu($ident)
    {
        $this->_activeMenuIdent = $ident;
    } // end setActiveMenu
    
    public function getActiveMenu()
    {
        return $this->_activeMenuIdent;
    } // end getActiveMenu
    
    /**
     * Display default
     * 
     * @urlRule ~^/$~
     * @area backend
     */
    public function onDisplayDefault(Response &$response)
    {
        if (!$this->core->user->isLogin()) {
            $response->setAction(Response::ACTION_REDIRECT);
            $response->url = $this->core->getUrl('/login/');
            return true;
        }
        
        if ($this->hasSetting('default_url')) {
            $this->core->redirect(
                $this->getSetting('default_url'), 
                false
            );
        }
        
        $response->content = "";
        
        return true;
    } // end onDisplayDefault
    
    /**
     * Display default login form
     * 
     * @urlRule ~^/login/$~
     * @area backend
     */
    public function onDisplaySignin(Response &$response)
    {
        if (array_key_exists('rel', $_GET)) {
            $this->redirectTo = $_GET['rel'];
        } else {
            $this->redirectTo = $this->core->getOption('http_base');
        }
        
        if (!empty($_REQUEST['token'])) {
            $res = $this->signinByToken($_REQUEST['token']);
        
            if ($res) {
                $response->setAction(Response::ACTION_REDIRECT);
                $response->url = urldecode($this->redirectTo);
                return true;
            }
        }
        
        if ($this->core->user->isLogin()) {
            $response->setAction(Response::ACTION_REDIRECT);
            $response->addNotification(__('Already Signin'));
            $response->url = urldecode($this->redirectTo);
            return true;
        }
        
        if (!empty($_POST) && $this->_doSignin()) {
            $url = urldecode($this->redirectTo);
            $this->core->redirect($url, false);
        }
        
        $this->errorMessage = $this->core->popParam('error');
        
        $response->content = $this->fetch('signin.php');
        
        return true;
    } // end onDisplaySignin
    
    public function signinByToken($token)
    {
        if ($this->core->user->isLogin()) {
            $this->core->user->logout();
        }
        
        $search = array(
            'access_token' => $token
        );
        
        $usersTableName = $this->getSetting('users_table');
        
        $data = $this->object->getUser($usersTableName, $search);
        
        if (!$data) {
            return false;
        }
        
        $loginColumn    = $this->getSetting('auth_login_column');
        $roleColumn     = $this->getSetting('auth_role_column');
        
        $params = array(
            'auth'       => 'yes',
            'auth_id'    => $data['id'],
            'auth_login' => $data[$loginColumn],
            'auth_role'  => $data[$roleColumn],
            'auth_data'  => $data
        );
        
        $this->core->user->doLogin($params);
        
        return $data['id'];
    } // end signinByToken
    
    /**
     * Display default signup form
     *
     * @urlRule ~^/signup/$~
     * @area backend
     */
    public function onDisplaySignup(Response &$response)
    {
        if (
            (!empty($_POST) && $this->_doSignup()) || 
            $this->core->user->isLogin()
        ) {
            $redirectTo = $this->core->getOption('http_base');
            
            $response->setAction(Response::ACTION_REDIRECT);
            
            $response->addNotification(__('Success Sign Up'));
            $response->url = urldecode($redirectTo);
            return true;
        }
        
        $this->errorMessage = $this->core->popParam('error');
        
        $response->content = $this->fetch('signup.phtml');
        
        return true;
    } // end onDisplaySignup
    
    /**
     * Display default signup form
     *
     * @urlRule ~^/forgot/$~
     * @area backend
     */
    public function onDisplayFrogotPassword(Response &$response)
    {
        if ($this->core->user->isLogin()) {
            $redirectTo = $this->core->getOption('http_base');
            $response->setAction(Response::ACTION_REDIRECT);
            $response->url = urldecode($redirectTo);
            return true;
        }
                
        $this->isSend = (!empty($_POST) && $this->_doFrogotPassword());
        
        if ($this->isSend) {
            $response->setAction(Response::ACTION_REDIRECT);
            
            $response->addNotification(__('You will receive link for change password via e-mail'));
            $response->url = $this->getUrl('/login/');
            return true;
        }

        $this->errorMessage = $this->core->popParam('error');

        $response->content = $this->fetch('forgot_password.phtml');
        
        return true;
    } // end onDisplayFrogotPassword
    
    private function _doFrogotPassword()
    {
        try {
            $userData = $this->doFrogotPasswordProcedure($_REQUEST);
        } catch (SystemException $exp) {
            $this->core->addParam('error', $exp->getMessage());
            return false;
        }
    
        return true;
    } // end _doFrogotPassword
    
    private function _doSignup()
    {
        try {
            $userData = $this->signup($_POST);
        } catch (SystemException $exp) {
            $this->core->addParam('error', $exp->getMessage());
            return false;
        }
        
        return true;
    } // end _doSignup
    
    public function doFrogotPasswordProcedure($data)
    {
        $fields = $this->getFrogotPasswordProcedureFields();
    
        $errors = array();
        $data = $this->getPreparedData($data, $fields, $errors);
        
        if ($errors) {
            $error = each($errors);
            $errorMessage = empty($error['value']) ? __('Undefined error') : $error['value'];
            throw new SystemException($errorMessage);
        }
    
        $usersTableName = $this->getSetting('users_table');
        $emailColumn    = $this->getSetting('auth_email_column');
        $loginColumn    = $this->getSetting('auth_login_column');
        $passColumn     = $this->getSetting('auth_pass_column');
        $roleColumn     = $this->getSetting('auth_role_column');
        $idUserRole     = $this->getSetting('default_user_type');
    
        //
        $search = array(
            $emailColumn => $data['email']
        );
    
        $info = $this->object->getUser($usersTableName, $search);
        if (!$info) {
            throw new SystemException(
                __('No account found for this email address.')
            );
        }
        
        if (!$info['access_token']) {
            $info['access_token'] = $this->_updateAccesssToken($info['id']);
        }
        
        $params = array(
            'token' => $info['access_token'],
            'uid'   => $info['id']
        );
        
        $url = 'https://'.$this->getSetting('host').
               $this->getUrl('/forgot/change/', $params);
               
        $emailData = array(
            'user' => $info,
            'url'  => $url
        );
        
        $this->plugin->mail->send(
            $info['email'],
            'email_forgot_password', 
            $emailData
        );
    } // end doFrogotPasswordProcedure
    
    private function _updateAccesssToken($idUser)
    {
        $usersTableName = $this->getSetting('users_table');
        
        $accessToken = md5($idUser.time());
        $values = array(
            'access_token' => $accessToken
        );
        
        $search = array(
            'id' => $idUser
        );
        
        $this->object->changeUser($usersTableName, $values, $search);
        
        return $accessToken;
    } // end _updateAccesssToken
    
    public function signup($data)
    {
        $fields = $this->getSignupFields();
        
        $errors = array();
        $data = $this->getPreparedData($data, $fields, $errors);
        
        if ($errors) {
            $error = each($errors);
            $errorMessage = empty($error['value']) ? __('Undefined error') : $error['value'];
            throw new SystemException($errorMessage);
        }
        
        $usersTableName = $this->getSetting('users_table');
        $loginColumn    = $this->getSetting('auth_login_column');
        $passColumn     = $this->getSetting('auth_pass_column');
        $emailColumn    = $this->getSetting('auth_email_column');
        $roleColumn     = $this->getSetting('auth_role_column');
        $idUserRole     = $this->getSetting('default_user_type');
        
        //
        $search = array(
            $loginColumn => $data['login']
        );
        
        $info = $this->object->getUser($usersTableName, $search);
        if ($info) {
            throw new SystemException(
                __('An account with this login address already exists')
            );
        }
        
        //
        $search = array(
            $emailColumn => $data['email']
        );
        
        $info = $this->object->getUser($usersTableName, $search);
        if ($info) {
            throw new SystemException(
                __('An account with this E-mail address already exists')
            );
        }
        
        $values = array(
            $loginColumn => $data['login'],
            $passColumn  => $data['password'],
            $emailColumn => $data['email'],
            $roleColumn  => $idUserRole
        );
        
        $this->object->begin();
        
        $this->core->fireHook(static::HOOK_SIGNUP_VALUES, $values);
        
        $values[$passColumn] = md5($values[$passColumn]);
        
        $values['id'] = $this->object->addUser($usersTableName, $values);
        
        $this->object->commit();
        
        $this->signin($data['login'], $data['password']);
        
        return $values;
    } // end signup
    
    protected function getFrogotPasswordProcedureFields()
    {
        $fields = array(
            'email' => array(
                'type'     => Entity::FIELD_TYPE_STRING,
                'filter'   => FILTER_VALIDATE_EMAIL,
                'required' => true,
                'error'    => __('Enter your email.')
            )
        );
    
        return $fields;
    } // end getFrogotPasswordProcedureFields
    
    protected function getSignupFields()
    {
        $fields = array(
            'login'    => array(
                'type'     => Entity::FIELD_TYPE_SECURITY_STRING,
                'required' => true,
                'error'    => __('Enter your login.')
            ),
            'email' => array(
                'type'     => Entity::FIELD_TYPE_STRING,
                'filter'   => FILTER_VALIDATE_EMAIL,
                'required' => true,
                'error'    => __('Enter your email.')
            ),
            'password' => array(
                'type'     => Entity::FIELD_TYPE_STRING,
                'required' => true,
                'error'    => __('Enter your password.')
            )
        );
        
        return $fields;
    } // end getSignupFields
    

    private function _doSignin()
    {
        $signinFields = array(
            'login'    => PARAM_STRING,
            'password' => PARAM_STRING,
        );
        
        $fields = $this->getPreparedData($_REQUEST, $signinFields);
        
        try {
            $idUser = $this->signin($fields['login'], $fields['password']);
        } catch (SystemException $exp) {
            $this->core->addParam('error', $exp->getMessage());
            return false;
        }
        
        return $idUser;
    } // end _doSignin
    
    /**
     * User authorization on system. Returns user ID if all is good or throw 
     * exception if something wrong.
     * 
     * @param string $login
     * @param string $passaword
     * @throws SystemException
     * @return boolean|integer
     */
    public function signin($login, $passaword)
    {
        if (empty($login) || empty($passaword)) {
            $msg  = __('Username or password can not be empty');
            throw new SystemException($msg);
        }
        
        $loginColumn    = $this->getSetting('auth_login_column');
        $passColumn     = $this->getSetting('auth_pass_column');
        $usersTableName = $this->getSetting('users_table');
        $roleColumn     = $this->getSetting('auth_role_column');
        
        $search = array(
            $loginColumn => $login,
            $passColumn  => md5($passaword) // FIXME:
        );
        
        $info = $this->object->getUser($usersTableName, $search);
        
        if (!$info) {
            $msg  = __('Wrong username or password');
            throw new SystemException($msg);
        }
        
        $params = array(
            'auth'       => 'yes', 
            'auth_id'    => $info['id'], 
            'auth_login' => $info[$loginColumn], 
            'auth_role'  => $info[$roleColumn],
            'auth_data'  => $info
        );
        
        $this->core->user->doLogin($params);
        
        return $info['id'];
    } // end signin
    
    /**
     * Make logout user
     * 
     * @urlRule ~^/logout/$~
     * @area backend
     */
    public function onDisplayLogout(Response &$response)
    {
        $this->core->user->logout();
        
        $response->setAction(Response::ACTION_REDIRECT);
        $response->url = $this->core->getOption('http_base');
        
        return true;
    } // end onDisplayLogout
    
    public function onDisplay()
    {
        $seo = array();
        if (isset($this->core->seo)) {
            $seo = $this->core->seo;
        }
        
        $metaFields = array(
            'title'       => Entity::FIELD_TYPE_STRING_NULL,
            'description' => Entity::FIELD_TYPE_STRING_NULL,
            'keywords'    => Entity::FIELD_TYPE_STRING_NULL,
            'image_src'   => Entity::FIELD_TYPE_STRING_NULL
        );
        
        $seo = $this->getExtendData($seo, $metaFields);
        
        $this->onMeta($seo);
        
    } // end onDisplay
    
    public function onMeta(&$data)
    {
        $this->seo = $data;
    }
    
    public function fetchHead($info)
    {
        $this->info = $info;
        
        return $this->fetch('head.php');
    } // end onHead
    
    public function fetchBreadcrumb(DisplayPlugin &$plugin)
    {
        $plugin->onBreadcrumb();
        
        return $this->fetch('breadcrumb.php');
    } // end fetchBreadcrumb
    
    
    public function fetchNotifications($info = false)
    {
        $this->notifications = $this->core->popParam("notifications");
        
        return $this->fetch('notifications.php');
    } // end fetchNotifications
    
    
    public function fetchHeader($info)
    {
        return $this->fetch('header.php');
        
    } // end fetchHeader
    
    public function fetchFooter($info)
    {
        return $this->fetch('footer.php');
    } // end fetchFooter
    
    /**
     * <code>
     * $systemPlugin = $core->getPluginInstance('Jimbo');
     * 
     * $options = array(
     *      'onBind' => array($systemPlugin, 'onBind')
     * );
     * 
     * $systemPlugin->bindRequest($options);
     * </code>
     */
    public function onBind()
    {
        $currentUrl = $this->core->getCurrentURL();
        
        $allowedUrls = array(
            '/login/',
            '/signup/',
            '/forgot/',
            '/forgot/change/',
            '/user/change_password/'
        );
        
        if (
            $this->core->user->isLogin() || 
            in_array($currentUrl, $allowedUrls)
        ) {
            return false;
        }
        
        $params = array();
        
        if (!empty($_SERVER['REQUEST_URI'])) {
            $params['rel'] = urlencode($_SERVER['REQUEST_URI']);
        }
        
        $relUrl = $this->core->getUrl('/login/', $params);
        
        if (isset($_REQUEST['popup'])) {
            $response = new Response(
                Response::JSON_IFRAME, 
                Response::ACTION_ALERT
            );
            
            $response->title = __("Error");
            $response->url = $relUrl;
            $response->addMessage(__("Session expired"));
            $response->send();
            exit();
        }
        
        $this->core->redirect($relUrl, false);
    } // end onBind
    
    public function hasUserPermissionToSection($sectionIdent, $user = null)
    {
        if (is_null($user) && empty($this->core->user)) {
            throw new SystemException("Undefined user");
        }

        if (is_null($user)) {
            $user = &$this->core->user;
        }

        if (!$user->isLogin()) {
            return false;
        }

        if (!array_key_exists($sectionIdent, $this->_userSections)) {
            return false;
        }

        $grantedMask = $this->_userSections[$sectionIdent];
        $expectedMask = $this->_sections[$sectionIdent];

        return intval($grantedMask) >= intval($expectedMask);
    } // end hasUserPermissionToSection
    
    public function text($ident)
    {
        // FIXME: Add cache
        $search = array(
            'ident' => $ident
        );
        
        $text = $this->object->getText($search);
        
        return __($text);
    } // end text
    
    private function _doInstallPlugins()
    {
        require_once 'workbench/Workbench.php';

        Workbench::install();
    } // end _doInstallPlugins
    
    
    public function getResponseModel($call, $regs = array())
    {
        throw new SystemException("Undefined getResponseModel");
    }
    
    /**
     * @override
     */
    public function __isInstalled()
    {
        return $this->object->isInstalled();
    } // end __isInstalled
    
    public function install()
    {
        $this->_doInstallPlugins();
    } // end install
    
    
    public function fetchThemeTitle()
    {
        $data = array(
            'title' => $this->core->user->get('auth_login')
        );
        
        $this->core->fireEvent(Core::EVENT_THEME_TITLE, $data);
        
        return $data['title'];
    } // end fetchThemeTitle
    
    public function __getMenu()
    {
        $menu = array(
            "System" => array(
                "Menu's" => "/festi/festi_menus/Jimbo/",
                "Url's Manager" => "/festi/festi_url_rules/Jimbo/",
                "Plugins" => "/festi/festi_plugins/Jimbo/",
                "Sections" => "/festi/festi_sections/Jimbo/",
                "Environment Variables" => "/festi/festi_settings/Jimbo/",
            )
        );
        
        return $menu;
    } // end __getMenu
    
    /**
     * @store festi_plugins
     * @event Store::EVENT_PREPARE_ACTION_REQUEST
     */
    public function onUploadPlugin(FestiEvent &$event)
    {
        $request = &$event->target['request'];
        
        $path = __DIR__.'/dist/';
        
        if (!is_dir($path) && !mkdir($path)) {
            $msg = "Can't upload the plugin: ".$path;
            throw new SystemException($msg);
        }
        
        if (!is_writeable($path)) {
            $msg = "Please give write permission: ".$path;
            throw new SystemException($msg);
        }
        
        $fileName = $_FILES['plugin']['name'];
        $filePath = $path.$fileName;
        
        $pluginName = pathinfo($fileName, PATHINFO_FILENAME);
        $tmpPluginPath = $path.$pluginName."/";
        if (is_dir($tmpPluginPath)) {
            FestiUtils::removeDir($tmpPluginPath);
        }
        
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $tmpName = $_FILES['plugin']['tmp_name'];
        if (!move_uploaded_file($tmpName, $filePath)) {
            $msg = "Upload Error #603: Please contact plugin provider.";
            throw new SystemException($msg);
        }
        
        $zip = new ZipArchive();
        $res = $zip->open($filePath);
        if ($res === false) {
            $msg = "Upload Error #604: Please contact plugin provider.";
            throw new SystemException($msg);
        } 
        
        $zip->extractTo($path);
        $zip->close();
        
        $pluginsPath = $this->core->getOption('plugins_path');
        $pluginPath = $pluginsPath.$pluginName."/";
        
        if (is_dir($pluginPath)) {
            FestiUtils::removeDir($pluginPath);
        }
        
        if (!rename($tmpPluginPath, $pluginPath)) {
            $msg = "Please give write permission: ".$pluginsPath;
            throw new SystemException($msg);
        }
        
        $action = &$event->target['instance'];
        $fields = &$action->getStore()->getModel()->getFields();
        unset($fields['plugin']);
        
        $request['ident'] = $pluginName; 
        
        return true;
    } // end onUploadPlugin
    
    /**
     * @store festi_plugins
     * @event Store::EVENT_BEFORE_INSERT | Store::EVENT_BEFORE_UPDATE
     */
    public function onInstallPlugin(FestiEvent &$event)
    {
        $event->target['isUpdated'] = true;
        $values = $event->getTargetValueByKey('values');

        if (!array_key_exists('ident', $values) || empty($values['ident'])) {
            throw new SystemException("Undefined plugin name");
        }
        
        $pluginName = $values['ident'];
        $pluginsPath = $this->core->getOption('plugins_path');
        $pluginPath = $pluginsPath.$pluginName."/";
        
        require_once 'workbench/Workbench.php';
        
        $context = new PluginContext($pluginName, $pluginPath);
        
        Workbench::doInstallPlugin($context);
        
        return true;
    } // end onInstallPlugin

    public function setPermissionSection(string $name, int $mask, int $userType = null, array $users = null): int
    {
        $section = $this->object->getSection($name);
        $idSection = false;
        if (!$section) {
            $section = array();
        } else {
            $idSection = $section['id'];
        }

        $section['ident'] = $name;
        $section['mask'] = $mask;

        if ($idSection) {
            $this->object->changeSections($section, $name);
        } else {
            $idSection = $this->object->addSection($section);
        }

        if (!is_null($userType)) {
            $values = array(
                array(
                    'id_section' => $idSection,
                    'id_user_type' => $userType,
                    'value' => $mask
                )
            );

            $this->object->addUserTypesToSection($values);
        }

        if (!is_null($users)) {

            $values = array();
            foreach ($users as $idUser) {
                $values[] = array(
                    'id_section' => $idSection,
                    'id_user' => $idUser,
                    'value' => $mask
                );
            }

            $this->object->addUsersToSection($values);
        }

        return $idSection;
    } // end setPermissionSection
   
}

// @codingStandardsIgnoreStart
function ftext($ident)
{
    $plugin = Core::getInstance()->getPluginInstance('Jimbo');
    
    return $plugin->text($ident);
} // end ftext
// @codingStandardsIgnoreEnd

