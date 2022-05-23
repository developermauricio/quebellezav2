<?php

require_once 'bundle/ui/IHtmlAccess.php';

abstract class HuiElement extends Entity implements IHtmlAccess
{
    protected $view;
    
    public function setView($view)
    {
        $this->view = $view;
    } // end setView
    
    public function html()
    {
        if (!$this->view) {
            throw new Exception("Undefined view");
        }
        
        return $this->view->html($this);
    } // end html
    
}

