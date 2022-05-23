<?php

class ButtonView
{
    public function html(IHtmlAccess &$instance)
    {
        $css = "i-button ui-button hui-btn";
        
        $name = $instance->getName();
        $attributes = "";
        if ($name) {
            $css .= " i-button-".$name;
            $attributes = ' name="'.$name.'" id="'.$name.'"';
        }
        
        $externalAttributes = $instance->getHtmlProperty('attributes');
        if ($externalAttributes) {
            $attributes .= " ".$externalAttributes;
        }
        
        $externalCss = $instance->getHtmlProperty('css');
        if ($externalCss) {
            $css .= " ".$externalCss;
        }
        
        return '<button class="'.$css.'"'.$attributes.'>'.
               $instance->getHtmlProperty('caption').
               '</button>';
    } // end html
}

?>