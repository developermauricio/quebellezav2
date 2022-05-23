<?php

use core\stage\StageException;

class StageBuilder
{
    const REQUEST_KEY_BLOCK_INDEX = '_stageBlock';
    
    const LAYOUT_ROWS = "rows";
    const LAYOUT_COLUMNS = "columns";
    const LAYOUT_MASONRY = "masonry";
    
    
    private $_layout = false;
    private $_view;
    
    private $_maxIndex = 0;
    private $_blocks;
    
    private $_size;
    private $_maxLength = 12;
    
    public function __construct(int $size = 3, $layout = false, $view = false)
    {
        $this->_size = $size;
        $this->_layout = $layout ? $layout : static::LAYOUT_ROWS;
        
        if (!$view) {
            $path = Core::getInstance()->getOption(
                'filter_template_path'
            );
            $view = new Display($path);
        }
        
        $this->_view = $view;
        $this->_blocks = array();
    }
    
    public function setSize($size)
    {
        $this->_size = $size;
    }
    
    public function setLayout($layout)
    {
        $this->_layout = $layout;
    }
    
    protected function getLayout()
    {
        return $this->_layout;
    }
    
    public function addBlock($instance, int $size = 1, $className = false) : int
    {
        if (!$className) {
            $className = $this->_getBlockClassName($instance);
        }
        
        $index = $this->_maxIndex;
        
        $this->_blocks[$index] = new $className(
            $instance, 
            $index, 
            $size, 
            $this->_view
        );
        
        $this->_maxIndex++;
        
        return $index;
    } // end addBlock
    
    private function _getBlockClassName(&$instance)
    {
        if ($instance instanceof Store) {
            $className = "StoreStageBlock";
        } else if ($instance instanceof AbstractDisplayAction) {
            $className = "StoreActionStageBlock";
        } else if (is_array($instance)) {
            if (!is_callable($instance)) {
                throw new StageException(
                    "Not found callback for stage block type."
                );
            }
            
            $className = "CallbackStageBlock";
        } else if (is_string($instance)) {
            $className = "ContentStageBlock";
        } else {
            throw new StageException("Undefined stage block type.");
        }
        
        return $className;
    } // end _getBlockClassName
    
    
    public function addStoreBlock(Store $store, int $size = 1) : int
    {
        return $this->addBlock($store, $size, 'StoreStageBlock');
    } // end addStoreBlock
    
    public function addStoreActionBlock($store, $size = 1)
    {
        return $this->addBlock($store, $size, 'StoreActionStageBlock');
    } // end addStoreActionBlock
    
    public function addCallbackBlock(
        $callback, $externalParams = false, $size = 1
    )
    {
        $index =  $this->addBlock($callback, $size, 'CallbackStageBlock');
        
        if ($externalParams) {
            $this->_blocks[$index]->setExternalParams($externalParams);
        }
        
        return $index;
    } // end addCallbackBlock
    
    public function addUrlBlock($store, $size = 1)
    {
        return $this->addBlock($store, $size, 'UrlStageBlock');
    } // end addUrlBlock
    
    public function onRequest(Response &$response)
    {
        if ($this->_isExecBlockRequest()) {

            $blockIndex = $this->_getExecutedBlockIndexFromRequest();
            $blockInstance = $this->_getBlockInstanceByIndex($blockIndex);

            return $blockInstance->exec($response);
        }
        
        $response->content = $this->fetchLayout();
        
        return true;
    } // end onRequest

    private function _getBlockInstanceByIndex($index) : StageBlock
    {
        if (!array_key_exists($index, $this->_blocks)) {
            throw new StageException("Undefined stage block with index: ".$index);
        }

        return $this->_blocks[$index];
    } // end _getBlockInstanceByIndex
    
    protected function fetchLayout()
    {
        $defaultLength = $this->_maxLength / $this->_size;
        
        $grid = array();
        
        $rowIndex = 0;
        $columnIndex = 0;
        
        $currentLength = 0;
        
        foreach ($this->_blocks as $index => $block) {
        
            $blockSize = $block->getSize();
            $lenght = $blockSize * $defaultLength;
        
            if ($currentLength >= $this->_maxLength) {
                $rowIndex++;
                $columnIndex = 0;
                $currentLength = 0;
            }
        
            $grid[$rowIndex][$columnIndex]['length'] = $lenght;
            $grid[$rowIndex][$columnIndex]['content'] = $block->exec();
        
            $currentLength += $lenght;
            $columnIndex++;
        }
        
        $this->_view->grid = $grid;
        $this->_view->defaultLength = $defaultLength;
        $this->_view->size = $this->_size;
        
        $templateName = "stage/layout_".$this->getLayout();
        
        return $this->_view->fetch($templateName.'.phtml');
    } // end fetchView
    
    
    private function _getExecutedBlockIndexFromRequest() : ?int
    {
        if (!array_key_exists(StageBuilder::REQUEST_KEY_BLOCK_INDEX, $_REQUEST)) {
            return null;
        }

        return (int) $_REQUEST[StageBuilder::REQUEST_KEY_BLOCK_INDEX];
    } // end _getExecutedBlockIndexFromRequest
    
    private function _isExecBlockRequest()
    {
        $blockIndex = $this->_getExecutedBlockIndexFromRequest();

        if (is_null($blockIndex)) {
            return false;
        }

        if (Response::isAjaxRequest()) {
            return true;
        }

        $blockInstance = $this->_getBlockInstanceByIndex($blockIndex);

        return $blockInstance->isExec();
    } // end _isExecBlockRequest
    
}