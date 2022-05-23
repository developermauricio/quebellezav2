<?php

class ListView
{
    protected $childCallback;
    
    public function addChildListener($callback)
    {
        $this->childCallback = $callback;
        
    } // end onChild
    
    public function html(IHtmlCollectionAccess &$instance)
    {
        $items = $instance->getItems();
        
        if (!$items) {
            return false;
        }
        
        $cssName = "e-list";
        
        $name = $instance->getName();
        if ($name) {
            $cssName .= "b-".$name;
        }
        
        $content = '<ul class="'.$cssName.'">';
        
        foreach ($items as $item) {
            
            if (is_callable($this->childCallback)) {
                call_user_func_array($this->childCallback, array(&$item));
            }
            
            $content .= '<li>'.$item->html().'</li>';
        }
        
        $content .= '<ul>';
        
        return $content;
    } // end html
}

?>