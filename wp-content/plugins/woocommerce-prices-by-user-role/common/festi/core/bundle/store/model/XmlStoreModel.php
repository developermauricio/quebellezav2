<?php

require_once 'bundle/store/model/XmlFieldModel.php';

class XmlStoreModel extends StoreModel
{
    /**
     * @return bool
     * @throws StoreException
     * @throws SystemException
     */
    public function load()
    {
        if (!extension_loaded('simplexml')) {
            throw new SystemException("Simplexml extension not loaded");
        }

        $content = $this->_loadContent();

        $parser = $this->_getParser($content);

        $attributes = (array) $parser->attributes();

        $this->_loadAttributes($attributes['@attributes']);

        $this->_loadFields($parser);
        $this->_loadActions($parser);

        $this->doPrepareActions();
        
        $this->_loadRelations($parser, "relations/link");

        $this->_loadAttributeValues(
            $parser, 
            "filters/filter",
            'filters',
            'field',
            'content'
        );
        
        $this->_loadAttributeValues(
            $parser, 
            "search/filter",
            'search',
            'field',
            'content'
        );
        
        $this->_loadAttributeValues(
            $parser, 
            'grouped/item',
            'grouped',
            'type',
            'attributes', 
            $this->getGroupActionFields()
        );
        
        $this->_loadRouters($parser);
        $this->_loadListeners($parser);
        $this->_loadAggregations($parser);
        $this->_loadSections($parser);

        $this->_loadAttributeValues(
            $parser,
            "externalValues/value",
            'externalValues',
            'field',
            'content'
        );
        
        $this->_loadHighlights($parser);

        unset($parser);

        return true;
    } // end load
    
    /**
     * @param string $content
     * @return SimpleXMLElement
     * @throws SystemException
     */
    private function &_getParser(string $content): SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        
        $parser = simplexml_load_string($content);
        if (!$parser) {
            $errors = libxml_get_errors();
            if (!$errors) {
                $msg = "Undefined error parse xml model file";
                throw new SystemException($msg);
            }
            
            foreach ($errors as $error) {
                $msg = "Error parse xml model file: ".$error->message;
                throw new SystemException($msg);
            }
        }
         
        return $parser;
    } // end _getParser
    
    /**
     * @param SimpleXMLElement $parser
     * @throws SystemException
     */
    private function _loadRouters(SimpleXMLElement &$parser)
    {
        $this->_loadAttributeValues(
            $parser, 
            "routers/route",
            'routers'
        );
        
        $routers = $this->routers;
        $this->routers = array();
        
        foreach ($routers as $route) {
            if (!isset($this->routers[$route['store']])) {
                $this->routers[$route['store']] = array();
            }
            $row = &$this->routers[$route['store']];
            $row[$route['joinStore']] = array(
                'type'  => $route['type'],
                'value' => $route['on']
            );
            
            if (!empty($route['joinStoreName'])) {
                $row[$route['joinStore']]['joinName'] = $route['joinStoreName'];
            }
        }
    } // end _loadRouters
    
    /**
     * @param SimpleXMLElement $parser
     * @throws SystemException
     */
    private function _loadListeners(SimpleXMLElement &$parser)
    {
        $this->_loadAttributeValues(
            $parser,
            "listeners/listener",
            'listeners'
        );

        // FIXME:
        foreach ($this->listeners as $listener) {
            if (is_scalar($listener['plugin'])) {
                $plugin = Core::getInstance()->getPluginInstance(
                    $listener['plugin']
                );
            } else {
                $plugin = &$listener['plugin'];
            }

            $method = array(&$plugin, $listener['method']);
            $this->store->addEventListener(
                $listener['event'],
                $method
            );
            unset($plugin);
        }
    } // end _loadListeners
    
    /**
     * @param SimpleXMLElement $parser
     * @return bool
     * @throws StoreException
     * @throws SystemException
     */
    private function _loadAggregations(SimpleXMLElement &$parser)
    {
        $this->_loadAttributeValues(
            $parser,
            "aggregations/aggregation",
            'aggregations'
        );

        $fields = array(
            'field' => array(
                'required' => true,
                'error' =>
                    "Not found required attribute [field] in aggregation",
                'type' => static::FIELD_TYPE_STRING
            ),
            'type' => array(
                'default' => 'sum'
            )
        );

        $result = array();
        foreach ($this->aggregations as $row) {

            $errors = array();
            $data = $this->getPreparedData($row, $fields, $errors);

            if ($errors) {
                $error = reset($errors);
                throw new StoreException($error);
            }

            $result[$data['field']] = $data;
        }

        $this->aggregations = $result;

        return true;
    } // end _loadAggregations
    
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
                __("Undefined store attributes: %s", $filedsError)
            );
        }
        
        $this->setName($this->attributes['name']);
        $this->primaryKey = $this->attributes['primaryKey'];
        $this->charset    = $this->attributes['charset'];

        $this->options[StoreModel::OPTION_FILTERS_MODE] =
            $this->attributes['filter'];
    } // end _loadAttributes
    
    /**
     * Returns xml content.
     * 
     * @throws SystemException
     * @return string
     */
    private function _loadContent(): string
    {
        $path = $this->store->getOption('defs_path');
        $xmlPath = $path.$this->store->getIdent().'.xml';

        if (!is_file($xmlPath)) {
            throw new SystemException("Not found model file: ".$xmlPath);
        }
        
        $vars = $this->store->getOption('handler_options');
        
        $tpl = new Display("");
        $tpl->store = null;
        $tpl->store = &$this->store;
        if ($vars) {
            foreach ($vars as $key => $value) {
                $tpl->assign($key, $value);
            }
        }
        
        return $tpl->fetch($xmlPath);
    } // end _loadContent
    
    /**
     * Load fields from scheme defination
     * 
     * @param SimpleXMLElement $parser
     * @throws SystemException
     * @return boolean
     */
    private function _loadFields(SimpleXMLElement &$parser)
    {
        $fieldsNodes = (array) $parser->xpath("fields/field");
        
        if (!$fieldsNodes) {
            throw new SystemException("Not found fields in model");
        }

        foreach ($fieldsNodes as $index => $xmlField) {
            
            $fieldName = !empty($xmlField['name']) ? $xmlField['name'] : false;
            
            if (empty($xmlField['type'])) {
                throw new SystemException(
                    __("Not found type on field %s", $fieldName)
                );
            }
            
            $field = $this->createFieldInstance($xmlField);

            $fieldModel = new XmlFieldModel($xmlField);
            $field->setIndex($index);
            $field->onInit($fieldModel);

            if (!$field->hasUserPermission()) {
                continue;
            }

            $fieldName = $field->getName();
            if ($fieldName && !isset($this->fields[$fieldName])) {
                $this->fields[$fieldName] = $field;
            } else {
                $this->fields[$index] = $field;
            }
            unset($field);
        }
        unset($fieldsNodes);
        
        return true;
    } // end _loadFields
    
    /**
     * @param SimpleXMLElement $parser
     * @return bool
     * @throws SystemException
     */
    private function _loadHighlights(SimpleXMLElement &$parser)
    {
        $rulesNodes = (array) $parser->xpath("highlights/rule");
        
        if (!$rulesNodes) {
            return false;
        }
        
        $result = array();
        foreach ($rulesNodes as $index => $rule) {
            
            $attr = $rule->attributes();
            if (is_null($attr['cssClass'])) {
                $msg = "Not fount cssClass on highlights rule.";
                throw new SystemException($msg);
            }
            
            $fields = array();
            foreach ($rule->field as $field) {
                $fieldAttr = $field->attributes();
                
                if (is_null($fieldAttr['name'])) {
                    $msg = "Not fount name on field in highlights rule.";
                    throw new SystemException($msg);
                }
                
                $name = (string) $fieldAttr['name'];
                
                $fields[$name] = (string) $field;
            }
            
            $result[] = array(
                'css'    => (string) $attr['cssClass'],
                'fields' => $fields
            );
        }
        
        $this->highlights = $result;
    } // end _loadHighlights
    
    /**
     * Load group of attributes from XML Tree
     *
     * @param SimpleXMLElement $xmlObj XML-Tree Object
     * @param string $path             Path to key
     * @param string $keyName          How to name saved result
     * @param string|null $keyAttr     Name of key attribute
     * @param string $whatToGet        What need to put as value
     * @param array|null $fields
     * @return bool
     * @throws SystemException
     */
    private function _loadAttributeValues(
        SimpleXMLElement &$xmlObj,
        string $path,
        string $keyName,
        string $keyAttr = null,
        string $whatToGet = 'attributes',
        array $fields = null
    ): bool
    {
        $items = $xmlObj->xpath($path);

        $result = array();

        // FIXME:
        if (!empty($items)) {
            $counter = 0;
            foreach ($items as $item) {
                $attributes = $item->attributes();
                if (!$attributes) {
                    continue;
                }

                if ($keyAttr) {
                    $index = (string) $attributes[$keyAttr];
                } else {
                    $index = $counter;
                }


                if ($whatToGet == 'attributes') {
                    foreach ($attributes as $key => $value) {
                        if (strtoupper($this->charset) == 'UTF-8' ||
                            !function_exists('iconv')) {
                            $result[$index][$key] = (string)$value;
                        } else {
                            $result[$index][$key] = html_entity_decode(
                                iconv("UTF-8", $this->charset, (string)$value),
                                ENT_COMPAT, $this->charset
                            );
                        }
                    } // end foreach
                } else if ($whatToGet == 'content') {
                    $key = (string) $attributes[$keyAttr];
                    if (strtoupper($this->charset) == 'UTF-8' ||
                        !function_exists('iconv')) {
                        $result[$index] = (string)$item;
                    } else {
                        $result[$index] = html_entity_decode(
                            iconv("UTF-8", $this->charset, (string)$item),
                            ENT_COMPAT, $this->charset
                        );
                    }
                } // end if

                if ($fields) {
                    $result[$index] = $this->getExtendData(
                        $result[$index], 
                        $fields, 
                        $errors
                    );
                    
                    if ($errors) {
                        list($atrributeName, $message) = each($errors);
                        throw new SystemException($message);
                    }
                }

                $counter++;
            }
        }
        $this->$keyName = $result;

        unset($items);
        unset($result);
        return true;
    } // end _loadAttributeValues
    
    /**
     * @param SimpleXMLElement $xmlObj
     * @param string $path
     * @return bool
     */
    private function _loadRelations(SimpleXMLElement &$xmlObj, string $path): bool
    {
        $items = $xmlObj->xpath($path);

        $result = array();

        if (!empty($items)) {
            foreach ($items as $item) {
                $attributes = $item->attributes();
                if (!isset($attributes['foreignTable']) ||
                    $attributes['foreignTable'] == "") {
                    continue;
                }

                $type = (string) $attributes['type'];
                $foreignTable = (string) $attributes['foreignTable'];
                foreach ($attributes as $key => $value) {
                    $result[$type][$foreignTable][$key] = (string)$value;
                }

            }
        }
        $this->relations = $result;

        unset($items);
        unset($result);
        return true;
    } // end _loadRelations
    
    /**
     * @param SimpleXMLElement $parser
     * @throws SystemException
     */
    private function _loadActions(SimpleXMLElement $parser)
    {
        $this->_loadAttributeValues(
            $parser,
            "actions/action",
            'actions',
            'type',
            'attributes'
        );

        $options = $this->_loadOptions($parser, 'actions');

        $this->loadSectionOptions(
            'actions',
            $options,
            $this->getActionsAttributesOptions()
        );
    } // end _loadActions
    
    /**
     * @param SimpleXMLElement $parser
     * @throws SystemException
     */
    private function _loadSections(SimpleXMLElement $parser)
    {
        $this->_loadAttributeValues(
            $parser,
            "sections/section",
            'sections',
            'name',
            'attributes'
        );

        $options = $this->_loadOptions($parser, 'sections');

        $this->loadSectionOptions(
            'sections',
            $options,
            $this->getSectionsAttributesOptions()
        );
    } // end _loadSections
    
    /**
     * @param SimpleXMLElement $xmlObj
     * @param string $section
     * @return bool|mixed
     */
    private function _loadOptions(SimpleXMLElement &$xmlObj, string $section)
    {
        // Configuration option from actions
        $objects = $xmlObj->xpath($section);
        if (empty($objects)) {
            return false;
        }
        $sectionsObj = array_pop($objects);
        $sections = (array) $sectionsObj->attributes();

        if (!$sections || !array_key_exists('@attributes', $sections)) {
            return false;
        }

        return $sections['@attributes'];
    } // end _loadOptions
}