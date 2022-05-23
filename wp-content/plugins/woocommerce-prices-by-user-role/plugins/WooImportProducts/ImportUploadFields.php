<?php

class ImportUploadFields extends CsvWooProductsImporter
{
    public function __construct($languageDomain)
    {
        $this->languageDomain = $languageDomain;
    } // end __construct
    
    public function get($options = array())
    {
        $defaultValues = array(
            'csvSeparator' => array(
                'type' => FestiObject::FIELD_TYPE_STRING,
                'default' => ','
            ),
            'categorySeparator' => array(
                'type' => FestiObject::FIELD_TYPE_STRING,
                'default' => '/'
            ),
            'decimalSeparator' => array(
                'type' => FestiObject::FIELD_TYPE_STRING,
                'default' => '.'
            ),
            'isFirstRowHeader' => FestiObject::FIELD_TYPE_STRING
        );
        
        $values = $this->getPreparedData($options, $defaultValues);
        
        $settings = array(
            'importFile' => array(
                'caption' => __(
                    'File to Import',
                    $this->languageDomain
                ),
                'type' => 'input_file'
            ),
            'importUrl' => array(
                'caption' => __(
                    'URL to Import',
                    $this->languageDomain
                ),
                'type' => 'input_text',
                'class' => 'festi-user-role-prices-import-url',
                'hint' => __(
                    'Enter the full URL to a CSV file. Leave this field '.
                    ' blank if uploading a file.',
                    $this->languageDomain
                ),
            ),
            'isFirstRowHeader' => array(
                'type' => 'input_checkbox',
                'lable' => __(
                    'First Row is Header',
                    $this->languageDomain
                ),
                'value' => $values['isFirstRowHeader']
            ),
            'uploadFolderPath' => array(
                'type' => 'text',
                'caption' => __(
                    'Path to Uploads Folder',
                    $this->languageDomain
                ),
                'text' => __(
                    $this->getUploadDir(),
                    $this->languageDomain
                ),
                'value' => $this->getUploadDir()
            ),
            'csvSeparator' => array(
                'caption' => __(
                    'CSV Field Separator',
                    $this->languageDomain
                ),
                'type' => 'input_text',
                'value' => $values['csvSeparator'],
                'hint' => __(
                    'Enter the character used to separate each field in your '.
                    'CSV',
                    $this->languageDomain
                ),
            ),
            'categorySeparator' => array(
                'caption' => __(
                    'Category Hierarchy Separator',
                    $this->languageDomain
                ),
                'type' => 'input_text',
                'value' => $values['categorySeparator'],
                'hint' => __(
                    'Enter the character used to separate categories in a '.
                    'hierarchical structure',
                    $this->languageDomain
                ),
            ),
            'decimalSeparator' => array(
                'caption' => __(
                    'Decimal Separator',
                    $this->languageDomain
                ),
                'type' => 'input_text',
                'value' => $values['decimalSeparator'],
                'hint' => __(
                    'Enter the decimal separator of prices.',
                    $this->languageDomain
                ),
            ),
        );
        
        return $settings;
    } // end get
}