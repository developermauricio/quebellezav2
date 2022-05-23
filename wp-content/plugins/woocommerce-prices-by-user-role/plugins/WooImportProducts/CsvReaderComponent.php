<?php

class CsvReaderComponent
{
    private $_handle;

    public function __construct($file, $delimiter)
    {
        $this->_handle = $this->_getHandle($file);
        $this->delimiter = $delimiter;
    } // end __construct
    
    public function getNextRow()
    {
        $row = fgetcsv($this->_handle, 0, $this->delimiter);

        if (!is_array($row)) {
            return $row;
        }

        foreach ($row as $key => &$value) {
            $value = $this->_getPreparedEncoding($value);
        }

        return $row;
    } // end getNextRow
    
    public function resetHandle()
    {
        fseek($this->_handle, 0);
    } // end resetHandle
    
    private function _getHandle($file)
    {
        if (!file_exists($file)) {
            $message = "Not exists file ".$file;
            throw new Exception($message);
        }
        
        $handle = fopen($file, "r");
        if ($handle === false) {
            $message = "Error reading file";
            throw new Exception($message);
        }
        
        return $handle;
    } // end _getHandle
    
    public function __destruct()
    {
        fclose($this->_handle);
    } // end __destruct

    private function _getPreparedEncoding($value)
    {
        $encoding = mb_detect_encoding($value);

        if (empty($encoding)) {
            $value = mb_convert_encoding($value, 'UTF-8');
        }

        return $value;
    } // end _getPreparedEncoding
}