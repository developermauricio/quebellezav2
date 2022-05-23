<?php
class Display extends Entity
{
    /**
     * @var string
     */
    private $__path;
    
    /**
     * Display constructor.
     * @param string $path
     */
    public function __construct(string $path)
    {
        parent::__construct();

        $this->__path = $path;
    } // end __construct
    
    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->__path;
    } // end getPath
    
    /**
     * @param string $path
     */
    public function setTemplatePath($path)
    {
        $this->__path = $path;
    } // end setTemplatePath
    
    /**
     * @param $key
     * @param mixed $value
     * @return bool
     */
    public function assign($key, $value = null): bool
    {
        if (!is_array($key)) {
            $this->$key = $value;
            return true;
        }

        foreach ($key as $varName => $varValue) {
             $this->$varName = $varValue;
        }

        return true;
    } // end assign
    
    /**
     * @param string $file
     * @return bool
     */
    public function isTemplateExists($file): bool
    {
        $templatePath = $this->__path.$file;

        return file_exists($templatePath);
    } // end isTemplateExists
    
    /**
     * @param string $file
     * @return string
     */
    public function getTemplateFilePath(string $file): string
    {
        return $this->getPath().$file;
    } // end getTemplatePath
    
    /**
     * @param string $file
     * @param array|null $localVars
     * @param string|null $templatePath
     * @return string
     * @throws SystemException
     */
    public function fetch(string $file, array $localVars = null, string $templatePath = null): string
    {
        $vars = $this->getArrayCopy();

        if ($templatePath) {
            $templatePath .= $file;
        } else {
            $templatePath = $this->getTemplateFilePath($file);
        }

        if (!file_exists($templatePath) || !is_file($templatePath)) {
            throw new SystemException('Template file not found '.$templatePath);
        }

        extract($vars);
        if ($localVars) {
            extract($localVars);
        }

        ob_start();

        include($templatePath);

        $result = ob_get_contents();
        ob_end_clean();

        return $result;
    } // end fetch

}