<?php
 
require_once 'bundle/object/ISystemObject.php';
 
class SystemObject extends DataAccessObject implements ISystemObject
{
    const TABLE_PLUGINS         = "plugins";
    const TABLE_SECTIONS        = "sections";
    const TABLE_SETTINGS        = 'settings';
    const TABLE_SECTION_ACTIONS = "section_actions";
    const TABLE_SECTION_USER_TYPE = "sections_user_types_permission";
    const TABLE_SECTION_USERS = "sections_user_permission";
    const TABLE_URL_AREAS       = "url_areas";
    
    const COLUMN_IDENT = "ident";
    
    public function getPrefix()
    {
        return 'festi_';
    } // end getPrefix
    
    public function getSettings()
    {
        $sql = "SELECT name, value FROM ".$this->getPrefix().static::TABLE_SETTINGS;
        
        return $this->getAssoc($sql);
    } // end getSettings
     
    public function getUrlRules($search)
    {
        $sql = "SELECT 
                    ".$this->getPrefix()."url_rules.*
                FROM
                    ".$this->getPrefix()."url_rules2areas
                    INNER JOIN ".$this->getPrefix()."url_rules ON (".
                        $this->getPrefix()."url_rules2areas.id_url_rule = ".$this->getPrefix()."url_rules.id
                    )
                    INNER JOIN ".$this->getPrefix()."url_areas as areas ON (
                        areas.ident = ".$this->getPrefix()."url_rules2areas.area
                    )";
        
        return $this->select($sql, $search);
    } // end getUrlRules
    
    public function searchUrlRules($search)
    {
        $sql = "SELECT 
                    ".$this->getPrefix()."url_rules.*
                FROM
                    ".$this->getPrefix()."url_rules";
        
        return $this->select($sql, $search);
    } // end searchUrlRules
    
    public function addUrlRule($values)
    {
        return $this->insert($this->getPrefix()."url_rules", $values);
    } // end addUrlRule
    
    public function addUrlRulesToAreas($values)
    {
        return $this->massInsert($this->getPrefix()."url_rules2areas", $values);
    } // end addUrlRulesToAreas
    
    public function isInstalled()
    {
        $sql = "SHOW TABLES LIKE '".$this->getPrefix()."settings'";
        $res = $this->getOne($sql);
        
        return !empty($res);
    } // end isInstalled
    
    public function getUrlAreas($search = array())
    {
        $sql = "SELECT id, ident FROM ".$this->getTable(static::TABLE_URL_AREAS);

        return $this->select($sql, $search, array(), self::FETCH_ASSOC);        
    } // end getUrlAreas
    
    public function addPlugin($values)
    {
        return $this->insert($this->getPrefix().static::TABLE_PLUGINS, $values);
    } // end addPlugin
    
    /**
     * @param array $values
     * @param array|string $search
     * @return mixed
     */
    public function changePlugin(array $values, $search)
    {
        if (is_scalar($search)) {
            $search = array(
                static::COLUMN_IDENT => $search
            );
        }
        $table = $this->getTable(static::TABLE_PLUGINS);
        return $this->update($table, $values, $search);
    } // end changePlugin
    
    /**
     * Returns data about a plugin.
     * 
     * @param array $search
     * @return array
     * @throws DatabaseException
     */
    public function getPlugin($search): array
    {
        if (is_scalar($search)) {
            $search = array(
                static::COLUMN_IDENT => $search
            );
        }
        
        $sql = "SELECT * FROM ".$this->getTable(static::TABLE_PLUGINS);
        
        return $this->select($sql, $search, false, static::FETCH_ROW);
    } // end getPlugin

    /**
     * Returns list of plugins.
     *
     * @param array $search
     * @return array
     * @throws DatabaseException
     */
    public function getPlugins($search): array
    {
        $sql = "SELECT * FROM ".$this->getTable(static::TABLE_PLUGINS);

        return $this->select($sql, $search, false);
    } // end getPlugins
    
    public function addUrlAreas($areas)
    {
        if (is_scalar($areas)) {
            $areas = array($areas);
        }
        
        $values = array();
        foreach ($areas as $area) {
            $values[] = array(
                static::COLUMN_IDENT => $area
            );
        }
        
        return $this->massInsert($this->getTable(static::TABLE_URL_AREAS), $values);
    } // end addUrlAreas
    
    public function getSection($search)
    {
        if (is_scalar($search)) {
            $search = array(
                static::COLUMN_IDENT => $search
            );
        }
        
        $sql = "SELECT * FROM ".$this->getTable(static::TABLE_SECTIONS);
        return $this->select($sql, $search, array(), self::FETCH_ROW);
    } // end getSection
    
    public function changeSections($values, $search)
    {
        if (is_scalar($search)) {
            $search = array(
                static::COLUMN_IDENT => $search
            );
        }
        
        return $this->update($this->getTable(static::TABLE_SECTIONS), $values, $search);
    } // end changeSection
    
    public function addSection($values)
    {
        return $this->insert($this->getTable(static::TABLE_SECTIONS), $values);
    } // end addSection
    
    public function getSectionAction($search)
    {
        $sql = "SELECT * FROM ".$this->getTable(static::TABLE_SECTION_ACTIONS);
        
        return $this->select($sql, $search, array(), self::FETCH_ROW);
    } // end getSectionAction
    
    public function addSectionAction($values)
    {
        return $this->insert($this->getTable(static::TABLE_SECTION_ACTIONS), $values);
    } // end addSectionAction
    
    public function changeSectionAction($values, $search)
    {
        return $this->update($this->getTable(static::TABLE_SECTION_ACTIONS), $values, $search);
    } // end changeSectionAction

    public function addUserTypesToSection($values)
    {
        $this->massInsert($this->getTable(static::TABLE_SECTION_USER_TYPE), $values);
    } // end addUserTypesToSection

    public function addUsersToSection($values)
    {
        $this->massInsert($this->getTable(static::TABLE_SECTION_USERS), $values);
    } // end addUsersToSection

    protected function getTable(string $name): string
    {
        return $this->getPrefix().$name;
    } // end _getTableName
}
