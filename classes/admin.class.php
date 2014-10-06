<?php

/*
/**
 * Description of admin
 *
 * @author greg2008200
 */
class admin {
    
    private $id;
    private $db;
    private $permissions;


    public function __construct($id) {
        $this->id = $id;
        $this->db = db::getInstance();
        $this->permissions = new permissions($this->id);
    }
    
    private function getAllAllowedList() {
        try {
            $query = "SELECT `characterID`, `corporationID`, `allianceID`, `accessMask` FROM `allowedMask`";
            return $this->db->fetchAssoc($this->db->query($query));
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
    }
    
    public function getAllowedList() {
        $fullAllowedList = $this->getAllAllowedList();
        try {
            for ($i = 0; $i<=count($fullAllowedList); $i++) {
                $query = "SELECT `characterName` FROM `apiPilotList` WHERE `characterID` = '{$fullAllowedList[$i][characterID]}' LIMIT 1";
                $result = $this->db->query($query);
                if ($this->db->countRows($result) < 1) {
                    //Fetch from API
                } else {
                    $characterName = $this->db->getMySQLResult($result);
                    $fullAllowedList[$i]['characterName'] = $characterName;
                }

                $query = "SELECT `name` FROM `corporationList` WHERE `id` = '{$fullAllowedList[$i][corporationID]}' LIMIT 1";
                $result = $this->db->query($query);
                if ($this->db->countRows($result) < 1) {
                    //Fetch from API
                } else {
                    $corporationName = $this->db->getMySQLResult($result);
                    $fullAllowedList[$i]['corporationName'] = $corporationName;
                }

                $query = "SELECT `name` FROM `allianceList` WHERE `id` = '{$fullAllowedList[$i][allianceID]}' LIMIT 1";
                $result = $this->db->query($query);
                if ($this->db->countRows($result) < 1) {
                    //Fetch from API
                } else {
                    $allianceName = $this->db->getMySQLResult($result);
                    $fullAllowedList[$i]['allianceName'] = $allianceName;
                }
                
                $perms = new permissions();
                $perms->setUserMask($fullAllowedList[$i][accessMask]);
                $fullAllowedList[$i]['permissions'] = $perms->getAllPermissions();
                unset($perms);
            }
            return $fullAllowedList;
        } catch (Exception $ex) {
            if ($ex->getCode() < $mysqlMax && $ex->getCode() > $mysqlMin) {
                throw new Exception("MySQL error: " . $ex->getMessage(), 30);
            } else {
                throw new Exception("Pheal error: " . $ex->getMessage(), 30);
            }
        }
    }
}
