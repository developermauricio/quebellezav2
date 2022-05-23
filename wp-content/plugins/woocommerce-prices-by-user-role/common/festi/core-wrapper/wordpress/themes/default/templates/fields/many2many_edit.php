<?php
    $html = '<div class="'.$this->getCssClassName().'">';

        if ($this->extended) {
            $html .= '<table width="95%">';
            foreach ($this->list as $id => $option) {
                $firstLine = false;
                $html .= '<tr><td rowspan="'.count($this->valuesList).'"><b>'. $option['value'].": </b></td>";
                $first = true;
                foreach ($this->valuesList as $key => $caption) {
                    if (!$first) {
                        $html .= '<tr>';
                        $first = false;
                    }
                    $checked = (isset($option['checked']) && (($option['checked'] & pow(2, $key)) == pow(2, $key))) ? 'checked' : '';
                    $html .= '<td><div class="checkbox"><label><input type="checkbox" class="i-checkbox" name="m2m_'.$this->attributes['linkTable'].'['.$id.'][]" style="vertical-align: middle;" value="'.pow(2, $key).'" '.$checked.'> '.$caption.'</label></div></td></tr>';
                }
            }
            $html .= '</table>';
        } else {
            foreach ($this->list as $id => $option) {
                $checked = isset($option['checked']) ? 'checked' : '';
                $html .= '<div class="checkbox"><label><input type="checkbox" class="i-checkbox" name="m2m_'.$this->attributes['linkTable'].'['.$id.']" style="vertical-align: middle;" value="1" '.$checked.'> '.$option['value']."</label></div>";
            }
        }
        
        /*
        $html .= '
        </div>
        <label><input type="checkbox" style="vertical-align: middle; margin-left:5px" onClick="tbl_check_all(\'m2m_'.$this->attributes['linkTable'].'\', this.checked)">
        <b>'.__('FORM_CHECK_ALL').'</b></label>';
         */
         
         $html .= '</div>';
         echo $html;
?>