<?php
if (is_numeric($value)) {
    $value = ($value) ? 'Yes' : 'No';
}
$httpBase = $this->store->getOption('theme_url');
$checked = (strtoupper(substr($value, 0, 1)) == 'Y') ? '<img align="center" src="'.$httpBase.'images/dbadmin_tick.png" />' : '';
echo $checked;
?>