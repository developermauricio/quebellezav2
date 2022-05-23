<?php
echo $value;
/*
var_dump($value);
die();
$_sessionData = &$this->tblAction->sessionData;
        $httpBase = $this->tblAction->getOption('http_base');

        $value = explode(';0;', $value);
        if (!empty($value[0])) {
            if (!empty($this->attributes['fileName'])) {
                $httpPath = !empty($this->attributes['httpPath']) ? $this->attributes['httpPath'] : $httpBase.'storage/'.$_sessionData['DB_CURRENT_TABLE'].'/';

                $preview = $httpPath.'thumbs/'.$value[0];
                $link = $httpPath.$value[0];
            } else {
                $preview = $httpBase.'getfile/'.$_sessionData['DB_CURRENT_TABLE'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0].'?thumb=1';
                $link = $httpBase.'getfile/'.$_sessionData['DB_CURRENT_TABLE'].'/'.$this->name.'/'.$GLOBALS['currentID'].'/'.$value[0];
            }
            if (!empty($this->attributes['thumb'])) {
                return "<a href='$link' class='db_link' target='_blank'><img src='$preview' border='0' vspace='2px'></a>";
            } else {
                return "<a href='$link' class='db_link'>".$value[0]."</a>";
            }
        } else {
            return '';
        }
 */
?>