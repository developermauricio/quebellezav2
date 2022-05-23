<?php 

require_once 'bundle/object/SystemObject.php';

class JimboObject extends SystemObject
{
    public function getMenu($search, $orderBy = array())
    {
        $idUser = isset($search['id_user']) ?  $search['id_user'] : false;
        unset($search['id_user']);

        $idRole = false;
        if (isset($search['id_user_type'])) {
            $idRole = $search['id_user_type'];
            unset($search['id_user_type']);
        }

        $sql = "SELECT 
                    m.* 
                FROM 
                    festi_menus m 
                    LEFT JOIN festi_menu_permissions p ON (p.id_menu = m.id) 
                    ";

        if ($idRole) {
            $sql .= " LEFT JOIN festi_sections_user_types_permission sections ON
                    (
                        sections.id_section = m.id_section AND 
                        sections.id_user_type = ".$this->quote($idRole)."
                    )";
        } else {
            $sql .= " LEFT JOIN festi_sections_user_types_permission sections ON
                    (
                        sections.id_section = m.id_section AND 
                        p.id_role =  sections.id_user_type
                    )";
        }

        if ($idUser) {
            $sql .= " LEFT JOIN 
                        festi_sections_user_permission user_sections ON (
                            user_sections.id_section = m.id_section AND 
                            user_sections.id_user = ".$this->quote($idUser)."
                        )";
        } else {
            $sql .= " LEFT JOIN 
                        festi_sections_user_permission user_sections ON (
                            user_sections.id_section = m.id_section
                        )";
        }


        return $this->select($sql, $search, $orderBy);
    } // end getMenu
    
    public function getUrlRules($search)
    {
        $sql = "SELECT 
                    festi_url_rules.*
                FROM
                    festi_url_rules2areas
                    INNER JOIN festi_url_rules ON (
                        festi_url_rules2areas.id_url_rule = festi_url_rules.id
                    )
                    INNER JOIN festi_url_areas as areas ON (
                        areas.ident = festi_url_rules2areas.area
                    )";
        
        return $this->select($sql, $search);
    } // end getUrlRules
    
    public function getSettings()
    {
        $sql = "SELECT name, value FROM festi_settings";
        
        return $this->getAssoc($sql);
    } // end getSettings
    
    public function getUser($tableName, $search)
    {
        $sql = "SELECT * FROM ".$tableName;
        
        if (is_scalar($search)) {
            $search = array(
                $tableName.'.id' => $search
            );
        }

        return $this->select($sql, $search, array(), self::FETCH_ROW);
    } // end getUser
    
    public function addUser($tableName, $values)
    {
        return $this->insert($tableName, $values);
    } // end addUser
    
    public function changeUser($tableName, $values, $search)
    {
        return $this->update($tableName, $values, $search);
    }
    
    public function getListeners($search)
    {
        $sql = "SELECT * FROM festi_listeners";
        
        return $this->select($sql, $search);
    } // end getListeners
    
    public function removePlugins($search)
    {
        return $this->delete("festi_plugins", $search);
    } // end removePlugins
    
    public function addPlugins($values)
    {
        return $this->massInsert("festi_plugins", $values);
    } // end removePlugins
    
    public function searchUrlRule($search)
    {
        $sql = "SELECT * FROM festi_url_rules";
        
        return $this->select($sql, $search, array(), self::FETCH_ROW);
    } // end searchUrlRules
    
    public function addUrlRule($values)
    {
        return $this->insert("festi_url_rules", $values);
    } // end addUrlRule
    
    public function searchUrlArea($search)
    {
        $sql = "SELECT * FROM festi_url_areas";
        
        return $this->select($sql, $search, array(), self::FETCH_ROW);
    } // end addUrlRule
    
    public function addUrlArea($values)
    {
        return $this->insert("festi_url_areas", $values);
    } // end addUrlArea
    
    public function addUrlRuleToArea($values)
    {
        return $this->insert("festi_url_rules2areas", $values, true);
    }
    
    public function getMenuItem($search)
    {
        $sql = "SELECT * FROM festi_menus";
        
        return $this->select($sql, $search, array(), self::FETCH_ROW);
    } // end getMenuItem
    
    public function addMenuItem($values)
    {
        return $this->insert('festi_menus', $values);
    }
    
    public function getText($search)
    {
        $sql = "SELECT text FROM festi_texts";
        
        return $this->select($sql, $search, array(), self::FETCH_ONE);
    }

    public function getSectionsActionsByUser(DefaultUser $user)
    {
        $idUser = (int) $user->getID();
        $idUserType = (int) $user->getRole();

        $sql = "SELECT
                    festi_section_actions.id_section,
                    festi_sections.ident,
                    festi_section_actions.plugin,
                    festi_section_actions.method,
                    festi_sections_user_types_permission.value as type_mask,
                    festi_sections_user_permission.value as user_mask
                FROM
                    festi_sections
                    LEFT JOIN festi_section_actions ON (
                      festi_sections.id = festi_section_actions.id_section
                    )
                    LEFT JOIN  
                        festi_sections_user_types_permission ON (
                            festi_sections.id = 
                                festi_sections_user_types_permission.id_section
                            AND 
                            festi_sections_user_types_permission.id_user_type = 
                                ".$this->quote($idUserType)."
                        )
                    LEFT JOIN  
                        festi_sections_user_permission ON (
                            festi_sections.id = 
                                festi_sections_user_permission.id_section
                            AND 
                            festi_sections_user_permission.id_user = 
                                ".$this->quote($idUser)."
                        )
                WHERE
                    festi_sections_user_types_permission.value IS NOT NULL OR 
                    festi_sections_user_permission.value IS NOT NULL";
                        
        return $this->getAll($sql);
    } // end getSectionsActionsByUser

    public function getAllSectionsActions()
    {
        $sql = "SELECT 
                  festi_section_actions.*,
                  festi_sections.ident,
                  festi_sections.mask as section_mask
                FROM 
                  festi_sections
                  LEFT JOIN festi_section_actions ON (
                    festi_sections.id = festi_section_actions.id_section
                  )";

        return $this->getAll($sql);
    } // end getAllSectionsActions
}
