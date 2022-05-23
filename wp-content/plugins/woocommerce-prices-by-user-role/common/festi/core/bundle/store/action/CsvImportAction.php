<?php

require_once 'bundle/store/field/FileField.php';

class CsvImportAction extends AbstractDisplayAction
{
    const DEFAULT_DELIMITER = ',';
    
    protected $lastErrorMessage;
    protected $updateInfo;
    protected $primaryKeyValue;

    protected $allowedFileTypes = array(
        'text/csv',
        'application/vnd.ms-excel'
    );
    
    /**
     * @param Response $response
     * @return bool
     * @throws SystemException
     */
    public function onStart(Response &$response): bool
    {   
        try {
            if ($this->_isExec()) {
                $this->_onUpdate($response);
            } else {
                $this->_onDisplayForm($response);
            }
        } catch (Exception $exp) {
            $this->lastErrorMessage = $exp->getMessage();
            return $this->error($response);
        }
        
        return true;
    } // end onStart
    
    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_CSV_IMPORT;
    } // end getActionName
    
    /**
     * @param Response $response
     * @return void
     * @throws SystemException
     */
    private function _onDisplayForm(Response &$response): void
    {
        $action = $this->model->getAction($this->getActionName());
        
        $action = $this->getActionInfoForListDataItem($action);

        $info = array(
            'caption'         => $action['caption'],
            'base_http_icon'  => '',
            'action'          => Store::ACTION_PERFORM_SAVE,
            'actionbutton'    => $action['button']
        );
        
        $vars = array(
            'action'       => $action,
            'info'         => &$info,
            'items'        => $this->_getItems(),
            'what'         => $this->store->getAction(),
            'store'        => &$this->store,
        );
        
        $view = $this->store->getView();
        
        $response->content = $view->fetch('form.php', $vars, null);
    } // end _onDisplayForm
    
    /**
     * @return array
     * @throws SystemException
     */
    private function _getItems(): array
    {   
        $field = new FileField($this->store);
        
        $field->set('type', 'file');
        $field->setName($this->getActionName());

        $items = array(
            array(
                'caption'    => 'File',
                'disclaimer' => '',
                'name'       => 'file',
                'input'      => $field->getEditInput(),
            )
        );
        
        return $items;
    } // end _getItems
    
    /**
     * @param Response $response
     * @return bool
     * @throws StoreException
     * @throws SystemException
     */
    private function _onUpdate(Response &$response): bool
    {   
        $importData = $this->_getImportData();
        
        $errors = new ArrayObject();
        
        $options = array(
            'response'   => &$response,
            'importData' => $importData,
            'errors'     => &$errors
        );
        
        $this->doImport($options);
        
        if ($options['errors']->count()) {
            list($this->lastErrorMessage) = $options['errors'];
            return $this->error($response);
        }
        
        $response->setType(Response::JSON_IFRAME);
        $response->url = $this->getUrl();
        $response->setAction(Response::ACTION_REDIRECT);
        
        $msg = $this->store->getOption(Store::OPTION_MESSAGE_SUCCESS);
        
        if (!is_null($msg)) {
            if (!$msg) {
                $msg = __('Import Success. Record %s item', count($importData));
            }

            $response->addNotification($msg);
        }

        return true;
    } // end _onUpdate
    
    /**
     * @override
     */
    protected function error(Response &$response): bool
    {
        $response->setType(Response::JSON_IFRAME);
        $response->setAction(Response::ACTION_ALERT);

        if (!$this->lastErrorMessage) {
            $this->lastErrorMessage = __l('ERR_UNKNOWN');
        }

        if ($this->store->isExceptionMode()) {
            throw new StoreException($this->lastErrorMessage);
        }
        
        $response->addMessage($this->lastErrorMessage);
        return true;
    } // end error
    
    /**
     * @param array $options
     * @return bool
     */
    protected function doImport(array $options): bool
    {
        $proxy = &$this->store->getProxy();
        
        $primaryKeyTable = $this->store->getPrimaryKey();
        
        try {
            $proxy->begin();

            foreach ($options['importData'] as $values) {
                unset($values[$primaryKeyTable]);
                
                $this->_setPrimaryKeyValue($values);
                
                $isUpdated = false;
                
                $this->updateInfo = array(
                    'id'        => $this->primaryKeyValue,
                    'values'    => &$values,
                    'isUpdated' => &$isUpdated,
                    'response'  => &$options['response'],
                    'action'    => $this->store->getAction(),
                );
                
                
                $this->_dispatchBeforeUpdateEvent();

                $this->_doSync($proxy, $values);
            }
            $proxy->commit();
        } catch (Exception $exp) {
            $options['errors'][] = $exp->getMessage();
            
            $proxy->rollback();
            
            return false;
        }
        
        return true;
    } // end doImport
    
    /**
     * @param array $values
     * @return bool
     */
    private function _setPrimaryKeyValue(array $values): bool
    {
        $primaryKey = $this->_getPrimaryKeyByAction();
        
        if (!array_key_exists($primaryKey, $values)) {
            return false;
        }

        $search = array(
            $primaryKey => $values[$primaryKey]
        );
        
        $result = $this->store->loadRow($search);
        if (!empty($result['id'])) {
            $this->primaryKeyValue = $result['id'];
        }
        return true;
    } // end _setPrimaryKeyValue
    
    /**
     * @return bool|mixed
     */
    private function _getAction()
    {
        return $this->model->getAction($this->getActionName());
    } // end _getAction
    
    /**
     * @return bool|mixed
     */
    private function _getPrimaryKeyByAction()
    {
        $primaryKey = false;
        
        $action = $this->_getAction();
        
        if (array_key_exists('primaryKey', $action)) {
            $primaryKey = $action['primaryKey'];
        }
        
        return $primaryKey;
    } // end _getPrimaryKeyByAction
    
    /**
     * @param IProxy $proxy
     * @param array $values
     * @return bool
     */
    private function _doSync(IProxy $proxy, array $values): bool
    {
        if ($this->updateInfo['isUpdated']) {
            return false;
        }
        
        if ($this->primaryKeyValue) {
            $this->store->updateByPrimaryKey($this->primaryKeyValue, $values);
        } else {
            $proxy->insert($values);
        }
                
        return true;
    } // end _doSync
    
    /**
     * @throws StoreException
     */
    private function _dispatchBeforeUpdateEvent()
    {
        $eventName = Store::EVENT_BEFORE_UPDATE;
        if (!$this->primaryKeyValue) {
            $eventName = Store::EVENT_BEFORE_INSERT;
        }
        
        $this->event($eventName, $this->updateInfo);
    } // end _dispatchBeforeUpdateEvent
    
    /**
     * @return array
     * @throws SystemException
     */
    private function _getImportData(): array
    {
        $action = $this->_getAction();
        
        $uploadFile = $this->getUploadFilePath();
        
        $handle = $this->_getHandle($uploadFile);
        
        $delimiter = static::DEFAULT_DELIMITER;
        
        if (array_key_exists('delimiter', $action)) {
            $delimiter = $action['delimiter'];
        }
        
        $columns = $this->_getModelColumns();
        
        $offset = 0;
        $dataFile = array();
        
        if ($handle === false) {
            throw new SystemException(__("Undefined file hundle")); 
        }
        
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $offset++;

            if ($this->_isFirstRowHeader($action, $offset)) {
                continue;
            }
            foreach ($data as $index => $row) {
                if (!array_key_exists($index, $columns)) {
                    continue;
                }
                $dataFile[$offset][$columns[$index]] = $row;
            }
        }
        
        fclose($handle);
        
        return $dataFile;
    } // end _getImportData
    
    /**
     * @return array
     */
    private function _getModelColumns(): array
    {
        $columns = array();
        
        foreach ($this->model->getFields() as $key => $field) {
            if ($field->getName() == $this->store->getPrimaryKey()) {
                continue;
            }
            $columns[] = $field->getName();
        }
        
        return $columns;
    } // end _getModelColumns
    
    /**
     * @param array $action
     * @param int $offset
     * @return bool
     */
    private function _isFirstRowHeader(array $action, int $offset): bool
    {
        return $offset == 1 && 
               array_key_exists('isFirstRowHeader', $action) && 
               filter_var($action['isFirstRowHeader'], FILTER_VALIDATE_BOOLEAN);
    } // end _isFirstRowHeader
    
    /**
     * @return string
     * @throws SystemException
     */
    public function getUploadFilePath()
    {
        $uploadName = $this->getActionName();

        if (empty($_FILES[$uploadName])) {
            throw new SystemException(__('CSV File Not found'));
        }

        if (!in_array($_FILES[$uploadName]['type'], $this->allowedFileTypes)) {
            throw new SystemException(__('Type File Must By CSV'));
        }
        
        $fileName = $_FILES[$uploadName]['name'];
        
        $uploadDir = $this->_getUploadDirPath();

        $uploadFile = $uploadDir . basename($fileName);

        if (move_uploaded_file($_FILES[$uploadName]['tmp_name'], $uploadFile)) {
            return $uploadFile;
        }
        throw new SystemException(__('File Not Download'));
    } // end getUploadFilePath
    
    /**
     * @return string
     * @throws SystemException
     */
    private function _getUploadDirPath(): string
    {
        $uploadDir = $this->store->getDefaultUploadFilePath();
        $action = $this->model->getAction($this->getActionName());
        
        if (array_key_exists('uploadDirPath', $action)) {
            $uploadDir = $action['uploadDirPath'];
        }

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
            $msg = __("Can't create folder: %s", $uploadDir);
            throw new SystemException($msg);
        }

        if (!is_dir($uploadDir)) {
            throw new SystemException(__("Fail Path: %s", $uploadDir));
        }
        
        return $uploadDir;
    } // end _getUploadDirPath
    
    /**
     * @param string $file
     * @return bool|resource
     * @throws SystemException
     */
    private function _getHandle(string $file)
    {
        if (!file_exists($file)) {
            $msg = __("Not Exists File %s", $file);
            throw new SystemException($msg);
        }
        
        $handle = fopen($file, "r");
        if ($handle === false) {
            $msg = __("Error reading file");
            throw new SystemException($msg);
        }
        
        return $handle;
    } // end _getHandle
    
    /**
     * @return bool
     */
    private function _isExec()
    {
        return $this->store->getPostParam(Store::ACTION_PERFORM_KEY_IN_POST);
    } // end isExec
}
