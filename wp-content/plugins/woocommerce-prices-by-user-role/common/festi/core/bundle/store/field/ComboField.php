<?php

class ComboField extends AbstractField
{
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        global $db;

        $city = $db->getCol("select distinct {$this->getName()} from {$this->table} order by {$this->getName()}");

        $value = htmlspecialchars(stripslashes($value));
        $out = '<input style="width:150px" type="text" name="'.$this->getName().'" id="'.$this->getName().'
                " value="'.$value.'" class="thin">';
        $out .= '&nbsp;<select style="width:190px" class="thin" onChange="doSelectTo(this, \''.$this->getName().'\')">';
        foreach ($city as $item) {
            $out .= '<option value="'.htmlspecialchars($item).'">'.htmlspecialchars($item);
        }
        $out .= '</select>';
        return $out;
    }
}