<?php

class SetField extends AbstractField
{
    public $valuesList;

    public function onInit(FieldModel $scheme)
    {
        parent::onInit($scheme);
        
        if (!$scheme->hasOptions()) {
            return false;
        }
        
        // FIXME:
        $options = $scheme->getOptions();
        foreach ($options as $item) {
            $value = (string) $item;
            
            $attr = $item->attributes();
            $this->valuesList[(string)$attr['id']] = $value;
        }
    } // end onInit
    
    /**
     * @override
     */
    public function getEditInput(?string $value = '', $inline = null): ?string
    {
        $tpl = dbDisplayer::getTemplateInstance();

        $tpl->assign("valuesList", $this->valuesList);
        $tpl->assign("currentValues", explode(",", $value));
        $tpl->assign("name", $this->getName());

        return $tpl->fetch("fields/set/edit.tpl");
    }
    
    /**
     * @override
     */
    public function displayValue(?string $value, array $row = array()): ?string
    {
        return $value;
    }
    
    public function getValue($requests = array())
    {
        $value = parent::getValue($requests);

        if (!$value && isset($this->attributes['isnull'])) {
            $value = null;
            return $value;
        }

        return join(",", $value);
    } // end getValue
    
}

?>