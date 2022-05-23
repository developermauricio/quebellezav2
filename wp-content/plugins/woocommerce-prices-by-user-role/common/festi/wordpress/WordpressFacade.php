<?php

class WordpressFacade extends EngineFacade
{
    const META_QUERY_KEY = 'meta_query';
    const QUERY_CLASS_NAME = 'WP_Query';

    public function getAttachmentsByPostID($postParent, $fileType)
    {
        $attachmentQuery = array(
            'numberposts' => -1,
            'post_status' => 'inherit',
            'post_type' => 'attachment',
            'post_parent' => $postParent,
            'post_mime_type' => $fileType
        );

        return get_posts($attachmentQuery);
    } // end getAttachmentsByPostID
    
    public function getAbsolutePath($url)
    {
        return str_replace(home_url('/'), ABSPATH, $url);
    } // end getAbsolutePath
    
    public function addAttachment($idPost, $attachment, $path)
    {
        return wp_insert_attachment($attachment, $path, $idPost);
    } // end addAttachment
    
    public function getPluginData($pluginPath)
    {
        return get_plugin_data($pluginPath);    
    } // end getPluginData

    public static function createQueryInstance($params = array())
    {
        return new WP_Query($params);
    } // end createQueryInstance
    
    public function onRemoveAllActions($hookName)
    {
        return remove_all_actions($hookName);
    } // end onRemoveAllActions
    
    public function deleteOption($optionName)
    {
        return delete_option($optionName);
    } // end deleteOption

    public function getQueryVars(WP_Query $query)
    {
        return $query->query_vars;
    } // end getQueryVars

    public function getProductType($idPost)
    {
        return $this->getPostMeta($idPost, '_product_type', true);
    } // end getProductType

    public function getCurrentPostID()
    {
        return get_the_ID();
    } // end getCurrentPostID

    public function registerActivationHook(
        $callback,
        $pluginMainFilePath
    )
    {
        register_activation_hook(
            $pluginMainFilePath,
            $callback
        );
    } // end registerActivationHook

    public function registerDeactivationHook(
        $callback,
        $pluginMainFilePath
    )
    {
        register_deactivation_hook(
            $pluginMainFilePath,
            $callback
        );
    } // end registerDeactivationHook

    public function updateOption($optionName, $value, $autoload = null)
    {
        return update_option($optionName, $value, $autoload);
    } // end updateOption

    public function getOption($optionName, $default = false )
    {
        return get_option($optionName, $default);
    } // end getOption

    public function updatePostMeta(
        $idPost,
        $metaKey,
        $metaValue,
        $previousValue = ''
    )
    {
        return update_post_meta($idPost, $metaKey, $metaValue, $previousValue);
    } // end updatePostMeta

    public function getPostMeta($idPost, $metaKey = '', $single = false)
    {
        return get_post_meta($idPost, $metaKey, $single);
    } // end getPostMeta

    public function isCurrentUserCan($capability, $args = null)
    {
        return current_user_can($capability, $args);
    } // end isCurrentUserCan

    public function dispatchAction($actionName)
    {
        if (!is_array($actionName)) {
            $params = array($actionName);
        } else {
            $params = $actionName;
        }

        $args = func_get_args();

        array_shift($args);

        $params = array_merge($params, $args);

        return call_user_func_array('do_action', $params);
    } // end dispatchAction

    public function dispatchFilter($filterName, &$value)
    {
        $params = array(
            $filterName,
            $value
        );

        $args = func_get_args();

        $args = array_slice($args, 2);

        $params = array_merge($params, $args);

        $result = call_user_func_array('apply_filters', $params);

        if (class_exists('Core')) {
            Core::getInstance()->fireEvent($filterName, $result);
        }

        return $result;
    } // end dispatchFilter

    public function addActionListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        add_action($hook, $method, $priority, $acceptedArgs);
    } // end addActionListener

    public function addFilterListener(
        $hook, $method, $priority = 10, $acceptedArgs = 1
    )
    {
        add_filter($hook, $method, $priority, $acceptedArgs);
    } // end addFilterListener

    protected function getIdent()
    {
        return 'wordpress';
    } // end getIdent

    public function setObjectTerms($idPost, $terms, $taxonomy, $append = false)
    {
        wp_set_object_terms($idPost, $terms, $taxonomy, $append);
    } // end setObjectTerms

    public function addPostMeta($idPost, $metaKey, $metaValue, $unique = false)
    {
        add_post_meta($idPost, $metaKey, $metaValue, $unique);
    } // end addPostMeta

    public function doInsertPost($postData, $error = false)
    {
        return wp_insert_post($postData, $error);
    } // end doInsertPost

    public function doUpdatePost($postData, $error = false)
    {
        return wp_update_post($postData, $error);
    } // end doUpdatePost

    public function getPostStatus($idPost = false)
    {
        return get_post_status($idPost);
    } // end getPostStatus

    public function getActiveThemeName()
    {
        $theme = wp_get_theme();

        return $theme->name;
    } // end getActiveThemeName

    public function doEnqueueScript(
        $handle,
        $source = '',
        $dependsOn = array(),
        $version = false,
        $inFooter = false
    )
    {
        wp_enqueue_script($handle, $source, $dependsOn, $version, $inFooter);
    } // end doEnqueueScript

    public function doEnqueueStyle(
        $handle,
        $source = '',
        $dependsOn = array(),
        $version = false,
        $media = 'all'
    )
    {
        wp_enqueue_style($handle, $source, $dependsOn, $version, $media);
    } // end doEnqueueStyle

    public function addUserRole($role, $displayName, $capabilities = array())
    {
        return add_role($role, $displayName, $capabilities);
    } // end addUserRole

    public function getWordpressPostInstance()
    {
        if ($this->_hasPostInGlobals()) {
            return $GLOBALS['post'];
        }

        return null;
    } // end getWordpressPostInstance

    private function _hasPostInGlobals()
    {
        return array_key_exists('post', $GLOBALS);
    } // end _hasPostInGlobals

    public function getSiteUrl($idBlog = null, $path = '', $scheme = null)
    {
        return get_site_url($idBlog, $path, $scheme);
    } // end getSiteUrl

    public function getPluginsUrl($path = '', $plugin = '')
    {
        return plugins_url($path, $plugin);
    } // end getPluginsUrl

    public function getPluginBaseName($filePath)
    {
        return plugin_basename($filePath);
    } // end getPluginBaseName

    public function getPluginBasePath($filePath)
    {
        return plugin_dir_path($filePath);
    } // getPluginBasePath

    public function isMultiSiteOptionOn()
    {
        return is_multisite();
    } // isMultiSiteOptionOn

    public function getMainSiteOption($option, $default = false)
    {
        return get_network_option(null, $option, $default);
    } // getMainSiteOption

    public function getAdminUrl($path = '', $scheme = 'admin')
    {
        return admin_url($path, $scheme);
    } // end getAdminUrl()

    public function addShortCode($tag, $method)
    {
        add_shortcode($tag, $method);
    } // end addShortCode

    public function doLocalizeScript($handle, $objectName, $vars)
    {
        wp_localize_script($handle, $objectName, $vars);
    } // end doLocalizeScript

    public function setTransient($transient, $value, $expiration = 0)
    {
        set_transient($transient, $value, $expiration);
    } // end setTransient

    public function getTransient($transient)
    {
        return get_transient($transient);
    } // end getTransient

    public function doSendJson($response, $statusCode = null)
    {
        wp_send_json($response, $statusCode);
    } // end doSendJson

    public function isAdminPanel()
    {
        return is_admin() || is_blog_admin();
    } // end isAdminPanel

    public function isTermExists($term, $taxonomy = '', $parent = null)
    {
        return term_exists($term, $taxonomy, $parent);
    } // end isTermExists

    public function doInsertTerm($term, $taxonomy, $args = array())
    {
        return wp_insert_term($term, $taxonomy, $args);
    } // end doInsertTerm

    public function doSanitizeTitle(
        $title,
        $fallbackTitle = '',
        $context = 'save'
    )
    {
        return sanitize_title($title, $fallbackTitle, $context);
    } // end doSanitizeTitle

    public function doGenerateAttachmentMetaData($idAttachment, $file)
    {
        return wp_generate_attachment_metadata($idAttachment, $file);
    } // end doGenerateAttachmentMetaData

    public function updateAttachmentMetadata($idAttachment, $data)
    {
        wp_update_attachment_metadata($idAttachment, $data);
    } // end updateAttachmentMetadata

    public function isTestEnvironmentDefined()
    {
        return defined('WP_TESTS_TABLE_PREFIX');
    } // end isTestEnvironmentDefined

    public function getPostTermsByPostID(
        $idPost,
        $taxonomy,
        $args = array('fields' => 'all')
    )
    {
        return wp_get_post_terms($idPost, $taxonomy, $args);
    } // end getPostTermsByPostID

    public function hasSearchParamsInQuery(WP_Query $object)
    {
        return array_key_exists('s', $object->query) &&
               $object->query['s'];
    } // end hasSearchParamsInQuery

    public function isAjax()
    {
        return defined('DOING_AJAX') && DOING_AJAX;
    } // end isAjax

    public function getCurrentUserID()
    {
        return get_current_user_id();
    } // end getCurrentUserID

    public function getDefaultFileSystemInstance($args = false)
    {
        $includesPath = ABSPATH.'wp-admin/includes/';

        $methodName = static::DEFAULT_FILE_SYSTEM_METHOD;

        require_once $includesPath.'class-wp-filesystem-base.php';
        require_once $includesPath."class-wp-filesystem-{$methodName}.php";

        $className = 'WP_Filesystem_'.$methodName;

        return new $className($args);
    } // end getDefaultFileSystemInstance

    public function initFileSystem(
        $args = false,
        $context = false,
        $allowRelaxedFileOwnership = false
    )
    {
        WP_Filesystem($args, $context, $allowRelaxedFileOwnership);
    } // end initFileSystem

    public function addAdminMenuPage(
        $pageTitle,
        $menuTitle,
        $capability,
        $menuSlug,
        $function = false,
        $iconUrl = false,
        $position = null
    )
    {
        return add_menu_page(
            $pageTitle,
            $menuTitle,
            $capability,
            $menuSlug,
            $function,
            $iconUrl,
            $position
        );
    } // end addAdminMenuPage

    public function addSubmenuPage(
        $parentSlug,
        $pageTitle,
        $capability,
        $menuSlug,
        $function = false,
        $position = null
    )
    {
        return add_submenu_page(
            $parentSlug,
            $pageTitle,
            $capability,
            $menuSlug,
            $function,
            $position
        );
    } // end addSubmenuPage

    public function isRestApiRequest()
    {
        return !empty($_SERVER['REQUEST_URI']) &&
               strpos($_SERVER['REQUEST_URI'], 'wp-json') !== false;
    } // end isRestApiRequest

    public function deleteTransient($name)
    {
        delete_transient($name);
    } // end deleteTransient

    public function isAdministrationInterfaceRequest()
    {
        return is_admin();
    } // end isAdministrationInterfaceRequest
}