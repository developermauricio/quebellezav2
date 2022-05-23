<?php
 $select = '<select class="form-control '.$this->getCssClassName().'" name="'.$this->name.'" >';
        foreach ($this->valuesList as $key => $val) {
            $selected = ($key == $value) ? 'selected' : '';
            $select .= '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
        }
        $select .= '</select>';
 echo $select;       
?>