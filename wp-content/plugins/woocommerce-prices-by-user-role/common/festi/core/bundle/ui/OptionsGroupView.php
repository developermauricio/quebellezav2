<?php

class OptionsGroupView
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
        
        $content = "";
        
        foreach ($items as $item) {
            $attributes = $item->getHtmlProperty('attributes');
            $name = $item->getHtmlProperty('name');
            $value = $item->getHtmlProperty('value');
            $caption = $item->getHtmlProperty('caption');
            $content .= '<option value="'.$value.'" caption="'.$caption.'" '.$attributes.'>'.$caption.'</option>';
        }
        
        return $content;
    } // end html
}

?>