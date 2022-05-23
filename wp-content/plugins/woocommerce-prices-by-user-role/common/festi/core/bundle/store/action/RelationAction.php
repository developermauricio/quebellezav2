<?php

class RelationAction extends AbstractAction
{
    private $_relationStoreName;

    private $_relationModel;

    protected $session;

    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {
        $this->session = &$this->store->getSession();

        $data = $this->getDataFromRequest();

        $relation = $this->_getRelation($data);

        $relationStore = new Store(
            $this->store->getConnection(),
            $relation['foreignTable'],
            $this->store->getOptions()
        );

        $this->_relationModel = $relationStore->getModel();

        $this->_relationStoreName = $relationStore->getIdent();

        if ($relation['type'] == Store::RELATION_TYPE_CHILD) {
            $this->_doChildRelation($relation);
        } else if ($relation['type'] == Store::RELATION_TYPE_PARENT) {
            $this->_doParentRelation($relation);
        }

        $core = Core::getInstance();

        // FIXME:
        $params = array();
        if (Response::isAjaxRequest()) {
            $params[Response::REQUEST_KEY_AJAX_MODE] = 'true';
        }

        $storeName = $relationStore->getIdent();

        $options = $relationStore->getOption('handler_options');

        $url = $this->_getRedirectUrl($storeName, $relation, $options);

        $redirectUrl = Core::getInstance()->getUrl($url, $params);
        $response->url = $redirectUrl;
        $response->setAction(Response::ACTION_REDIRECT);

        return true;
    } // end onStart

    private function _getRedirectUrl($storeName, $relation, $options)
    {
        if (empty($relation['url'])) {
            $currentUrl = $this->store->getOption('current_url');
            
            $regExp = "#^/festi/#Umis";
            if (preg_match($regExp, $currentUrl)) {
                
                $url = "/festi/".$storeName."/";
                if (!empty($options['plugin'])) {
                    $url .= $options['plugin']."/";
                }

            } else {
                $url = $this->getUrl();
            }
            
        } else {
            $url = $relation['url'];
        }

        $primaryKeyValue = $this->store->getPrimaryKeyValueFromRequest();
        $vars = array(
            Store::PRIMARY_KEY_IN_REQUEST => $primaryKeyValue
        );

        $url = self::fillString($url, $vars);

        return $url;
    } // end _getRedirectUrl

    private function _doParentRelation($relation)
    {
        if ($this->store->hasSessionValue(Store::PARENT_CAPTION_IN_SESSION)) {
            $caption = $this->store->getSessionValue(
                Store::PARENT_CAPTION_IN_SESSION
            );
            $pos = strrpos($caption, '/');

            if ($pos === false) {
                $caption = '';
            } else {
                $caption = substr($caption, 0, $pos);
            }

            $this->store->setSessionValue(
                Store::PARENT_CAPTION_IN_SESSION,
                $caption,
                $this->_relationStoreName
            );
        }
        
        $parentValue = null;
        if ($this->_isParentRelation($relation)) {
            $primaryKeyValue = $this->store->getSessionValue(
                Store::PARENT_PRIMARY_KEY_IN_SESSION
            );
            $data = $this->store->loadRowByPrimaryKey($primaryKeyValue);
            
            $field = $this->model->getField($relation['field']);
            if ($field && $field->getType() == 'foreignKey') {
                $parentValueKey = '_foreign_'.$relation['field'];
            } else {
                $parentValueKey = $relation['field'];
            }

            if (isset($data[$parentValueKey])) {
                $parentValue = $data[$parentValueKey];
            }

        }

        $this->store->setSessionValue(
            Store::PARENT_PRIMARY_KEY_IN_SESSION,
            $parentValue
        );

        return true;
    } // end _doParentRelation

    private function _isParentRelation($relation): bool
    {
        $hasPrimaryKeyInSession = $this->store->hasSessionValue(
            Store::PARENT_PRIMARY_KEY_IN_SESSION
        );
        return $hasPrimaryKeyInSession && 
               $this->store->getIdent() == $relation['foreignTable'];
    }

    private function _doChildRelation($relation)
    {
        $id = $this->store->getPrimaryKeyValueFromRequest();

        $rowData = $this->store->loadRowByPrimaryKey($id);

        $this->_setParentTableRowForChild(
            $this->_relationStoreName,
            $rowData
        );

        // FIXME: If open two tab with table in browser and modify we have bug
        $this->store->setSessionValue(
            Store::PARENT_PRIMARY_KEY_IN_SESSION, 
            $rowData[$relation['field']],
            $this->_relationStoreName
        );
        
        $captionValue = null;
        if (!empty($relation['treeCaption'])) {
            $field = $this->model->getField($relation['treeCaption']);

            if (!$field) {
                $tableName = $this->store->getIdent();
                $msg = 'Not found column "'.$relation['treeCaption'].'"'.
                       " in ".$tableName." for treeCaption";
                throw new SystemException($msg);
            }

            if ($field->getType() == 'foreignKey') {
                $this->store->loadForeignKeyValues($field);
                $caption = $field->keyData[$rowData[$relation['treeCaption']]];
            } else {
                $caption = $rowData[$relation['treeCaption']];
            }

            if ($this->store->hasSessionValue(Store::PARENT_CAPTION_IN_SESSION)) {
                $mainCaption = $this->store->getSessionValue(
                    Store::PARENT_CAPTION_IN_SESSION
                );
                $caption = $mainCaption.' / '.$caption;
            }
            
            $captionValue = $caption;
        }

        $this->store->setSessionValue(
            'caption',
            $captionValue,
            $this->_relationStoreName
        );

        return true;
    } // end _doChildRelation

    /**
     * Save parent table row values
     *
     * @param string $childTableName
     * @param array $values
     */
    private function _setParentTableRowForChild($childTableName, $values): void
    {
        $this->store->setSessionValue(
            Store::PARENT_ROW_VALUES_IN_SESSION, 
            $values, 
            $childTableName
        );
    }

    private function _getRelation($data)
    {
        $action = $this->model->getAction($data['action']);

        $relations = $this->model->getRelation($action['type']);

        if (!$relations) {
            $relations = $this->model->getRelation($action['relationType']);
        }

        if (!$relations) {
            $msg = "Not found relations for action ".$action['type'];
            throw new SystemException($msg);
        }

        if (!isset($relations[$action['relation']])) {
            $msg = "Not found relation ".$action['relation'];
            throw new SystemException($msg);
        }

        $relation = $relations[$action['relation']];

        if (!is_null($action['link'])) {
            $relation['url'] = $action['link'];
        }

        return $relation;
    } // end _getRelation

    protected function getRequestFields(): ?array 
    {
        $requestFields = array(
           'action' => array(
                'type'     => self::FIELD_TYPE_STRING_NULL,
                'required' => true
            )
        );

        return $requestFields;
    } // end getRequestFields
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_RELATION;
    }

    /**
     * @override
     */
    public function isExec()
    {
        return true;
    } // end isExec

}
