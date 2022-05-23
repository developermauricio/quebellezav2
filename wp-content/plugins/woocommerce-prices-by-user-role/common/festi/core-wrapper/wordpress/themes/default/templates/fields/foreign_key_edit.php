<?php

$ajaxHtml = "";
if ($this->get('ajaxChild')) {
    $params = array(
        Store::ACTION_KEY_IN_REQUEST => 'foreignKeyLoad',
        'ajaxChild' => $this->get('ajaxChild'),
        'ajaxParent' => $this->getName()
    );
    
    $params = array(
        $this->getStore()->getIdent() => $params
    );
    
    $url = $this->getStore()->getOption('current_url');
    
    $url = Controller::getInstance()->getUrl($url, $params);
    
    $ajaxHtml = 'onChange="dbaForeignKeyLoad(\''.$url.'\', this.value);"';
}

        $ajaxHtml2 = empty($this->attributes['ajaxChild']) ? '' : '<option '.($value === false ? ' selected ' : '').' value="">';
        $result = '<select class="form-control '.$this->getCssClassName().'" name="'.$this->name.'" id="'.$this->name.'" '.$ajaxHtml.'>' . $ajaxHtml2;
        if (isset($this->attributes['allowEmpty'])) {
            $result .= '<option value="0"></option>';
        }
        
        foreach ($this->keyData as $key => $val) {
            $selected = ($key == $foreignValue) ? 'selected' : '';
            $result .= '<option value="'.$key.'" '.$selected.'>'.$val."</option>\n";
        }
        $result .= '</select>';
        if ( (!empty($this->attributes['ajaxChild'])) && (!empty($value)) ) {
            //$GLOBALS['dba_afetrpatyjs'] .= '<script>dbaForeignKeyLoad(\''.$this->attributes['ajaxChild'].'\', "'.$this->name.'", "'.$value.'")</script>';
        }

echo $result;
?>