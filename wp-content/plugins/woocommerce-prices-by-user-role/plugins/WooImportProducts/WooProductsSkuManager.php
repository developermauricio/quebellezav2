<?php

require_once __DIR__.DIRECTORY_SEPARATOR.'ImportWooProductValidator.php';

class WooProductsSkuManager
{
    private $_engine;
    private $_columnsToCaptions = array();
    private $_parentProductValuesObject;
    
    public $config;
    public $languageDomain;
    
    protected $rowIndex;
    
    public $idNewPost;
    public $newPost = array();
    public $newPostDefaults = array();
    public $newPostMeta = array();
    public $newPostMetaDefaults = array();
    public $newPostTerms = array();
    public $newPostCustomFields = array();
    public $newPostCustomFieldCount = 0;
    public $newPostImageUrls = array();
    public $newPostImagePaths = array();
    public $newPostErrors = array();
    public $newPostMessages = array();
    public $newPricesByUserRole = array();
    public $existProduct = null;
    public $uploadImagesDir = '';

    public function __construct($config, $engine, $languageDomain)
    {
        $this->_engine = $engine;
        $this->config = $config;
        
        $this->languageDomain = $languageDomain;
        
        if (!empty($this->config['mapTo'])) {
            $this->_columnsToCaptions = array_combine(
                $this->config['mapTo'],
                $this->config['custom_field_name']
            );
        }
    } // end __construct
    
    protected function onInitDefaultPostValues()
    {
        $this->idNewPost = null;
        $this->newPost = array();
        $this->newPostDefaults = $this->_getDefaultPostData();
        $this->newPostMeta = array();
        $this->newPostMetaDefaults = $this->_getDefaultPostMetaData();
        $this->newPostTerms = array();
        $this->newPostCustomFields = array();
        $this->newPostCustomFieldCount = 0;
        $this->newPostImageUrls = array();
        $this->newPostImagePaths = array();
        $this->newPostErrors = array();
        $this->newPostMessages = array();
        $this->newPricesByUserRole = array();
        $this->uploadImagesDir = wp_upload_dir();
        
        $this->_parentProductValuesObject = false;

        $this->mappingOptions = $this->_engine->mappingOptions->get();
        $this->mappingOptions = $this->_deleteMappingOptionsGroup();
    } // end onInitDefaultPostValues
    
    protected function setValuesThatRequireValidation(
        $mappingActions, $column, $key
    )
    {
        $mapTo = $this->config['mapTo'][$key];
        
        if ($this->_isNotImportColumn($mapTo)) {
            return false;
        }

        if (!$this->_isValidateValue($column, $mapTo)) {
            return false;
        }
        
        foreach ($mappingActions as $ident => $item) {
            if (in_array($mapTo, $item)) {
                $methodName = $ident;
            } else if (strpos($mapTo, '_festi_price')) {
                $methodName = 'setRolePriceValue';
            } else {
                continue;
            }

            if (
                $this->_isValueRequireDecimalSeparatorValidation($methodName) &&
                $this->_hasIncorrectDecimalSeparator($column)
            ) {
                $message = __(
                    "Incorrect decimal separator was provided for {$column}",
                    $this->languageDomain
                );
                $this->newPostErrors[] = $message;
                
                continue;
            }
            
            $method = array($this->_engine->mappingOptions, $methodName);
            call_user_func_array(
                $method,
                array($this, $mapTo, $column, $key)
            );
        }
    } // end setValuesThatRequireValidation
    
    private function _isValueRequireDecimalSeparatorValidation($methodName)
    {
        $floatMethods = array('setRolePriceValue', 'setFloatPostMetaFields');
        
        return in_array($methodName, $floatMethods);
    } // end _isValueRequireDecimalSeparatorValidation
    
    private function _hasIncorrectDecimalSeparator($value)
    {
        $separator = $this->config['decimalSeparator'];
        
        return $this->_isSeparatorNotComma($value, $separator) ||
               $this->_isSeparatorNotDot($value, $separator);
    } // end _hasIncorrectDecimalSeparator

    private function _isSeparatorNotComma($value, $separator)
    {
        return $this->_isValueSeparatedByComma($value) && $separator !== ",";
    } // end _isSeparatorNotComma

    private function _isSeparatorNotDot($value, $separator)
    {
        return $this->_isValueSeparatedByDot($value) && $separator !== ".";
    } // end _isSeparatorNotDot

    private function _isValueSeparatedByComma($value)
    {
        return (bool) preg_match("/^[0-9]+,[0-9]+$/", $value);
    } // end _isValueSeparatedByComma

    private function _isValueSeparatedByDot($value)
    {
        return (bool) preg_match("/^[0-9]+\.[0-9]+$/", $value);
    } // end _isValueSeparatedByDot
    
    public function start($row, $rowIndex) 
    {
        $this->rowIndex = $rowIndex;
        
        $this->onInitDefaultPostValues();

        $engine = $this->_engine;
        
        $mappingActions = $engine->mappingOptions->getImportMapingActions(
            $this
        );
        
        foreach ($row as $key => $column) {
            $this->setValuesThatRequireValidation(
                $mappingActions,
                $column,
                $key
            );
        }
        
        $this->existProduct = $this->_getExsistProduct();

        if ($this->_isUpdatePriceEnabled()) {
            $this->_doUpdatePriceForCurrentProduct();
        }

        $this->newPostMeta['_manage_stock'] = $this->setManageStockForProduct();
        $this->newPostMeta['_stock_status'] = $this->setStockStatusForProduct();

        if ($this->_isUpdateUserRolePriceEnabled()) {
            $this->_doUpdateUserRolePriceForCurrentProduct();
        }
        
        if (!$this->_hasValidateProductNameInData() && !$this->existProduct) {
            $this->newPostErrors[] = __(
                'Skipped import of product without a name',
                $this->languageDomain
            );
            
            return $this->getImportReport();
        }
        
        $validator = new ImportWooProductValidator(
            $this->newPost, 
            $this->newPostMeta,
            $this->existProduct,
            $this->_columnsToCaptions,
            $engine
        );
        
        $validator->exec();
        
        $errors = $validator->getErrors();
        if ($errors) {
            $this->newPostErrors = $errors;
            return $this->getImportReport();
        }

        try {
            if ($this->existProduct) {
                $this->updatePost();
            } else {
                $this->insertPost();
            }
            
            if ($this->_isValidateNewPostID()) {
                $this->_updateProduct($key);
            }
            
            if ($this->_parentProductValuesObject) {
                $this->_doSynchronizeVariationProduct();
            }
        } catch (Exception $exp) {
            $this->newPostErrors[] = $exp->getMessage();
        }

        return $this->getImportReport();   
    } //end start
    
    private function _isUpdateUserRolePriceEnabled()
    {
        return (bool) $this->newPricesByUserRole;
    } // end _isUpdateUserRolePriceEnabled
    
    private function _doUpdateUserRolePriceForCurrentProduct()
    {
        $userRolePrices = $this->getPreparedUserRolePricesForProduct();
        $this->newPostMeta[PRICE_BY_ROLE_PRICE_META_KEY] = $userRolePrices;
    } // end _doUpdateUserRolePriceForCurrentProduct
    
    private function _doSynchronizeVariationProduct()
    {
        $parentValuesObject = $this->_getParentProductValuesObject();
        
        $facade = EcommerceFactory::getInstance();

        $engineFacade = EngineFacade::getInstance();
        
        $attributes = array();
        $attributesValues = array();

        foreach ($this->newPostCustomFields as $attrName => $attrOption) {
            $option = $this->config['attributes'][$attrOption['name']];
            $attrKey = $facade->getAttributeNameByKey($option['ident']);
          
            $attributes[$attrKey] = $this->_getProductAttribute($option);   
            $attributesValues[$attrKey] = $facade->getProductAttributeValues(
                $parentValuesObject->getID(),
                $attrKey
            );

            if ($option['is_variation']) {
                $engineFacade->updatePostMeta(
                    $this->idNewPost, 
                    'attribute_'.$attrKey,
                    $engineFacade->doSanitizeTitle($attrOption['value'])
                );
            }
            
            $attributesValues[$attrKey][] = $attrOption['value'];
        }
        
        if ($attributes) {
            $facade->updateProductAttributes(
                $parentValuesObject->getID(),
                $attributes
            );
        }
        
       foreach ($attributesValues as $attrName => $attrValues) {
            $attrValues = array_unique($attrValues);
            $facade->updateProductAttributeValues(
                $parentValuesObject->getID(),
                $attrName,
                $attrValues
            );
       }

       $idParent = $parentValuesObject->getID();

       $facade->setProductTypeToVariable($idParent);

       $facade->doSynchronizeProductVariations($idParent);
    } // end _doSynchronizeVariationProduct

    private function _getParentProductValuesObject()
    {
        return $this->_parentProductValuesObject;
    } // end _getParentProductValuesObject
    
    private function _updateProduct($key)
    {
        $this->_updatePostMeta();

        $this->_updateProductAttributes();

        $this->_updatePostTerms();

        $this->_updateProductImagesByUrl();
        
        $this->_updateProductAtachmentData($key);

        $idPost = $this->idNewPost;

        $facade = EcommerceFactory::getInstance();

        $facade->doStoreProductPropertiesByID($idPost);
    } // end _updateProduct
    
    private function _isEnabledSkipDuplicatesImagesOption($columnIndex)
    {
        $optionKey = 'product_image_skip_duplicates';
        $skipDuplicatesOptionsByColumnIndexes = $this->config[$optionKey];
        
        $isSkipDuplicateImages = false;
        
        if ($this->_isExistsColumnIndex($columnIndex)) {
            $isSkipDuplicateImages = (bool) 
                $skipDuplicatesOptionsByColumnIndexes[$columnIndex];
        }

        return $this->existProduct !== null && $isSkipDuplicateImages;
    } // end _isEnabledSkipDuplicatesImagesOption
    
    private function _isExistsColumnIndex($columnIndex)
    {
        $optionKey = 'product_image_skip_duplicates';
        $this->config[$optionKey];
        
        return array_key_exists($columnIndex, $this->config[$optionKey]);
    } // end _isExistsColumnIndex
    
    private function _isDuplicateImage($source, $numCol)
    {
        if (!$this->_isEnabledSkipDuplicatesImagesOption($numCol)) {
            return false;
        }

        $facade = EngineFacade::getInstance();
        $engineClassName = get_class($facade);

        $existingAttachmentQuery = array(
            'numberposts' => 1,
            'meta_key' => '_import_source',
            'post_status' => 'inherit',
            'post_parent' => $this->existProduct->ID,
            $engineClassName::META_QUERY_KEY => array(
                array(
                    'key'=>'_import_source',
                    'value'=> $source,
                    'compare' => '='
                )
            ),
            'post_type' => 'attachment'
        );
        
        $existingAttachments = get_posts($existingAttachmentQuery);
        
        if ($this->_hasImageInProductData($existingAttachments)) {
            $message = __(
                'Skipping import of duplicate image %s.',
                $this->languageDomain
            );
            
            $this->newPostMessages[] = sprintf(
                $message,
                $source
            );
            return true;
        }
        
        return false;
    } // end _isDuplicateImage
    
    private function _isExistsLocalImage($path)
    {
        if (!file_exists($path)) {
            $message = __(
                'Couldn\'t find local file %s.',
                $this->languageDomain
            );
                
            $this->newPostErrors[] = sprintf($message, $path);
            return false;
        }
        
        return true;
    } // end _isExistsLocalImage
    
    protected function doInsertAtachmentImage($path)
    {
        $destUrl = str_ireplace(ABSPATH, home_url('/'), $path);
        
        $pathParts = pathinfo($path);

        $wpFiletype = wp_check_filetype($path);
        
        $postTitle = preg_replace(
            '/\.[^.]+$/',
            '',
            $pathParts['filename']
        );
        
        $idAttachment = $this->_getExistsAttachmentID(
            $path,
            $wpFiletype['type']
        );
        
        if ($idAttachment) {
            return $idAttachment;
        }
        
        $attachment = array(
            'guid' => $destUrl,
            'post_mime_type' => $wpFiletype['type'],
            'post_title' => $postTitle,
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $facade = EngineFacade::getInstance();
        
        $idAttachment = $facade->addAttachment(
            $this->idNewPost,
            $attachment,
            $path
        );
        
        return $idAttachment;
    } // end doInsertAtachmentImage
    
    private function _getExistsAttachmentID($path, $fileType)
    {   
        $facade = EngineFacade::getInstance();
        $idPostParent = $this->_getCurrentProductID();
        
        $attachments = $facade->getAttachmentsByPostID(
            $idPostParent,
            $fileType
        );
        
        if (is_array($attachments)) {
            foreach ($attachments as $post) {
                $postPath = $facade->getAbsolutePath($post->guid);
                
                if ($this->_isFileEquals($path, $postPath)) {
                    return $post->ID;
                }              
            } 
        }
        
        return false;
    } // end _getExistsAttachmentID
    
    private function _getCurrentProductID()
    {
        return $this->existProduct->ID;
    } // end _getCurrentProductID
    
    private function _isFileEquals($pathOne, $pathTwo)
    {
        $hashOne = sha1_file($pathOne);
        $hashTwo = sha1_file($pathTwo);
        
        return $hashOne == $hashTwo;
    } // end _isFileEquals

    private function _updateAttachmentMetaData($idAttachment, $path)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $facade = EngineFacade::getInstance();

        $attachData = $facade->doGenerateAttachmentMetaData(
            $idAttachment,
            $path
        );

        $facade->updateAttachmentMetadata($idAttachment, $attachData);
    } // end _updateAttachmentMetaData
    
    protected function updateProductImagePostMeta($idAttachment, $source)
    {
        $facade = EngineFacade::getInstance();

        $result = $facade->addPostMeta(
            $idAttachment,
            '_import_source',
            $source,
            true
        );
                
        if (!$result) {
            $facade->updatePostMeta(
                $idAttachment,
                '_import_source',
                $source
            ); 
        }
    } // end updateProductImagePostMeta
    
    private function _isEnabledFirstImageFeaturedOption($columnIndex)
    {
        $setFeatureOptions = $this->config['product_image_set_featured'];
        
        if (array_key_exists($columnIndex, $setFeatureOptions)) {
            
            return (bool) $setFeatureOptions[$columnIndex];
        }
        
        return false;
    } // end _isEnabledFirstImageFeaturedOption
    
    private function _isFirstImage($imageIndex)
    {
        return $imageIndex == 0;
    } // end _isFirstImage

    private function _updateProductAtachmentData($numCol)
    {
        $imageGalleryIds = array();

        $facade = EngineFacade::getInstance();
        
        foreach ($this->newPostImagePaths as $imageIndex => $destPathInfo) {
            $path = $destPathInfo['path'];
            $source = $destPathInfo['source'];
            
            if ($this->_isDuplicateImage($source, $numCol)) {
                return false;
            }

            if (!$this->_isExistsLocalImage($path)) {
                return false;
            }
            
            $attachmentId = $this->doInsertAtachmentImage($path);
            
            $this->_updateAttachmentMetaData($attachmentId, $path);

            $this->updateProductImagePostMeta($attachmentId, $source);
            
            $setFeatured = $this->_isEnabledFirstImageFeaturedOption($numCol);
        
            if (!$this->_isFirstImage($imageIndex) || !$setFeatured) {
                $imageGalleryIds[] = $attachmentId;
                continue;
            }

            $facade->updatePostMeta(
                $this->idNewPost,
                '_thumbnail_id',
                $attachmentId
            );
        }
        
        $this->updateProductImageGallery($imageGalleryIds);
    } // end _updateProductAtachmentData
    
    public function updateProductImageGallery($imageGalleryIds)
    {
        $facade = EngineFacade::getInstance();

        if ($this->_hasImageGalleryIds($imageGalleryIds)) {
            $facade->updatePostMeta(
                $this->idNewPost,
                '_product_image_gallery',
                implode(',', $imageGalleryIds)
            );
        }
    } // end updateProductImageGallery
    
    private function _hasImageGalleryIds($imageGalleryIds)
    {
        return count($imageGalleryIds) > 0;
    } // end _hasImageGalleryIds
    
    private function _hasImageInProductData($existingAttachments)
    {
        return is_array($existingAttachments) &&
               sizeof($existingAttachments) > 0;
    } // end _hasImageInProductData
    
    private function _updateProductImagesByUrl()
    {
        if (!$this->newPostImageUrls) {
            return false;
        }
        
        foreach ($this->newPostImageUrls as $imageIndex => $imageUrl) {
            $imageUrl = str_replace(' ', '%20', trim($imageUrl));
            $parsedUrl = parse_url($imageUrl);
            $pathinfo = pathinfo($parsedUrl['path']);
            $imageExt = strtolower($pathinfo['extension']);
            
            if (!$this->_isAllowedImageExtension($imageExt, $imageUrl)) {
                return false;
            }
            
            $destFilename = wp_unique_filename(
                $this->uploadImagesDir['path'],
                $pathinfo['basename']
            );
            
            $destPath = $this->uploadImagesDir['path'].'/'.$destFilename;
            
            $this->copyImageFromUrl($imageUrl, $destPath);
            
            if (!file_exists($destPath)) {
                $message = __(
                    'Couldn\'t download file %s.', 
                    $this->languageDomain
                );
                
                $this->newPostErrors[] = sprintf($message, $imageUrl);
                return false;
            }
    
            $this->newPostImagePaths[] = array(
                'path' => $destPath,
                'source' => $imageUrl
            );
        }
    } // end _updateProductImagesByUrl
    
    protected function copyImageFromUrl($imageUrl, $destPath)
    {
        if ($this->_isAllowUrlFopen()) {
            $result = @copy($imageUrl, $destPath);
            
            if (!$result) {
                $message = __(
                    'Error Encountered while attempting to download %s',
                    $this->languageDomain
                );
                $this->newPostErrors[] = sprintf($message, $imageUrl);
            }
        } elseif (function_exists('curl_init')) {
            $this->_copyImagesWithCurlFunction($imageUrl, $destPath);
        }
    } // end copyImageFromUrl
    
    private function _copyImagesWithCurlFunction($imageUrl, $destPath)
    {
        $ch = curl_init($imageUrl);
        $fp = fopen($destPath, "wb");

        $options = array(
            CURLOPT_FILE => $fp,
            CURLOPT_HEADER => 0,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_TIMEOUT => 60
        );

        curl_setopt_array($ch, $options);
        curl_exec($ch);
        
        $result = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        $httpStatus = intval($result);
        
        curl_close($ch);
        
        fclose($fp);

        if ($httpStatus == 200) {
            return true;    
        }
        
        unlink($destPath);
        
        $message = __(
            'HTTP status %s encountered while attempting to download %s',
            'woo-product-importer'
        );
        
        $this->newPostErrors[] = sprintf($message, $httpStatus, $imageUrl);
    } // end _copyImagesWithCurlFunction
    
    private function _isAllowUrlFopen()
    {
        return ini_get('allow_url_fopen');
    } // end _isAllowUrlFopen

    private function _isAllowedImageExtension($imageExt, $imageUrl)
    {
        $allowedExtensions = array('jpg', 'jpeg', 'gif', 'png');
        
        if (in_array($imageExt, $allowedExtensions)) {
            return true;
        }
        
        $message = __(
            'A valid file extension wasn\'t found in %s. Extension '.
            'found was %s. Allowed extensions are: %s.', 
            $this->languageDomain
        );
        
        $this->newPostErrors[] = sprintf(
            $message,
            $imageUrl,
            $imageExt,
            implode(',', $allowedExtensions)
        );

        return false;
    } // end _isAllowedImageExtension
    
    private function _updatePostTerms()
    {
        $facade = EngineFacade::getInstance();

        foreach ($this->newPostTerms as $tax => $termIds) {
            $facade->setObjectTerms($this->idNewPost, $termIds, $tax);
        }

        $stockStatus = $this->newPostMeta['_stock_status'];

        $facade->setObjectTerms(
            $this->idNewPost,
            $stockStatus,
            'product_visibility'
        );
    } // end _updatePostTerms

    private function _updateProductAttributes()
    { 
        if ($this->existProduct !== null) {
            $this->_loadProductExistsAttributes();
        }
        
        $facade = EcommerceFactory::getInstance();
        
        $attributes = array();
        $attributesValues = array();

        foreach ($this->newPostCustomFields as $attrName => $attrOption) {
            if (!$this->_hasOptionNameInAttributes($attrOption)) {
                continue;
            }
            $option = $this->config['attributes'][$attrOption['name']];
            $attrKey = $facade->getAttributeNameByKey($option['ident']);
          
            $attributes[$attrKey] = $this->_getProductAttribute($option);
            
            $attributesValues[$attrKey] = $facade->getProductAttributeValues(
                $this->idNewPost,
                $attrKey
            );
            
            $attributesValues[$attrKey][] = $attrOption['value'];
        }
        
        if ($attributes) {
            $facade->updateProductAttributes($this->idNewPost, $attributes);
        }
        
        $this->_doUpdateProductAttributesValues($attributesValues);
        
        return true;
    } // end _updateProductAttributes
    
    private function _hasOptionNameInAttributes($option)
    {
        return array_key_exists('attributes', $this->config) && 
               array_key_exists('name', $option) && 
               array_key_exists($option['name'], $this->config['attributes']);
    } // end _hasOptionNameInAttributes
    
    private function _getProductAttribute($option)
    {
        $facade = EcommerceFactory::getInstance();
        
        $isVisible = $this->_isProductAttributeVisible($option['index']);
        $attrKey = $facade->getAttributeNameByKey($option['ident']);

        $productAttribute = array(
            'name'         => $attrKey,
            'value'        => '',
            'is_visible'   => $isVisible,
            'is_variation' => $option['is_variation'],
            'is_taxonomy'  => '1',
            'position'     => 0
        );
        
        return $productAttribute;
    } // end _getProductAttribute

    private function _doUpdateProductAttributesValues($attributesValues)
    {
        $facade = EcommerceFactory::getInstance();

        foreach ($attributesValues as $attrName => $attrValues) {
            $attrValues = array_unique($attrValues);
            $facade->updateProductAttributeValues(
                $this->idNewPost,
                $attrName,
                $attrValues
            );
        }

        return true;
    } // end _doUpdateProductAttributesValues
    
    private function _isProductAttributeVisible($fieldIndex)
    {
        $visibleCustomFields = $this->config['custom_field_visible'];

        if (array_key_exists($fieldIndex, $visibleCustomFields)) {
            return true;
        }
        
        return false;
    } // end _isProductAttributeVisible
    
    private function _loadProductExistsAttributes()
    {
        $facade = EngineFacade::getInstance();

        $existingProductAttributes =  $facade->getPostMeta(
            $this->idNewPost,
            '_product_attributes',
            true
        );
        
        if (!is_array($existingProductAttributes)) {
            return false;
        }
        
        $maxPosition = 0;
        foreach ($existingProductAttributes as $fieldSlug => $fieldData) {
            $position = intval($fieldData['position']);
            $maxPosition = max($position, $maxPosition);
        }
        
        foreach ($this->newPostCustomFields as $fieldSlug => $fieldData) {
            $result = $this->_hasValueInDataArray(
                $fieldSlug,
                $existingProductAttributes
            );
            
            if ($result) {
                continue;
            }
            
            $this->newPostCustomFields[$fieldSlug]['position'] = ++$maxPosition;
        }
        
        $this->newPostCustomFields = array_merge(
            $existingProductAttributes,
            $this->newPostCustomFields
        );
    } // end _loadProductExistsAttributes
    
    private function _updatePostMeta()
    {
        $facade = EngineFacade::getInstance();

        foreach ($this->newPostMeta as $key => $value) {
            $result = $facade->addPostMeta(
                $this->idNewPost,
                $key,
                $value,
                true
            );

            if (!$result) {
                $facade->updatePostMeta($this->idNewPost, $key, $value);
            }
        }
    } // end _updatePostMeta
    
    private function _isValidateNewPostID()
    {
        if (is_wp_error($this->idNewPost)) {
            $message = __(
                'Couldn\'t insert product with name %s.',
                $this->languageDomain
            );
            
            $this->newPostErrors[] = sprintf(
                $message,
                $this->newPost['post_title']
            );
            return false;
        }
        
        if ($this->idNewPost == 0) {
            $message = __(
                'Couldn\'t update product with ID %s.',
                $this->languageDomain
            );
            
            $this->newPostErrors[] = sprintf(
                $message,
                $this->newPost['ID']
            );
            return false;
        }
        
        return true;        
    } // _isValidateNewPostID
    
    public function insertPost()
    {
        $this->newPost = array_merge(
            $this->newPostDefaults,
            $this->newPost
        );
        
        $this->newPostMeta = array_merge(
            $this->newPostMetaDefaults,
            $this->newPostMeta
        );

        $this->_onPrepareProductValues();
        
        $this->_createSimpleProduct();
        
        if (!$this->_isValidateNewPostID()) {
            $this->idNewPost = 0;
        }
        
        $this->newPostMessages[] = sprintf(
            __('Insert product with ID %s.', $this->languageDomain),
            $this->idNewPost
        );            
    } // end insertPost
    
    private function _createSimpleProduct()
    {
        $facade = EngineFacade::getInstance();

        $this->idNewPost = $facade->doInsertPost($this->newPost, true);
    } // end _createSimpleProduct

    public function updatePost()
    {
        $this->newPostMessages[] = sprintf(
            __('Updating product with ID %s.', $this->languageDomain),
            $this->existProduct->ID
        );
    
        $this->newPost['ID'] = $this->existProduct->ID;
        
        $this->_onPrepareProductValues();
        
        $this->_updateSimpleProduct();
    } // end updatePost
    
    private function _updateSimpleProduct()
    {
        $facade = EngineFacade::getInstance();

        $this->idNewPost = $facade->doUpdatePost($this->newPost);
    } // end _updateSimpleProduct
    
    /**
     * This method called every time before product values insert or update.
     */
    private function _onPrepareProductValues()
    {
        if ($this->_isVariationProduct()) {
            $this->_parentProductValuesObject = $this->_getParentProduct();
            
            $idParent = $this->_parentProductValuesObject->getID();
            $this->newPost['post_parent'] = $idParent;
            $this->newPost['post_title'] = 'Product #'.$idParent.' Variation';
            $this->newPost['post_type'] = 'product_variation';
        }
    } // end _onPrepareProductValues
    
    /**
     * Returns parent product for current variation product
     * @return WooCommerceProductValuesObject
     * @throws ImportWooProductException
     */
    private function _getParentProduct()
    {
        if ($this->_isEmptyParentSku()) {
            throw new ImportWooProductException(
                "Undefined _parent_sku for variation product."
            );
        }
        $key = WooMappingImportOptions::FIELD_PARENT_SKU;
        $parentSKU = $this->newPostMeta[$key];
        
        $facade = EcommerceFactory::getInstance();
        
        $parentProduct = $facade->loadProductValuesObjectBySKU($parentSKU);
        
        if (!$parentProduct) {
            throw new ImportWooProductException(
                "Not found parent for variation product.",
                ImportWooProductException::ERROR_CODE_NOT_FOUND_PARENT
            );
        }
        
        return $parentProduct;
    } // end _getParentProduct
    
    private function _isEmptyParentSku()
    {
        return empty(
            $this->newPostMeta[WooMappingImportOptions::FIELD_PARENT_SKU]
        );
    } // end _isEmptyParentSku
    
    private function _isVariationProduct()
    {
        return array_key_exists(
            WooMappingImportOptions::FIELD_PARENT_SKU, 
            $this->newPostMeta
        );
    } // end _isVariationProduct
    
    public function getImportReport()
    {
        $reportData  = array(
            'post_id' => '',
            'name' => '',
            'sku' => '',
            'has_errors' => false,
            'errors' => $this->newPostErrors,
            'has_messages' => false,
            'messages' => $this->newPostMessages
        );
        
        if ($this->idNewPost) {
            $reportData['post_id'] = $this->idNewPost;
        }
        
        if (isset($this->newPost['post_title'])) {
            $reportData['name'] = $this->newPost['post_title'];    
        }

        if (!empty($this->newPostMeta['_sku'])) {
            $reportData['sku'] = $this->newPostMeta['_sku'];    
        }
        
        if (sizeof($this->newPostErrors) > 0) {
            $reportData['success'] = false;
            $reportData['has_errors'] = true;    
        } else {
            $reportData['success'] = true;
        }
        
        if (sizeof($this->newPostMessages) > 0) {
            $reportData['has_messages'] = true;    
        }
        
        return $reportData;
    } // end getImportReport
    
    private function _hasValidateProductNameInData()
    {
        return array_key_exists('post_title', $this->newPost) &&
               strlen($this->newPost['post_title']) > 0;
    } // end _hasValidateProductNameInData
    
    public function getPreparedUserRolePricesForProduct()
    {
        $festiValue = array();

        $facade = EngineFacade::getInstance();

        if ($this->existProduct !== null) {
            
            $idProduct = $this->existProduct->ID;
            
            $festiValue = $facade->getPostMeta(
                $idProduct,
                PRICE_BY_ROLE_PRICE_META_KEY,
                true
            );

            if (!is_null($festiValue) && !is_array($festiValue)) {
                $festiValue = json_decode($festiValue, true);
            }
        }
        
        if (!is_array($festiValue)) {
            $festiValue = array();
        }
        
        $this->newPricesByUserRole = array_merge(
            $festiValue,
            $this->newPricesByUserRole
        );
        
        return json_encode($this->newPricesByUserRole);
    } // end getPreparedUserRolePricesForProduct
    
    private function _getExsistProduct()
    {
        $var = $this->newPostMeta;

        if (
            !$this->_hasValueInDataArray('_sku', $var) || 
            empty($var['_sku'])
        ) {
            return null;
        }
            
        $postType = 'product';
        if ($this->_isVariationProduct()) {
            $postType = 'product_variation';
        }

        $facade = EngineFacade::getInstance();
        $engineClassName = get_class($facade);

        $existingPostQuery = array(
            'numberposts' => 1,
            'meta_key'    => '_sku',
            'post_type'   => $postType,
            $engineClassName::META_QUERY_KEY => array(
                array(
                    'key'     =>'_sku',
                    'value'   => $this->newPostMeta['_sku'],
                    'compare' => '='
                )
            )
        );
        
        $existingPosts = get_posts($existingPostQuery);
        
        if (!is_array($existingPosts) || sizeof($existingPosts) <= 0) {
            return null;
        }
        
        return array_shift($existingPosts);
    } // end _getExsistProduct
    
    public function setStockStatusForProduct()
    {
        $var = $this->newPostMeta;
        
        if ($this->_hasValueInDataArray('_stock', $var)) {
            return (intval($var['_stock']) > 0) ? 'instock' : 'outofstock';
        }
        
        if ($this->_hasValueInDataArray('_stock_status', $var)) {
            $result = $var['_stock_status'] == 'instock';    
            return ($result) ? 'instock' : 'outofstock';
        }
        
        if ($this->existProduct) {
            return $this->getStockOptionOfExsistingProduct();
        }
        
        return 'instock';
    } // end setStockStatusForProduct
    
    public function getStockOptionOfExsistingProduct()
    {
        $idProduct = $this->existProduct->ID;
        $optionName = '_stock_status';

        $facade = EngineFacade::getInstance();

        $option = $facade->getPostMeta($idProduct, $optionName);

        return $option[0];
    } // end getStockOptionOfExsistingProduct
    
    public function setManageStockForProduct()
    {
        $var = $this->newPostMeta;

        if (!$this->_hasValueInDataArray('_manage_stock', $var)) {
            return false;
        }
   
        $result = $this->_hasValueInDataArray('_stock', $var);

        return ($result) ? 'yes' : 'no';
    }// end setManageStockForProduct

    private function _isUpdatePriceEnabled()
    {
        return $this->_isSalePriceExists() || $this->_isRegularPriceExists();
    } // end _isUpdatePriceEnabled

    /**
     * @throws ImportWooProductException
     */
    private function _doUpdatePriceForCurrentProduct()
    {
        $salePriceKey = WooCommerceProductValuesObject::SALE_PRICE_KEY;
        $regularPriceKey = WooCommerceProductValuesObject::REGULAR_PRICE_KEY;

        $price = false;

        if ($this->_isSalePriceExists()) {
            $price = $this->newPostMeta[$salePriceKey];
        }

        if ($this->_isRegularPriceExists() && !$price) {
            $price = $this->newPostMeta[$regularPriceKey];
        }

        $this->_setPriceForProduct($price);
    } // end _doUpdatePriceForCurrentProduct

    private function _isSalePriceExists()
    {
        return $this->_hasValueInDataArray(
            WooCommerceProductValuesObject::SALE_PRICE_KEY,
            $this->newPostMeta
        );
    } // end _isSalePriceExists

    private function _isRegularPriceExists()
    {
        return $this->_hasValueInDataArray(
            WooCommerceProductValuesObject::REGULAR_PRICE_KEY,
            $this->newPostMeta
        );
    } // end _isRegularPriceExists

    private function _setPriceForProduct($price)
    {
        $this->newPostMeta[WooCommerceProductValuesObject::PRICE_KEY] = $price;
    } // end setPriceForProduct
    
    private function _hasValueInDataArray($value, $data) 
    {
        return array_key_exists($value, $data);
    } // _hasValueInDataArray
    
    private function _deleteMappingOptionsGroup()
    {
        $onlyOptions = array();
        
        foreach ($this->mappingOptions as $item) {
            $onlyOptions = array_merge($onlyOptions, $item['options']);
        }
        
        return $onlyOptions;
    } // end _deleteMappingOptionsGroup
    
    private function _isValidateValue($value, $mapTo)
    {
        if (!array_key_exists($mapTo, $this->mappingOptions)) {
            return true;
        }
        
        $result = array_key_exists(
            'validationValues',
            $this->mappingOptions[$mapTo]
        );
        
        if (!$result) {
            return true;
        }
        
        $methodName = $this->mappingOptions[$mapTo]['validationValues'];

        $method = array($this->_engine->mappingOptions, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }

        $values = call_user_func_array($method, array());
        
        if (!$values) {
            throw new Exception($methodName."Not return Values");
        }

        return in_array($value, $values);
    } // end _isValidateValue
    
    private function _isNotImportColumn($key)
    {
        return $key == 'do_not_import';
    } // end _isNotImportColumn
    
    private function _getDefaultPostData()
    {
        $postData = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'post_title' => '',
            'post_content' => '',
            'menu_order' => 0,
        );
        
        return $postData;
    } // end _getDefaultPostData
    
    private function _getDefaultPostMetaData()
    {
        $postMetaData = array(
            '_visibility' => 'visible',
            '_featured' => 'no',
            '_weight' => 0,
            '_length' => 0,
            '_width' => 0,
            '_height' => 0,
            '_sku' => '',
            '_stock' => '',
            WooCommerceProductValuesObject::SALE_PRICE_KEY => '',
            '_sale_price_dates_from' => '',
            '_sale_price_dates_to' => '',
            '_tax_status' => 'taxable',
            '_tax_class' => '',
            '_purchase_note' => '',
            '_downloadable' => 'no',
            '_virtual' => 'no',
            '_backorders' => 'no',
            PRICE_BY_ROLE_PRICE_META_KEY => ''
        );
        
        return $postMetaData;
    } // end _getDefaultPostMetaData
}