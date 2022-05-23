<?php 

require_once 'bundle/object/SystemObject.php';

class PriceByUserRoleCoreObject extends SystemObject
{
    public function getRolePricesRowByPostID($tableName, $search)
    {
        $sql = "SELECT * FROM ".$tableName;

        return $this->select($sql, $search, array(), self::FETCH_ROW);
    } // end getRolePricesRowByPostID

    public function isModuleActive($moduleName)
    {
        $search = array(
            'ident' => $moduleName,
            'status' => 'active'
        );

        $core = Core::getInstance();

        $systemPlugin = $core->getPluginInstance('Jimbo');

        $result = $systemPlugin->getObject()->getPlugins($search);

        return (bool)$result;
    } // end isModuleActive
}