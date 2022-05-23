<?php

class FileField extends AbstractField
{
    const SEPARATOR = ";0;";
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $row = array();
        if (is_array($inline)) {
            $row = $inline;
        }
        $this->value = $value;
        $this->row = $row;
    
        $this->fileName = '';
        if (!empty($value)) {
            list($this->fileName) = explode(static::SEPARATOR, $value);
        }
        
        $this->links = $this->getFileUrls($value, $row);
        
        return $this->fetch('edit.phtml');
    } // end getEditInput
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        $this->value = $value;
        $this->row = $row;

        $fileName = '';
        if (!empty($value)) {
            list($fileName) = explode(self::SEPARATOR, $value);
            
            if (isset($this->attributes['trim'])) {
                if (strlen($fileName) > 36) {
                    $fileName = htmlspecialchars(substr($fileName, 0, 32)).'...'.
                                htmlspecialchars(substr($fileName, -4));
                } else {
                    $fileName = htmlspecialchars($fileName);
                }
            }
        }
        $this->fileName = $fileName;
        
        $this->links = $this->getFileUrls($value, $row);
        
        return $this->fetch('cell_value.phtml');
    } // edn displayValue

    /**
     * @param string $value
     * @param array $row
     * @return string
     */
    public function getInfoValue($value, $row = null)
    {
        return $this->displayValue($value, $row);
    }

    /**
     * @param array $requests
     * @return bool|mixed|string|null
     * @throws SystemException
     */
    public function getValue($requests = array())
    {
        if ($this->_isEmptyInputFile()) {
            return $this->_getRequestValue($requests);
        }

        $value = $this->_getPreparedValue();

        if (!$this->isValidValue($value)) {
            return false;
        }

        $this->doAddEventListener();

        return $value;
    } // end getValue
    
    /**
     * @return bool
     */
    private function _isEmptyInputFile(): bool
    {
        return (
            !array_key_exists($this->getName(), $_FILES) ||
            empty($_FILES[$this->getName()]['name'])
        );
    }
    
    /**
     * @param $requests
     * @return bool|string|null
     */
    private function _getRequestValue($requests)
    {
        // FIXME:
        $key = '_'.$this->getName();
        if ($this->_hasRequestValue($requests) && !empty($requests[$key])) {
            return true;
        }

        if ($this->get('required')) {
            $msg = __l(
                '%s is required field',
                $this->get('caption')
            );
            $this->setErrorMessage($msg);
            return false;
        }

        if ($this->get('isnull')) {
            return null;
        }

        return '';
    }
    
    /**
     * @param array $requests
     * @return bool
     */
    private function _hasRequestValue(array &$requests): bool
    {
        if (!empty($_FILES[$this->getName()]['name'])) {
            return false;
        }

        return array_key_exists('_'.$this->getName(), $requests);
    }
    
    /**
     * @return string
     */
    private function _getPreparedValue(): string
    {
        return sprintf(
            '%s%s%s',
            $_FILES[$this->getName()]['name'],
            self::SEPARATOR,
            $_FILES[$this->getName()]['type']
        );
    } // end _getPreparedValue
    
    /**
     * @param $value
     * @return bool|mixed
     * @throws SystemException
     */
    public function isValidValue($value)
    {
        $fieldName = $this->getName();

        if (!$this->isAllowedFile($_FILES[$fieldName]['tmp_name'])) {
            $msg = __l(
                'The file "%s" has wrong type',
                $this->get('caption')
            );
            $this->setErrorMessage($msg);
            return false;
        }

        $onValidateValue = $this->get('onValidateValue');

        if ($onValidateValue) {
            return $this->doEventCallback($onValidateValue, $value);
        }

        return true;
    } // end isValidValue

    protected function doAddEventListener()
    {
        $type   = null;
        $action = $this->store->getAction();

        if ($action == Store::ACTION_INSERT) {
            $type = Store::EVENT_INSERT;
        } else if ($action == Store::ACTION_EDIT) {
            $type = Store::EVENT_UPDATE;
        }

        if ($type) {
            $this->store->addEventListener(
                $type,
                array(&$this, 'onUploadFile')
            );
        }

        return true;
    } // end _addEventListener
    
    /**
     * @override
     */
    protected function getTemplatePath(): string
    {
        return parent::getTemplatePath() . 'file' . DIRECTORY_SEPARATOR;
    } // end getTemplatePath
    
    /**
     * @return array
     */
    protected function getAllowedMimeTypes(): array
    { 
        return array('*');
    } // end getAllowedMimeTypes
    
    /**
     * @param string $fileName
     * @return bool
     */
    protected function isAllowedFile(string $fileName): bool
    {
        $allowedMimeTypes = $this->getAllowedMimeTypes();
        if (in_array('*', $allowedMimeTypes)) {
            return true;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileName);
        finfo_close($finfo);
        
        return in_array($mimeType, $allowedMimeTypes);
    } // end isAllowedFile
    
    /**
     * @return string
     */
    protected function getStorageUri(): string
    {
        $httpPath = '';
        $httpBase = $this->store->getOption('http_base');
        $fileName = $this->get('fileName');
        if ($fileName) {
            $httpPath = $this->get('httpPath');
            $httpPath = !empty($httpPath) 
                ? $httpPath 
                : $httpBase . 'storages/' . $this->store->getName() . '/';
        }
        
        return $httpPath;
    } // end getStorageUri
    
    /**
     * @param $value
     * @param array $row
     * @return array
     */
    protected function getFileUrls($value, array $row): array
    {
        $urls = array();
        if (empty($value)) {
            return $urls;
        }
        list($fileName, $fileType) = explode(static::SEPARATOR, $value);

        // FIXME: Bad code
        if (!empty($fileName)) {
            $link = $this->get('link');

            if (!empty($link)) {
                $urls['file'] = $this->getPreparedLink($link, $fileName, $row);
                return $urls;
            }

            $httpPath = $this->getStorageUri();
            $httpPath = Entity::fillString($httpPath, $row);

            // TODO: move thumb logic to image field
            $thumbDimension = $this->get('thumb');

            if (!empty($httpPath)) {
                $urls['file'] = $httpPath . $fileName;

                if (!empty($thumbDimension) && $this->_isFileImage($fileType)) {
                    $urls['thumb'] = $httpPath . 'thumbs/' . $fileName;
                }
            } else if (!empty($row['id'])) {
                
                $url = $this->store->getOption('current_url');
                if (!empty($url)) {
                    
                    $params = array();
                    $request = array(
                        $this->store->getIdent() => &$params
                    );
                    $params = array(
                        Store::ACTION_KEY_IN_REQUEST  => Store::ACTION_DOWNLOAD,
                        Store::PRIMARY_KEY_IN_REQUEST => $row['id'],
                        Store::FIELD_KEY_IN_REQUEST   => $this->getName()
                    );
                    $urls['file'] = $url . '?' . http_build_query($request);
                    
                    if (!empty($thumbDimension) && $this->_isFileImage($fileType)) {
                        $params[Store::THUMB_IMAGE_IN_REQUEST] = 1;
                        $urls['thumb'] = $url.'?'.http_build_query($request);
                    }
                        
                }
            }
        }

        return $urls;
    } // end getFilesUrl

    protected function getPreparedLink($link, $fileName, $row)
    {
        $repl = array(
            '__EXT__'   => pathinfo($fileName, PATHINFO_EXTENSION),
            '__NAME__'  => pathinfo($fileName, PATHINFO_FILENAME)
        );

        return $this->fillString(strtr($link, $repl), $row);
    }

    /**
     * Upload file
     *
     * @param FestiEvent $event
     * @return bool
     * @throws FieldException
     */
    public function onUploadFile(FestiEvent $event) 
    {
        $fieldName = $this->getName();
        $tmpName = $_FILES[$fieldName]['tmp_name'];

        $primaryValue = $event->target['id'];
        $rowData = $event->target['values'];
        $rowData['id'] = $primaryValue;

        $fileName = $_FILES[$fieldName]['name'];
        $fileName = $this->_getUploadFileName($fileName, $primaryValue);

        $uploadPath = $this->getUploadFilePath($rowData);

        if (!$this->isUploaded($tmpName)) {
            $msg = __("Can't upload file for field %s", $fieldName);
            throw new FieldException($msg, $this->getCssSelector());
        }

        $res = $this->copy($tmpName, $uploadPath.$fileName);
        if (!$res) {
            $msg = __("Can't upload file for field %s", $fieldName);
            throw new FieldException($msg, $this->getCssSelector());
        }

        // FIXME:

        // TODO: add isUpdated logic

        $value = $fileName.static::SEPARATOR.$_FILES[$fieldName]['type'];
        $values = array(
            $fieldName => $value
        );
        $connection = &$this->store->getConnection();
        $search = array(
            $this->store->getPrimaryKey() => $primaryValue
        );
        $connection->update($this->getStoreName(), $values, $search);
        
        if ($this->_isFileImage($_FILES[$fieldName]['type'])) {
            $this->doUploadImageConversion($uploadPath, $fileName);
        }

        return true;
    } // end onUploadFile

    protected function copy($source, $target)
    {
        return move_uploaded_file($source, $target);
    } // end copy

    protected function isUploaded($filePath)
    {
        return is_uploaded_file($filePath);
    }

    /**
     * Returns path to upload file
     *
     * @param array $rowData
     * @throws Exception
     * @return string
     */
    public function getUploadFilePath(array $rowData = array())
    {
        $path = $this->get('uploadDirPath');
        if (!$path) {
            $path = $this->getDefaultUploadFilePath();
        }

        $path = Entity::fillString($path, $rowData);

        if (!is_dir($path) && !mkdir($path, 0775, true)) {
            throw new Exception(__("Can't create folder: %s", $path));
        }
        
        if (!is_writable($path)) {
            $msg = __("Permission error: %s", $path);
            throw new Exception($msg);
        }

        return $path;
    } // end getUploadFilePath
    
    protected function getDefaultUploadFilePath()
    {
        return $this->store->getDefaultUploadFilePath();
    } // end getDefaultUploadFilePath
    
    /**
     * Returns true if upload file is image
     * 
     * @return boolean
     */
    private function _isFileImage($fileType)
    {
        $imageMimeTypes = array('image/jpeg', 'image/gif', 'image/png');
        
        return in_array($fileType, $imageMimeTypes);
    } // end _isFileImage
    
    protected function doUploadImageConversion($uploadPath, $fileName)
    {
        $thumbDimension = $this->get('thumb');
        if ($thumbDimension) {
            $filePath = $uploadPath.$fileName;
            $thumbPath = $uploadPath.'thumbs'.DIRECTORY_SEPARATOR;
            if (!is_dir($thumbPath) && !mkdir($thumbPath, 0777, true)) {
                throw new Exception(__("Can't create folder: %s", $thumbPath));
            }

            $thumbPath = $thumbPath.$fileName;

            list($width, $height) = explode('x', $thumbDimension);
            FestiUtils::convertImageWithImageMagic(
                $filePath, $thumbPath, $width, $height
            );
        }
        
        $resizeDimension = $this->get('resize');
        if ($resizeDimension) {
            $filePath = $uploadPath.$fileName;
            list($needWidth, $needHeight) = explode('x', $resizeDimension);
            list($width, $height) = getimagesize($filePath);
            if (($width > $needWidth) || ($height > $needHeight)) {
                FestiUtils::convertImageWithImageMagic(
                    $filePath, $filePath, $needWidth, $needHeight
                );
            }
        }
        
    } // end doUploadImageConversion
    
    /**
     * Returns file name for upload file
     * 
     * @param string $realFileName
     * @param integer $id
     * @return string
     */
    private function _getUploadFileName(string $realFileName,  $id)
    {
        $fieldName = $this->getName();

        $fileNameFormat = $this->get('fileName');

        if (!$fileNameFormat) {
            return $id.'_'.$fieldName;
        }

        $repl = array(
            '__ID__'        => $id,
            '__EXT__'       => pathinfo($realFileName, PATHINFO_EXTENSION),
            '__NAME__'      => pathinfo($realFileName, PATHINFO_FILENAME),
            '__TIMESTAMP__' => time(),
            '__FIELD__'     => $fieldName
        );
        $fileName = strtr($fileNameFormat, $repl);

        return $fileName;
    } // end _getUploadFileName

    public function getRealFileName($idItem, $realFileName)
    {
        $fieldName = $this->getName();
        $fileName = $idItem.'_'.$fieldName;
        
        if ($this->get('fileName')) {
            $fileName = $realFileName;
        }
        
        return $fileName;
    } // end getRealFileName

    /**
     * @override
     */
    public function displayRO($value)
    {
        if (!empty($value)) {
            list($fileName) = explode(static::SEPARATOR, $value);
            return $fileName;
        }

        return $value;
    }

}