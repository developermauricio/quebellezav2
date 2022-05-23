<?php 

class FestiUtils
{
    public static function convertToCamelCase($str)
    {
        return join("", array_map('ucfirst', explode("_", $str)));
    } // end convertToCamelCase
    
    public static function getIP()
    {
        $ip = false;
        if (isset($_SERVER)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } else {
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            } elseif (getenv('HTTP_CLIENT_IP')) {
                $ip = getenv('HTTP_CLIENT_IP');
            } else {
                $ip = getenv('REMOTE_ADDR');
            }
        }
    
        return $ip;
    } // end getIP
    
    public static function setImageResizeMagic(
        $outfile, $infile, $neww, $newh = null, $isCrop = false
    )
    {
        $core = Core::getInstance();
        
        // TODO: Add options for image processing
        $imageData = getimagesize($infile);
    
        if (is_null($newh)) {
            $ratio = $imageData[0] / $neww;
            $ratio = $ratio == 0 ? 1 : $ratio;
    
            $thumbWidth = round($imageData[0]/$ratio);
            $thumbHeight = round($imageData[1]/$ratio);
        } else {
            $thumbWidth = $neww;
            $thumbHeight = $newh;
        }
    
        // create thumb
        $zoomKoef = 1.15;
        $kw = $zoomKoef * $thumbWidth / $imageData[0];
        $kh = $zoomKoef * $thumbHeight / $imageData[1];
        $maxKoef = max($kw, $kh);
        $width = (int)($maxKoef * $imageData[0]);
        $height = (int)($maxKoef * $imageData[1]);
        $cropx = (int)(($width - $thumbWidth) / 2);
        $cropy = (int)(($height - $thumbHeight) / 2);
    
        $convertPath = $core->getOption("convert_path");
        if (!$convertPath) {
            throw new SystemException(__l("Undefined convert_path in options"));
        }
       
        $infile = '"'.$infile.'"';
        $outfile = '"'.$outfile.'"';
        if ($isCrop) {
             $cmd = $convertPath." -resize ".$width."x".$height."
             -crop {$thumbWidth}x{$thumbHeight}+".$cropx."+".$cropy;
             $cmd .= " ".$infile." ".$outfile;
        } else {
            $cmd = $convertPath." -resize ".$thumbWidth."x".$thumbHeight;
            $cmd .= " ".$infile." ".$outfile;
        }
         
        //echo $cmd."\n";
        $core->exec($cmd);
    
        return true;
    } // end setImageResizeMagic
    
    public static function removeDir($dir)
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $dir, 
                RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        
        rmdir($dir);
    } //end removeDir

    public static function getDateArray($startTime, $end)
    {
        $result = array();
        while ($startTime <= $end) {
            $date = date("Y-m-d", $startTime);
            $result[$date] = $date;
            $startTime += 86400;
        } 
        
        return $result;
    } // end getDateArray

    public static function doHandleCoreException($exp)
    {
        $core = Core::getInstance();
    
        if (!is_object($core)) {
            exit("ERROR: ".$exp->getMessage());
        }
        
        static::addLogMessage($exp->getMessage(), 'core', true);
    } // end doHandleCoreException
    
    public static function addLogMessage(
        $msg, $type = 'general', $isEmail = false
    )
    {
        $core = Core::getInstance();

        $text = array();
        $text[] = "Date: ".date('Y-m-d H:i:s');

        $ip = static::getIP();
        if ($ip) {
            $text[] = "IP: ".$ip;
        }

        if (!empty($_SERVER['REQUEST_URI'])) {
            $text[] = "URL: http://".$_SERVER['HTTP_HOST'].
                $_SERVER['REQUEST_URI']." [".$_SERVER['REQUEST_METHOD']."]";

        }

        if (isset($core->user) && $core->user->isLogin()) {
            $text[] = "User ID: ".$core->user->getID();
        }

        $text[] = "Message: [".$msg."]";

        $debug = debug_backtrace();
        $debug = empty($debug[1]) ? $debug[0] : $debug[1];

        $debug['class'] = !empty($debug['class']) ? $debug['class'].'::' : '';

        if (!empty($debug['file'])) {
            $text[] = "File: ".$debug['file'].":".$debug['line']." ".
                $debug['class'].$debug['function']."()";
        } else {
            $text[] = "Methods: ".$debug['class'].$debug['function']."()";
        }

        $body = join("\t", $text).PHP_EOL;

        $logPath = dirname(ini_get('error_log')).DIRECTORY_SEPARATOR;
        if (!empty($core->config['paths']['logs'])) {
            $logPath = $core->config['paths']['logs'];
        }

        $logPath .= $type.".log";

        error_log($body, 3, $logPath);

        if (defined('SUPPORT_MAIL') && $isEmail) {
            if (!defined('HOST')) {
                $subject = gethostname();
            } else {
                $subject = HOST;
            }

            $subject .= " ".$type." LOG";


            $body = join(PHP_EOL, $text)."<hr />";//.print_r($debug, 1);
            mail(SUPPORT_MAIL, $subject, $body);
        }

        return true;
    } // end addLogMessage

    public static function getFormattedPrice(
        $value, $locale = 'en_US', $accuracy = 2
    )
    {
        setlocale(LC_MONETARY, $locale);
        
        return money_format('%.'.$accuracy.'n', $value);
    }

    public function setImageResize(
        $outfile, $infile, $neww, $newh = null, $options = array()
    )
    {
        $image_info = getimagesize($infile);

        $quality = isset($options['quality']) ? $options['quality'] : 100;

        if (!$image_info) {
            return false;
        }

        $type = $image_info[2];
        $im = false;

        $im = null;
        switch ($type) {
            case IMAGETYPE_GIF:
                $im = imagecreatefromgif($infile);
                break;
            case IMAGETYPE_JPEG:
                $im = imagecreatefromjpeg($infile);
                break;
            case IMAGETYPE_PNG:
                $im = imagecreatefrompng($infile);
                break;
        }

        if (!$im) {
            return false;
        }

        $w_src = imagesx($im);
        $h_src = imagesy($im);

        if (!$w_src || !$h_src) {
            return false;
        }

        if (is_null($newh)) {
            // вычисление пропорций
            $ratio = $w_src / $neww;
            $ratio = $ratio == 0 ? 1 : $ratio;

            $w_dest = round($w_src/$ratio);
            $h_dest = round($h_src/$ratio);
        } else if (is_null($neww)) {
            $ratio = $h_src / $newh;
            $ratio = $ratio == 0 ? 1 : $ratio;

            $w_dest = round($w_src/$ratio);
            $h_dest = round($h_src/$ratio);
        } else {
            $w_dest = $neww;
            $h_dest = $newh;
        }

        $im1 = imagecreatetruecolor($w_dest, $h_dest);

        if (!$im1) {
            return false;
        }

        $res = imagecopyresampled(
            $im1, $im, 0, 0, 0, 0, $w_dest, $h_dest, $w_src, $h_src
        );
        if (!$res) {
            return false;
        }

        if (isset($options['watermark'])) {
            $im1 = self::create_watermark(
                $im1,
                $options['watermark'],
                $options['font']
            );
            if (!$im1) {
                return false;
            }
        }

        if (!imagejpeg($im1, $outfile, $quality)) {
            return false;
        }

        imagedestroy($im);
        imagedestroy($im1);

        chmod($outfile, 0777);
        chmod($outfile, 0777);

        return true;
    } // end setImageResize

    public static function create_watermark(
        $main_img_obj, $text, $font, $r = 0, $g = 0, $b = 255, $alpha_level = 95
    )
    {
        $width = imagesx($main_img_obj);
        $height = imagesy($main_img_obj);
        $angle =  -rad2deg(atan2((-$height), ($width)));

        $text = " ".$text." ";

        $c = imagecolorallocatealpha($main_img_obj, $r, $g, $b, $alpha_level);
        if (!$c) {
            return false;
        }
        $size = (($width+$height)/2)*2/strlen($text);


        $box  = imagettfbbox($size, $angle, $font, $text);
        if (!$box) {
            return false;
        }

        //$x = $width/2 - abs($box[4] - $box[0])/2;
        //$y = ($height/2 + abs($box[5] - $box[1])/2) + 15;
        $angle = 45;
        $x = -50;
        $y = ($height/6 + abs($box[5] - $box[1])/2);

        $res = imagettftext(
            $main_img_obj, $size, $angle, $x, $y, $c, $font, $text
        );

        if (!$res) {
            return false;
        }

        $x = $width/2 - abs($box[4] - $box[0])/2;
        $y = ($height/3 + abs($box[5] - $box[1])/2);

        $res = imagettftext(
            $main_img_obj, $size, $angle, $x, $y, $c, $font, $text
        );

        if (!$res) {
            return false;
        }

        $x = $width/2 - abs($box[4] - $box[0])/2;
        $y += $height/5 + $size;

        $res = imagettftext(
            $main_img_obj, $size, $angle, $x, $y, $c, $font, $text
        );

        if (!$res) {
            return false;
        }

        /*
        $x = $width/2 - abs($box[4] - $box[0])/2;
        $y = ($height/2 + abs($box[5] - $box[1])/2) + ($size + $size / 2);

        $res = imagettftext($main_img_obj,$size ,$angle, $x, $y, $c, $font,
        $text);
        if(!$res) {
            return false;
        }
        */

        /*
        $res = imagettftext($main_img_obj, $size , 0, 0, $height/2, $c, $font,
        $text);
        if(!$res) {
            return false;
        }
        */

        return $main_img_obj;
    } // end create_watermark

    /**
     * Returns field type name for DGS base on installed plugins for view large content.
     * @see TinyMcePlugin
     * @see IdePlugin
     * @return string
     */
    public static function getEditorFieldType()
    {
        if (class_exists('TinymceField')) {
            return 'tinymce';
        }

        if (class_exists('IdeField')) {
            return 'ide';
        }

        return 'textarea';
    } // end getEditorFieldType

    /**
     * Returns short date format.
     *
     * @see https://www.php.net/manual/en/function.strftime.php
     * @return string
     * @throws SystemException
     */
    public static function getShortDateFormat(): string
    {
        $systemPlugin = Core::getInstance()->getSystemPlugin();

        if ($systemPlugin->hasSetting('festi_date_format')) {
            return $systemPlugin->getSetting('festi_date_format');
        }

        return '%m/%d/%Y';
    } // end getShortDateFormat

    /**
     * Returns date format.
     *
     * @see https://www.php.net/manual/en/function.strftime.php
     * @return string
     * @throws SystemException
     */
    public static function getDateFormat(): string
    {
        $systemPlugin = Core::getInstance()->getSystemPlugin();

        if ($systemPlugin->hasSetting('festi_datetime_format')) {
            return $systemPlugin->getSetting('festi_datetime_format');
        }

        return '%m/%d/%Y %H:%M:%S';
    } // end getDateFormat

    // FIXME: Move all to strftime
    public static function getClientDateTimeFormat()
    {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        $acceptLang = ['ru', 'en'];
        $lang = in_array($lang, $acceptLang) ? $lang : 'en';

        $formats = array(
            'en' => 'm/d/Y h:i A',
            'ru' => 'd/m/Y H:i',
        );

        return $formats[$lang];
    }

    public static function getFormattedDate($date)
    {
        $time = strtotime($date);

        $core = Core::getInstance();

        $format = $core->getSystemPlugin()->getSetting('date_format');

        return date($format, $time);
    }

    public static function convertImageWithImageMagic(
        string $sourcePath, string $resultPath, int $width, int $height
    ): void
    {
        $imageMagicPath = Core::getInstance()->getOption('imagemagic_path');
        $size = sprintf("%dx%d", $width, $height);

        $cmd = sprintf(
            "\"%s\" \"%s\" -resize %s \"%s\"",
            $imageMagicPath,
            $sourcePath,
            $size,
            $resultPath
        );

        static::exec($cmd);
    }

    public static function exec($cmd, &$output = array())
    {
        $res = exec($cmd, $output, $ret);

        if ($ret !== 0) {
            throw new SystemException("Can't exec: ".$cmd, $ret);
        }

        return $res;
    } // end exec

}
