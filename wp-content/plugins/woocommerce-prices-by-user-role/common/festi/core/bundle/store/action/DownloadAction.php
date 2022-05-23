<?php

use core\dgs\action\StoreActionException;

class DownloadAction extends AbstractAction
{
    /**
     * @param Response $response
     * @return bool
     */
    public function onStart(Response &$response): bool
    {
        try {
            $primaryKeyValue = $this->store->getPrimaryKeyValueFromRequest();
            
            $data = $this->store->loadRowByPrimaryKey($primaryKeyValue);
            
            $fieldName = $this->store->getRequestParam(Store::FIELD_KEY_IN_REQUEST);
            $isThumb = $this->store->getRequestParam(Store::THUMB_IMAGE_IN_REQUEST);

            $fieldInstance = $this->store->getModel()->getFieldByName($fieldName);
            
            if (!$fieldInstance || !array_key_exists($fieldName, $data)) {
                throw new Exception(__('Not found file field'));
            }
            
            $realFileName = explode(FileField::SEPARATOR, $data[$fieldName])[0];

            $fileName = null;
            $uploadPath = null;

            if ($fieldInstance instanceof FileField) {
                $uploadPath = $fieldInstance->getUploadFilePath();
                $fileName = $fieldInstance->getRealFileName($primaryKeyValue, $realFileName);
            } else {
                throw new StoreActionException("Undefined file field type");
            }

            if ($isThumb) {
                $filePath = $uploadPath . '/thumbs/' . $fileName;
            } else {
                $filePath = $uploadPath.DIRECTORY_SEPARATOR.$fileName;
            }
    
            if (empty($fileName) || !is_file($filePath)) {
                throw new Exception(__('Not found file: '.$filePath));
            }
            
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $filePath);
            finfo_close($fileInfo);

            $response->setAction(Response::ACTION_FILE);
            $response->path = $filePath;
            $response->fileName = $realFileName;
            $response->contentType = $mimeType;
            
        } catch (DatabaseException $exp) {
            header("HTTP/1.0 404 Not Found");
            header('X-message: Database Exception');
        } catch (Exception $exp) {
            header("HTTP/1.0 404 Not Found");
            header('X-message: '.$exp->getMessage());
        }

        return true;
    } // end onStart

    /**
     * @return array|null
     */
    protected function getRequestFields(): ?array
    {
        $requestFields = array(
           'action' => array(
                'type'     => self::FIELD_TYPE_STRING_NULL,
                'required' => true
            )
        );

        return $requestFields;
    } // end getRequestFields

    /**
     * @override
     */
    public function getActionName(): string
    {
        return Store::ACTION_DOWNLOAD;
    }
}
