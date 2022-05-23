<?php 

require_once 'bundle/ui/HuiElement.php';
require_once 'bundle/ui/IHtmlCollectionAccess.php';

/**
 * Class Hui. Utils for creating UI elements from code.
 *
 * @method DialogHui dialog()
 * @method InputHui input()
 */
class Hui
{
    protected $view;
    
    public function __construct($view)
    {
        $this->view = $view;
    } // end __construct
    
    public function __call($name, $arguments) 
    {
        $className = ucfirst($name)."Hui";
        
        if (!class_exists($className)) {
            $className = "HuiComponent";
        }
        
        $instance = new $className(false, $arguments);
        $instance->view = &$this->view;
        
        $instance->setTemplateName($name.".php");
        
        return $instance;
    }
}


class BlockHui extends HuiComponent
{
    public function getView()
    {
        return $this->view->fetch('ui/block.php');
    }
}

class WidgetHui extends HuiComponent
{
    public function getView()
    {
        return $this->view->fetch('ui/widget.php');
    }
}

class DataListHui extends HuiComponent
{
    public function data($data)
    {
        $this->view->rows = $data;
        
        return $this;
    } // end data
    
    public function columns($columns)
    {
        $this->view->columns = $columns;
        
        return $this;
    } // emd columns
    
    public function getView()
    {
        return $this->view->fetch('ui/dl.php');
    }
}


class IconHui extends HuiComponent
{
    public function getSelector()
    {
        return '\^<div><img src="%icon%" /></div>\^';
    } // end getSelector
    
    public function getValue()
    {
        return '<div><img src="'.$this->arguments[0].'" /></div>';
    } // end getSelector
}

class RightButtonHui extends HuiComponent
{
    public function getSelector()
    {
        return '\^<div class="hui-block-top-bar-right">%right_button%</div>\^';
    } // end getSelector
    public function getValue()
    {
        return '<div class="hui-block-top-bar-right">'
               . $this->arguments[0]
               . '</div>';
    } // end getSelector
}

///////////////

class FileHui extends HuiComponent
{
    public function getView()
    {
        return '<input type="file" 
                id="%name%" 
                name="%name%"  
                ^onchange="%change%"^>';
    }
}

///////////////

class AjaxButtonDialogHui extends HuiComponent
{
    public function getView()
    {
        return '<input type="button" 
                id="%name%" 
                name="%name%" 
                value="%caption%" 
                ^class="%css%"^ />
        <script>
        var btn%name% = jQuery("#%name%");
        btn%name%.button();
        btn%name%.click(function() {
            jQuery.get("%url%", function(dialogContent) {
                Jimbo.dialog(dialogContent, "%title%");
                console.log(dialogContent);
            });
        });
        </script>';
    }
}

///////////////////

class NameHui extends HuiComponent
{
    public function getSelector()
    {
        return '%name%';
    } // end getSelector
    
    public function getValue()
    {
        return $this->arguments[0];
    } // end getSelector
}

class ContentHui extends HuiComponent
{
    public function getSelector()
    {
        return '%%content%%';
    } // end getSelector
    
    public function getValue()
    {
        return $this->arguments[0];
    } // end getSelector
}



class TitleHui extends HuiComponent
{
    public function getSelector()
    {
        return '%%title%%';
    } // end getSelector
    
    public function getValue()
    {
        return $this->arguments[0];
    } // end getSelector
}





class CssHui extends HuiComponent
{
    public function getSelector()
    {
        return '\^class="%css%"\^';
    } // end getSelector
    
    public function getValue()
    {
        return 'class="'.$this->arguments[0].'"';
    } // end getSelector
}

class CaptionHui extends HuiComponent
{
    public function getSelector()
    {
        return array(
            '\^value="%caption%"\^',
            '%caption%'
        );
    } // end getSelector
    
    public function getValue()
    {
        return array(
            'value="'.$this->arguments[0].'"',
            $this->arguments[0]
        );
    } // end getSelector
}

class ClickHui extends HuiComponent
{
    public function getSelector()
    {
        return '\^onclick="%click%"\^';
    } // end getSelector
    
    public function getValue()
    {
        return 'onclick="'.$this->arguments[0].'"';
    } // end getSelector
}

class ChangeHui extends HuiComponent
{
    public function getSelector()
    {
        return '\^onchange="%change%"\^';
    } // end getSelector
    
    public function getValue()
    {
        return 'onchange="'.$this->arguments[0].'(this);"';
    } // end getSelector
}



class UrlHui extends HuiComponent
{
    public function getSelector()
    {
        return '%url%';
    } // end getSelector
    
    public function getValue()
    {
        return $this->arguments[0];
    } // end getSelector
}

class NavButtonHui extends HuiComponent
{
    public function getView()
    {
        return '<input type="button" 
                id="%name%" 
                name="%name%" 
                ^value="%caption%"^ 
                ^class="%css%"^>
        <script>
        jQuery("#%name%").button();
        jQuery("#%name%").click(function () {
            window.location = "%url%";
        });
        </script>';
    }
}



class AjaxDialogHui extends HuiComponent
{
    public function getView()
    {
        return '<script>jQuery("<div>").load("%url%", function() {
                    alert("Load was performed. %name%");
                });</script>';
    }
}



class HuiComponent
{
    protected $parent;
    protected $arguments;
    protected $templateName;
    
    public $view;
    
    public function __construct($parent = false, $arguments = false)
    {
        $this->parent = $parent;
        $this->arguments = $arguments;
    }
    
    public function __call($name, $arguments) 
    {
        if (preg_match("#^set#Umis", $name)) {
            $name = lcfirst(substr($name, 3));
        
            $this->view->assign($name, $arguments[0]);
            
            return $this;
        }

        if (preg_match("#^get#Umis", $name)) {
            $name = lcfirst(substr($name, 3));

            if (!isset($this->view->$name)) {
                return null;
            }

            if (is_array($this->view->$name)) {
                return $this->view->$name[$arguments[0]];
            }

            return $this->view->$name;
        }

        if (preg_match("#^add#Umis", $name)) {
            $name = lcfirst(substr($name, 3));
        
            if (!isset($this->view->$name)) {
                $this->view->$name = array();
            }
            
            if (isset($arguments[1])) {
                $this->view->$name[$arguments[0]] = $arguments[1];
            } else {
                $var = &$this->view->$name;
                if (is_array($arguments[0])) {
                    $var = array_merge($var, $arguments[0]);
                } else {
                    $var[] = $arguments[0];
                }
            }
            
            return $this;
        }
        
        $className = ucfirst($name) . "Hui";
        $instance = new $className($this, $arguments);
        $instance->view = &$this->view;
        
        return $instance;
    } // end __call
    
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
    } // end setTemplateName
    
    public function fetch()
    {
        return $this->view->fetch('ui/'.$this->templateName);
    }
    
    public function getView()
    {
        return false;
    }
    
    public function getSelector()
    {
       return false;
    } // end getSelector
    
    public function getValue()
    {
        return isset($this->arguments[0]) ? $this->arguments[0] : false;
    } // end getSelector
    
    public function html($selectors = array())
    {
        $selector = $this->getSelector();
        if ($selector) {
            $value = $this->getValue();
            if (is_array($selector)) {
                foreach ($selector as $index => $key) {
                    $selectors[$key] = $value[$index];
                }
            } else {
                $selectors[$selector] = $value;
            }
        }
        
        $currentView = $this->getView();
        
        if (!$this->parent && !$currentView) {
            throw new HuiException("Base UI must have getView");
        }
        
        if (!$currentView) {
            return $this->parent->html($selectors);
        }
        
        foreach ($selectors as $condition => $value) {
            $regExp = "#".$condition."#Umis";
            $currentView = str_replace($condition, $value, $currentView);
            $currentView = preg_replace($regExp, $value, $currentView);
        }
        
        $regExp = "#\^[^\^]+\^#Umis";
        $currentView = preg_replace($regExp, "", $currentView);
        
        //
        $regExp = "#%%([^%]+)%%#Umis";
        if (preg_match_all($regExp, $currentView, $match)) {
            $attributes = array_unique($match[1]);
            throw new HuiException(
                "Undefined required attributes: " . join(", ", $attributes)
            );
        }
        
        return $currentView;
    } // end html
}

/**
 * Class InputHui.
 *
 * @method InputHui setName(string $name)
 * @method string getName()
 * @method InputHui setType(string $name)
 * @method string getType()
 * @method InputHui setValue(string $name)
 * @method string getValue()
 */
class InputHui extends HuiComponent
{
    public function fetch()
    {
        $type = $this->getType();

        $templateName = $type ? 'input_'.$type : 'input';

        return $this->view->fetch('ui/'.$templateName.'.php');
    }
}

/**
 * Class DialogHui.
 *
 * @method DialogHui setTitle(string $title)
 * @method DialogHui setSubmitCaption(string $caption)
 * @method DialogHui setBody(string $body)
 * @method DialogHui setUrl(string $url)
 * @method DialogHui addField(string $name, InputHui $field)
 */
class DialogHui extends HuiComponent
{
}

class HuiException extends Exception
{
}