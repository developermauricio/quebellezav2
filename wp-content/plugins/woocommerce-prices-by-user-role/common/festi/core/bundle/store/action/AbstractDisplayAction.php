<?php

abstract class AbstractDisplayAction extends AbstractAction
{
    /**
     * Returns preparent sql action with html
     *
     * @param array $action
     * @param array|null $rowData
     * @return array
     * @throws SystemException
     */
    protected function getActionInfoForListDataItem(
        array $action, array $rowData = null
    ): array
    {
        $className = FestiUtils::convertToCamelCase($action['type']).'Action';
        
        if (!class_exists($className)) {
            $classPath = dirname(__FILE__).DIRECTORY_SEPARATOR.
                         $className.".php";
            if (file_exists($classPath)) {
                require_once $classPath;
            }
        }
        
        if (class_exists($className)) {
            $actionInstance = new $className($this->store);
            if (method_exists($actionInstance, 'getPreparedAction')) {
                return $actionInstance->getPreparedAction($action, $rowData);
            }
        }
        
        return $this->getPreparedDefaultAction($action, $rowData);
    } // end getActionInfoForListDataItem
    
    /**
     * @param array $action
     * @param array|null $rowData
     * @return array
     * @throws SystemException
     */
    protected function getPreparedDefaultAction(
        array $action, array $rowData = null
    ): array
    {
        Core::getInstance()->fireEvent(
            Store::EVENT_PREPARE_ACTION,
            $action
        );
        
        $view = $this->store->getView();
        
        $isGeneralAction = ($rowData === null);
        
        if (array_key_exists('link', $action) && !$action['link']) {
            $params = array(
                'action' => $action['type']
            );
        
            if (!$isGeneralAction) {
                $key = Store::PRIMARY_KEY_IN_REQUEST;
                $primaryKey = $this->store->getPrimaryKey();
                $params[$key] = isset($rowData[$primaryKey]) ? $rowData[$primaryKey] : null;
            }
        
            $action['link'] = $this->getUrl($params);
        }
        
        $action['link'] = $view->fillString($action['link'], $rowData);
        
        if (!$isGeneralAction && !$action['src']) {
            $action['src'] = $this->store->getOption('http_base_icon').
            'dbadmin_'.$action['type'].'.gif';
        }
        
        if ($action['mode'] == Store::ACTION_VIEW_MODE_NEW) {
            $action['js'] = null;
        } else {
            if (!empty($action['link']) && strpos($action['link'], '?') === false) {
                $action['link'] .= '?'.Response::REQUEST_KEY_AJAX_MODE.'=true';
            } else if (!empty($action['link'])) {
                $action['link'] .= '&'.Response::REQUEST_KEY_AJAX_MODE.'=true';
            }
        }
        
        if ($action['js']) {
            $action['mode'] = 'js';
            $action['js'] = $view->fillString($action['js'], $rowData);
        }
        
        $attributes = $this->_getActionAttributes($action);
        
        $action['attributes'] = $attributes;
        
        $data = array(
            'info' => $action
        );
        
        if ($action['html']) {
            $action['html'] = $view->fillString($action['html'], $rowData);
        } else {
            $action['html'] = $view->fetch('list_action.php', $data);
        }
        
        return $action;
    } // end getPreparedDefaultAction
    
    /**
     * @param array $action
     * @return string
     */
    private function _getActionAttributes(array $action): string
    {
        $attributes = array();
        
        if (
            $action['confirmDialog'] === true ||
            $action['confirmDialog'] === 'true'
        ) {
            $attributes['data-dialog'] = 'true';
            if (!empty($action['dialogTitle'])) {
                $attributes['data-dialog-title'] = $action['dialogTitle'];
            }

            if (!empty($action['dialogMessage'])) {
                $attributes['data-dialog-message'] = $action['dialogMessage'];
            }
        }
        
        $attributes['data-action-name'] = $action['type'];
        
        $result = "";
        foreach ($attributes as $key => $value) {
            $result .= $key.'="'.htmlspecialchars($value).'" ';
        }
        
        return $result;
    } // end _getActionAttributes
    
    /**
     * @param $value
     * @param AbstractField $field
     * @param $primaryKeyValue
     * @param bool $row
     * @return string
     * @throws SystemException
     */
    protected function getValueWithLink(
        $value, AbstractField $field, $primaryKeyValue, $row = false
    )
    {
        $child = $this->model->getAction(
            Store::ACTION_CHILD
        );
        
        $action = false;
        $mode = false;
        if ($field->get('clicable') === 'info') {
            $action = "info";
        } else if ($child) {
            $action = Store::ACTION_CHILD;
            $mode = Store::ACTION_VIEW_MODE_NEW;
        }
        
        if (!$action) {
            return $value;
        }
        
        $params = array(
            'action' => $action,
            Store::PRIMARY_KEY_IN_REQUEST => $primaryKeyValue
        );
        
        $url = $this->getUrl($params);
        
        $data = array(
            'url'             => $url,
            'value'           => $value,
            'field'           => $field,
            'primaryKeyValue' => $primaryKeyValue,
            'row'             => $row,
            'mode'            => $mode,
            'target'          => '_self'
        );
        
        $event = new FestiEvent(
            Store::EVENT_ON_FETCH_LIST_CELL_VALUE_WITH_LINK, 
            $data
        );
        
        $this->store->dispatchEvent($event);
        
        $value = '<a href="'.$data['url'].'" mode="'.$data['mode'].'" '.
                 'target="'.$data['target'].'"'.
                 'class="e-db-action e-db-list-row-action">'.$data['value'].
                 '</a>';
        
        return $value;
    } // end getValueWithLink
    
    /**
     * @param AbstractField $field
     * @param string|null $value
     * @param bool|array $row
     * @return false|string
     * @throws SystemException
     */
    protected function getCellValue(AbstractField $field, ?string $value, $row = false)
    {
        $displayValue = $field->displayValue($value, $row);
        $hasLink      = $field->get('clicable');
        $url          = $field->get('url');
        
        $viewHandler = $field->get('cellViewHandler');
        
        if ($viewHandler && $viewHandler == Store::CELL_VIEW_HANDLER_CUSTOM) {
            $plugin = Core::getInstance()->getPluginInstance(
                $field->get('plugin')
            );
            
            $method = $field->get('method');
            
            $displayValue = $plugin->$method($field, $value, $row);
        }
        
        if ($hasLink) {
            $displayValue = $this->getValueWithLink(
                $displayValue, 
                $field, 
                $this->primaryKeyValue,
                $row
            );
        } else if ($url) {
            $view = $this->store->getView();
            $url = $view->fillString($url, $row);
            $displayValue = '<a href="'.$url.'">'.$displayValue.'</a>';
        }
        
        return $displayValue;
    } // end getCellValue
    
    /**
     * @param array $action
     * @return bool
     */
    protected function isActionWithAjaxResponse(array $action): bool
    {
        return $action['mode'] != Store::ACTION_VIEW_MODE_NEW;
    } // end isActionWithAjaxResponse
}

