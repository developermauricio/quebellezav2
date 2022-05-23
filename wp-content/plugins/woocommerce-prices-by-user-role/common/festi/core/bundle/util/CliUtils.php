<?php

class CliUtils
{
    public static function e($msg)
    {
        print_r($msg."\033[90G\033[31;1m[ ERROR ]\033[m\n");
    }
    
    public static function s($msg)
    {
        print_r($msg."\033[90G\033[32;1m[ OK ]\033[m\n");
    }
    
    public static function w($msg)
    {
        print_r($msg."\033[90G\033[33;1m[ Warning ]\033[m\n");
    }
    
    public static function i($msg)
    {
        print_r($msg."\033[90G\033[34;1m[ Info ]\033[m\n");
    }
    
    /**
     * @param string $str
     * @param string|null $color
     */
    public static function cout(string $str, string $color = null)
    {
        $colors = array();
        $colors['black'] = '0;30';
        $colors['dark_gray'] = '1;30';
        $colors['blue'] = '0;34';
        $colors['light_blue'] = '1;34';
        $colors['green'] = '0;32';
        $colors['light_green'] = '1;32';
        $colors['cyan'] = '0;36';
        $colors['light_cyan'] = '1;36';
        $colors['red'] = '0;31';
        $colors['light_red'] = '1;31';
        $colors['purple'] = '0;35';
        $colors['light_purple'] = '1;35';
        $colors['brown'] = '0;33';
        $colors['yellow'] = '1;33';
        $colors['light_gray'] = '0;37';
        $colors['white'] = '1;37';
    
        if (!isset($colors[$color])) {
            echo $str;
        } else {
            echo "\033[" . $colors[$color] . "m".$str."\033[0m";
        }
    } // end cout
    
    public static function getLabel($label, $enum, $default)
    {
        if (is_array($enum)) {
            if ($default) {
                $index = array_search($default, $enum);
                $enum[$index] .= "*";
            }
    
            $label .= " [".join(", ", $enum)."]";
        } else if (!$default) {
            $label .= " [required]";
        } else {
            $label .= " [default: ".$default."]";
        }
    
        return $label.": ";
    } // end cin_get_label
    
    public static function cin($label, $enum = false, $default = '')
    {
        $label = static::getLabel($label, $enum, $default);
    
        while (!isset($in) || (is_array($enum) && !in_array($in, $enum)) || (!is_array($enum) && empty($in))) {
            static::cout($label, 'light_green');
    
            $in = trim(fgets(STDIN));
    
            if (empty($in) && !empty($default)) {
                $in = $default;
            }
        }
    
        return $in;
    } // end cin
    
}