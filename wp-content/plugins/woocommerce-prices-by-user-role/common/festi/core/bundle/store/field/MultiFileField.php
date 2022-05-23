<?php

require_once 'bundle/store/field/FileField.php';

class MultiFileField extends FileField
{
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        $this->value = json_decode($value, true);

        if (!$this->value || !is_array($this->value)) {
            return null;
        }

        $files = $this->_getFileInfo($value);

        $this->row = $row;

        $this->fileData = array();
        foreach ($files as $file) {
            $this->fileData[$file['fileName']] = $this->getFileUrl($file, $row);
        }

        return $this->fetch('cell_value.phtml');
    }

    /**
     * @overide
     * @param $requests
     * @return bool|string
     * @throws FieldException
     */
    public function getValue($requests = array())
    {
        if ($this->_isEmptyInputFile()) {
            return $this->_getRequestValue($requests);
        }

        $values = $this->_getPreparedValue();
        if (!$this->isValidValue($values)) {
            return false;
        }
        
        $this->doAddEventListener();
        
        return json_encode($values, JSON_UNESCAPED_UNICODE);
    }

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
     * @return array
     */
    private function _getPreparedValue() : array
    {
        $fieldName = $this->getName();
        $files     = array();

        foreach ($_FILES[$fieldName] as $key => $file) {
            foreach ($file as $k => $value) {
                $files[$k][$key] = $value;
            }
        }

        $result = array_map(
            function($value) {
                $fileName = str_replace(' ', '_', $value['name']);

                return $fileName.static::SEPARATOR.$value['type'];
            },
            $files
        );

        return $result;
    }

    /**
     * @return bool
     */
    private function _isEmptyInputFile() : bool
    {
        return (
            !array_key_exists($this->getName(), $_FILES) ||
            empty($_FILES[$this->getName()]['name'][0])
        );
    }

    /**
     * @param $requests
     * @return bool|string|null
     */
    private function _getRequestValue($requests)
    {
        if ($this->_hasRequestValue($requests) &&
            !empty($requests['_'.$this->getName()])
        ) {
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
     * @param &$requests
     * @return bool
     */
    private function _hasRequestValue(&$requests) : bool
    {
        return array_key_exists('_'.$this->getName(), $requests);
    }

    /**
     * @overide
     * @param $values
     * @return bool
     * @throws FieldException
     */
    public function isValidValue($values)
    {
        if (empty($values)) {
            $msg = __(
                '%s is required field',
                $this->getCaption()
            );

            $this->setErrorMessage($msg);
            return false;
        }

        $fieldName = $this->getName();
        $files     = $_FILES[$fieldName];

        foreach ($values as $key => $value) {
            if (!$this->isAllowedFile($files['tmp_name'][$key])) {
                $msg = __(
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
        }
        
        return $values;
    }

    /**
     * @param FestiEvent $event
     * @return bool
     * @throws FieldException
     */
    public function onUploadFile(FestiEvent $event)
    {
        $data         = $event->target['values'];
        $fieldName    = $this->getName();
        $primaryValue = $event->target['id'];
        $fileNames    = array();

        $files = $this->_getFileInfo($data[$fieldName]);

        $uploadPath = $this->getUploadFilePath();

        foreach ($files as $key => $file) {
            $fileName = $this->_getUploadFileName($file, $primaryValue, $key);
            $tmpName  = $_FILES[$fieldName]['tmp_name'][$key];

            if (!$this->isUploaded($tmpName)) {
                $msg = __("Can't upload file for field %s", $this->getName());
                throw new FieldException($msg, $this->getCssSelector());
            }

            $res = $this->copy($tmpName, $uploadPath.$fileName);
            
            if (!$res) {
                $msg = __("Can't upload file for field %s", $this->getName());
                throw new FieldException($msg, $this->getCssSelector());
            }

            $fileNames[] = $fileName.static::SEPARATOR.$files[$key]['mimeType'];
        }


        if ($event->target['isUpdated']) {
            return true;
        }

        $values = array(
            $fieldName => json_encode($fileNames)
        );

        $connection = &$this->store->getConnection();
        
        $search = array(
            $this->store->getPrimaryKey() => $primaryValue
        );
        
        $connection->update($this->getStoreName(), $values, $search);
        
        return true;
    }

    /**
     * @return string
     */
    private function _getUploadFileName($file, $id, $index) : string
    {
        $fieldName = $this->getName();
        $fileName  = $file['fileName'];
        
        if ($this->get('fileName')) {
            $tmpName = $_FILES[$fieldName]['name'][$index];
            
            $repl = array(
                '__ID__'        => $id,
                '__KEY__'       => $index,
                '__EXT__'       => pathinfo($fileName, PATHINFO_EXTENSION),
                '__NAME__'      => pathinfo($fileName, PATHINFO_FILENAME),
                '__TIMESTAMP__' => time(),
                '__FIELD__'     => $fieldName,
                '__RANDNAME__'  => $index.substr(md5($tmpName), 0, 6).time()
            );
            
            $fileName = strtr($this->get('fileName'), $repl);
        }
        
        return $fileName;
    }

    /**
     * @overide
     * @return string
     * @throws SystemException
     */
    protected function getTemplatePath(): string
    {
        return Core::getInstance()->getOption('engine_path').
            "templates".DIRECTORY_SEPARATOR."fields".DIRECTORY_SEPARATOR.'multifile'.DIRECTORY_SEPARATOR;
    }

    /**
     * @overide
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $this->value = $value;

        if (empty($value)) {
            return $this->fetch('edit.phtml');
        }

        $row = array();

        $valueJson = htmlspecialchars_decode($value);
        $files     = $this->_getFileInfo($valueJson);

        if (is_array($inline)) {
            $row = $inline;
        }

        $this->row = $row;
        $this->fileData  = array();

        foreach ($files as $file) {
            $this->fileData[$file['fileName']] = $this->getFileUrl($file, $row);
        }
        
        return $this->fetch('edit.phtml');
    } // end getEditInput

    /**
     * @param $json
     * @return array
     * @throws FieldException
     */
    private function _getFileInfo($json) : array
    {
        $data = json_decode($json, true);

        if (!$data) {
            $msg = __("Invalid field %s value", $this->getName());
            throw new FieldException($msg, $this->getCssSelector());
        }

        $results = array();
        foreach ($data as $key => $item) {
            list($fileName, $mimeType) = explode(static::SEPARATOR, $item);

            $results[$key] =  array(
                'fileName' => $fileName,
                'mimeType' => $mimeType
            );
        }

        return $results;
    }

    /**
     * @param $file
     * @param $row
     * @return string
     */
    protected function getFileUrl($file, $row) : string
    {
        $url      = $this->store->getOption('current_url');
        $fileName = $file['fileName'];
        $link     = $this->get('link');

        if (!empty($link)) {
            $url = $this->getPreparedLink($link, $fileName, $row);
            return $url;
        }

        $httpPath = $this->getStorageUri();

        if (!empty($httpPath)) {
            $httpPath = $this->fillString($httpPath, $row);
            $url      = $httpPath . $fileName;
        }

        return $url;
    }

    /**
     * @overide
     * @return string
     */
    protected function getStorageUri() : string
    {
        $httpBase = $this->store->getOption('http_base');
        $httpPath = $this->get('httpPath');

        $httpPath = !empty($httpPath)
                ? $httpPath
                : $httpBase . 'storages/' . $this->store->getName() . '/';

        return $httpPath;
    }
}
