<?php

class PluginAnnotations
{
    private $_context;
    private $_annotations;
    
    public function __construct(PluginContext $context)
    {
        $this->_context = $context;
    } // end __construct
    
    public function parse(array $externalAnnotations = array())
    {
        $plugin = $this->_context->getInstance();
        $class = new ReflectionClass($plugin);
        $methods = $class->getMethods();
        
        
        $annotations = array_merge($this->getAnnotationsForMethods(), $externalAnnotations);
        $regExp = "#@(".join("|", $annotations).")(.*)\n#Umis";
        
        foreach ($methods as $method) {
            $comment = $method->getDocComment();
            if (!$comment) {
                continue;
            }

            $descriptionRegExp = "#([^@]+)@#Umis";
            $description = false;
            if (preg_match($descriptionRegExp, $comment, $descriptionMatch)) {
                $description = trim(str_replace(array("/**", "*"), "", $descriptionMatch[1]));
            }

            $res = preg_match_all($regExp, $comment, $matches);
            if (!$res) {
                continue;
            }
            
            $this->_doAnnotationsMapper($method, $matches, $description);
        }
        
        return $this->_annotations;
    } // end parse
    
    private function _doAnnotationsMapper($method, $matches, $description)
    {
        $this->_annotations['methods'][$method->name] = array(
            'description' => $description
        );

        $methodInfo = &$this->_annotations['methods'][$method->name];
        $annotation = &$this->_annotations['annotations'];
        
        foreach ($matches[1] as $index => $annotationName) {
            if (!isset($methodInfo[$annotationName])) {
                $methodInfo[$annotationName] = array();
            }
            
            $methodInfo[$annotationName][] = $matches[2][$index];
            $annotation[$annotationName][$method->name] = $method->name;
        }
    } // end _doAnnotationsMapper
    
    protected function getAnnotationsForMethods()
    {
        return array(
            'area',
            'urlRule',
            'section',
            //'interceptor',
            //'listener',
            //'prepare'
        ); 
    } // end getAnnotationsForMethods
    
    public function sync(ISystemObject $object)
    {
        if (!$this->_annotations) {
            return true;
        }
        
        $annotations = $this->getAnnotationsForMethods();
        
        foreach ($annotations as $name) {
            if (!$this->_hasAnnotation($name)) {
                continue;
            }
            
            $methodName = "_on".ucfirst($name).'MethodAnnotation';
            //echo $methodName."<hr />";
            $this->$methodName($object);
        }
    } // end sync
    
    private function _hasAnnotation($name)
    {
        return !empty($this->_annotations['annotations'][$name]);    
    } // end _hasAnnotation
    
    private function _getAnnotationMethods($name)
    {
        if (empty($this->_annotations['annotations'][$name])) {
            return array();
        }
        
        return $this->_annotations['annotations'][$name];
    } // end getAnnotations
    
    private function _onAreaMethodAnnotation($object)
    {
        $areas = $object->getUrlAreas();
        $values = $this->_getMethodsAnnotationValues("area");
        $values = array_unique($values);
        
        $newAreas = array_diff($values, $areas);
        
        if ($newAreas) {
            $object->addUrlAreas($newAreas);    
        }
        
        return true;
    } // end _onAreaMethodAnnotation
    
    private function _onUrlRuleMethodAnnotation($object)
    {
        $methods = $this->_getAnnotationMethods("urlRule");
        
        $search = array(
            'plugin' => $this->_context->getName()
        );
        
        $existsRules = $object->searchUrlRules($search);
        $existsUrls = array();
        foreach ($existsRules as $rule) {
            $existsUrls[$rule['pattern']] = $rule['pattern'];
        }
        
        
        $result = array();
        foreach ($methods as $methodName) {
            $values = $this->_getMethodAnnotationValues($methodName, "urlRule");
            foreach ($values as $value) {
                // TODO: Add update logic
                if (array_key_exists($value, $existsUrls)) {
                    continue;
                }
                
                $result[$value] = array(
                    'method' => $methodName,
                    'areas' => $this->_getMethodAnnotationValues(
                        $methodName, 
                        "area"
                    )
                );
            }
        }
        
        // FIXME:
        foreach ($result as $pattern => $data) {
            $values = array(
                'plugin'  => $this->_context->getName(),
                'pattern' => $pattern,
                'method'  => $data['method']
            );
            
            $idRule = $object->addUrlRule($values);
            
            $areaValues = array();
            foreach ($data['areas'] as $area) {
                $areaValues[] = array(
                    'id_url_rule' => $idRule,
                    'area'        => $area
                );
            }
            
            if ($areaValues) {
                $object->addUrlRulesToAreas($areaValues);
            }
        }
    } // end _onUrlRuleMethodAnnotation
    
    private function _onSectionMethodAnnotation($object)
    {
        $methods = $this->_getAnnotationMethods("section");
        
        foreach ($methods as $methodName) {
            $values = $this->_getMethodAnnotationValues($methodName, "section");
            
            foreach ($values as $value) {
                $this->_doUpdateSectionMethodAnnotation(
                    $methodName, 
                    $value, 
                    $object
                );
            }
        }
        
        return true;
    } // end _onSectionMethodAnnotation
    
    private function _doUpdateSectionMethodAnnotation(
        $methodName, $value, $object
    )
    {
        $options = $this->_parseSectionAnnotationValue($value);
        
        $section = $object->getSection($options['name']);
        
        $values = array(
            'ident' => $options['name'],
            'mask'  => $options['mask']
        );
            
        if (!$section) {
            $section = $values;
            $section['id'] = $object->addSection($values);
        }
        
        //
        $actionValues = array(
            'id_section' => $section['id'],
            'plugin'     => $this->_context->getName(),
            'method'     => $methodName,
            'mask'       => $options['mask']
        );
        
        $search = array(
            'id_section' => $section['id'],
            'plugin'     => $this->_context->getName(),
            'method'     => $methodName
        );
        
        $action = $object->getSectionAction($search);
        if (!$action) {
            $action = $actionValues;
            $action['id'] = $object->addSectionAction($actionValues);
        } else {
            $object->changeSectionAction($actionValues, $search);
        }
        
    } // end _doUpdateSectionMethodAnnotation
    
    private function _parseSectionAnnotationValue($value)
    {
        $options = array();
        
        $chunks = explode("<", $value);
        $section = $chunks[0];
        $section = explode("|", $section);
        
        $options['name'] = trim($section[0]);
        if (empty($section[1])) {
            $options['mask'] = 'exec';
        } else {
            $options['mask'] = trim($section[1]);
        }
        
        $options['mask'] = $this->_getSectionMaskValue($options['mask']);
        
        /*
        if (!empty($chunks[1])) {
            $userTypesChunks = explode(",", str_replace(">", "", $chunks[1]));
            foreach ($userTypesChunks as $chunk) {
                $chunk = explode("|", $chunk);
                $mask = empty($chunk[1]) ? "exec" : trim($chunk[1]);
                $userType = trim($chunk[0]);
                $options['userTypes'][$userType] = $this->_getSectionMaskValue(
                    $mask
                );
            }
        }*/
        
        return $options;
    } // end _parseSectionAnnotationValue
    
    private function _getSectionMaskValue($key)
    {
        $maskValues = array(
            'write' => 4,
            'read' => 2,
            'exec' => 6
        );
        
        if (array_key_exists($key, $maskValues)) {
            return $maskValues[$key];
        }
        
        return $maskValues['exec'];
    } // end _getSectionMaskValue
    
    
    private function _getMethodsAnnotationValues($name)
    {
        $methods = $this->_getAnnotationMethods($name);
        
        $result = array();
        foreach ($methods as $method) {
            $values = $this->_getMethodAnnotationValues($method, $name);
            $values = array_map('trim', array_filter($values));
            $result = array_merge($result, $values);
        }
        
        return $result;
    } // end _getMethodAnnotationValues
    
    private function _hasAnnotationInMethod($methodName, $annotationName)
    {
        return !empty(
            $this->_annotations['methods'][$methodName][$annotationName]
        );
    } // end _hasAnnotationInMethod
    
    private function _getMethodAnnotationValues($methodName, $annotationName)
    {
        if (!$this->_hasAnnotationInMethod($methodName, $annotationName)) {
            return false;
        }
        
        return $this->_annotations['methods'][$methodName][$annotationName];
    } // end _getMethodAnnotationValues
    
}
