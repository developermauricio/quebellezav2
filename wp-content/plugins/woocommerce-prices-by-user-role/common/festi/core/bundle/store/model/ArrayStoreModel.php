<?php
require_once 'bundle/store/model/ArrayFieldModel.php';

class ArrayStoreModel extends StoreModel
{
    public function load()
    {
        $model = $this->store->getOption('model');

        $this->_loadAttributes($model['table']);
        
        if (!empty($model['fields'])) {
            $this->_loadFields($model['fields']);
        }

        if (!empty($model['routers'])) {
            $this->routers = $model['routers'];
        }

        if (!empty($model['relations'])) {
            $this->_loadRelations($model['relations']);
        }

        if (!empty($model['search'])) {
            $this->search = $model['search'];
        }

        if (!empty($model['sections'])) {
            $this->sections = $model['sections'];
        }

        if (!empty($model['filters'])) {
            $this->filters = $model['filters'];
        }

        if (!empty($model['aggregations'])) {
            $this->aggregations = $model['aggregations'];
        }
        
        if (!empty($model['externalValues'])) {
            $this->externalValues = $model['externalValues'];
        }
        
        $this->actions = $model['actions'];

        $this->loadSectionOptions(
            'actions',
            $this->_getSectionAttributes($this->actions),
            $this->getActionsAttributesOptions()
        );

        $this->doPrepareActions();

        $this->loadSectionOptions(
            'sections',
            $this->_getSectionAttributes($this->sections),
            $this->getSectionsAttributesOptions()
        );
    } // end load

    private function _getSectionAttributes(&$section)
    {
        if (empty($section)) {
            return false;
        }

        if (!array_key_exists('@attributes', $section)) {
            return false;
        }

        $attributes = $section['@attributes'];
        unset($section['@attributes']);

        return $attributes;
    } // end _getSectionAttributes
    
    /**
     * @param array $values
     * @throws SystemException
     */
    private function _loadAttributes(array $values)
    {
        $this->attributes = $this->getExtendData(
            $values, 
            $this->getAttributesOptions(),
            $errors
        );

        if ($errors) {
            $filedsError = join(', ', array_keys($errors));
            throw new SystemException(
                "Undefined store attributes: ".$filedsError
            );
        }
        
        $this->setName($this->attributes['name']);
        $this->primaryKey = $this->attributes['primaryKey'];
        $this->charset    = $this->attributes['charset'];

        $this->options[StoreModel::OPTION_FILTERS_MODE] =
            $this->attributes['filter'];
    } // end _loadAttributes
    
    /**
     * @override
     */
    protected function getAttributesOptions()
    {
        $attributes = parent::getAttributesOptions();
        unset($attributes['plugin']);
        
        return $attributes;
    } // end getAttributesOptions
    
    /**
     * Load fields from scheme defination
     * 
     * @param object $storage
     * @throws SystemException
     * @return boolean
     */
    protected function _loadFields(&$storage)
    {
        foreach ($storage as $index => $xmlField) {
            
            $fieldName = !empty($xmlField['name']) ? $xmlField['name'] : false;

            if (empty($xmlField['type'])) {
                throw new SystemException(
                    "Not found type on field ".$fieldName
                );
            }

            $field = $this->createFieldInstance($xmlField);
            
            $fieldModel = new ArrayFieldModel($xmlField);
            $field->setIndex($index);
            
            $field->onInit($fieldModel);

            if (!$field->hasUserPermission()) {
                continue;
            }
            
            $fieldName = $field->getName();
            if ($fieldName && !isset($this->fields[$fieldName])) {
                $this->fields[$fieldName] = $field;
            } else {
                $this->fields[] = $field;
            }
            unset($field);
        }
        unset($fieldModel);
        
        return true;
    } // end loadFields
    
    /**
     * @param array $items
     * @return bool
     */
    private function _loadRelations(array &$items)
    {
        $result = array();

        foreach ($items as $item) {
            if (!isset($item['foreignTable']) ||
                $item['foreignTable'] == "") {
                continue;
            }

            $type = $item['type'];
            $foreignTable = $item['foreignTable'];
            $result[$type][$foreignTable] = $item;
        }

        $this->relations = $result;

        unset($result);

        return true;
    } // end _loadRelations
}
