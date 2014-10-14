<?php

class admin {
    
    private $id;
    private $db;
    //private $permissions;
    private $apiUserManagement;


    public function __construct($id) {
        $this->id = $id;
        $this->db = db::getInstance();
        //$this->permissions = new permissions($this->id);
        $this->apiUserManagement = new APIUserManagement();
    }

    public function getAllAlliAllowedList(){
        $query = "SELECT `id`, `allianceID`, `accessMask`, `comment` FROM `allowedList` WHERE `allianceID` IS NOT NULL AND (`corporationID` IS NULL AND `characterID` IS NULL)";
        $result = $this->db->query($query);
        $arr = ($this->db->hasRows($result)) ? (($this->db->countRows($result) == 1) ? array($this->db->fetchAssoc($result)) : $this->db->fetchAssoc($result)) : NULL;
        for($i=0; $i<count($arr); $i++){
            try{
                $name = $this->apiUserManagement->getAllianceName($arr[$i][allianceID]);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            $arr[$i][name] = $name;
            $perm = new permissions();
            $perm->setUserMask($arr[$i][accessMask]);
            $arr[$i][hasWebAccess] = $perm->hasPermission("webReg_Valid");
            $arr[$i][hasTSAccess] = $perm->hasPermission("TS_Valid");
            $arr[$i][hasXMPPAccess] = $perm->hasPermission("XMPP_Valid");
            unset($perm);
        }
        return $arr;
    }

    public function getAllCorpAllowedList(){
        $query = "SELECT `id`, `corporationID`, `allianceID`, `accessMask`, `comment` FROM `allowedList` WHERE `corporationID` IS NOT NULL AND `characterID` IS NULL";
        $result = $this->db->query($query);
        $arr = ($this->db->hasRows($result)) ? (($this->db->countRows($result) == 1) ? array($this->db->fetchAssoc($result)) : $this->db->fetchAssoc($result)) : NULL;
        for($i=0; $i<count($arr); $i++){
            try{
                $arr[$i][name] = $this->apiUserManagement->getCorporationName($arr[$i][corporationID]);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            $perm = new permissions();
            $perm->setUserMask($arr[$i][accessMask]);
            $arr[$i][hasWebAccess] = $perm->hasPermission("webReg_Valid");
            $arr[$i][hasTSAccess] = $perm->hasPermission("TS_Valid");
            $arr[$i][hasXMPPAccess] = $perm->hasPermission("XMPP_Valid");
            if($arr[$i][allianceID] != NULL){
                $query = "SELECT `accessMask` FROM `allowedList` WHERE `allianceID` = '{$arr[$i][allianceID]}' AND (`corporationID` IS NULL AND `characterID` IS NULL)";
                $result = $this->db->query($query);
                if($this->db->hasRows($result)){
                    $perm->setUserMask($this->db->getMysqlResult($result));
                    $arr[$i][alliance] = array(
                        "hasWebAccess" => $perm->hasPermission("webReg_Valid"),
                        "hasTSAccess" => $perm->hasPermission("TS_Valid"),
                        "hasXMPPAccess" => $perm->hasPermission("XMPP_Valid")
                    );
                }
            }
            unset($perm);
        }
        return $arr;
    }

    public function getAllCharAllowedList(){
        $query = "SELECT `id`, `characterID`, `corporationID`, `allianceID`, `accessMask`, `comment` FROM `allowedList` WHERE `characterID` <> 'NULL'";
        $result = $this->db->query($query);
        $arr = ($this->db->hasRows($result)) ? (($this->db->countRows($result) == 1) ? array($this->db->fetchAssoc($result)) : $this->db->fetchAssoc($result)) : NULL;
        for($i=0; $i<count($arr); $i++){ 
            try{
                $arr[$i][name] = $this->apiUserManagement->getCharacterName($arr[$i][characterID]);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            $perm = new permissions();
            $perm->setUserMask($arr[$i][accessMask]);
            $arr[$i][permissions] = $perm->getAllPermissions();
            if($arr[$i][allianceID] != NULL){
                $query = "SELECT `accessMask` FROM `allowedList` WHERE `allianceID` = '{$arr[$i][allianceID]}' AND (`corporationID` IS NULL AND `characterID` IS NULL)";
                $result = $this->db->query($query);
                if($this->db->hasRows($result)){
                    $perm->setUserMask($this->db->getMysqlResult($result));
                    $arr[$i][alliance] = array(
                        "hasWebAccess" => $perm->hasPermission("webReg_Valid"),
                        "hasTSAccess" => $perm->hasPermission("TS_Valid"),
                        "hasXMPPAccess" => $perm->hasPermission("XMPP_Valid")
                    );
                }
            }
            if($arr[$i][corporationID] != NULL){
                $query = "SELECT `accessMask` FROM `allowedList` WHERE `corporationID` = '{$arr[$i][corporationID]}' AND `characterID` IS NULL";
                $result = $this->db->query($query);
                if($this->db->hasRows($result)){
                    $perm->setUserMask($this->db->getMysqlResult($result));
                    $arr[$i][corporation] = array(
                        "hasWebAccess" => $perm->hasPermission("webReg_Valid"),
                        "hasTSAccess" => $perm->hasPermission("TS_Valid"),
                        "hasXMPPAccess" => $perm->hasPermission("XMPP_Valid")
                    );
                }
            }
            unset($perm);
        }
        return $arr;
    }

    public function addAlliToAllowedList($name, $rawmask, $comment=""){
        $perm = new permissions();
        $mask = $perm->convertPermissions($rawmask);
        try{
            $id = $this->apiUserManagement->getAllianceID($name);
        } catch(\Pheal\Exceptions\PhealException $ex){
            throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
        }
        if($id < 1) throw new Exception("Incorrect alliance name ", -501);
        $query = "SELECT `allianceID` FROM `allowedList` WHERE `allianceID` = '$id'";
        if($this->db->hasRows($this->db->query($query))) throw new Exception("Alliance already in allowed list ", -501);
        $query = "INSERT INTO `allowedList` SET `allianceID` = '$id', `accessMask` = '$mask', `comment` = '$comment'";
        $result = $this->db->query($query);
    }

    public function addCorpToAllowedList($name, $rawmask, $comment="", $alliname = NULL){
        $perm = new permissions();
        $mask = $perm->convertPermissions($rawmask);
        try{
            $id = $this->apiUserManagement->getCorporationID($name);
        } catch(\Pheal\Exceptions\PhealException $ex){
            throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
        }
        if($id < 1) throw new Exception("Incorrect corporation name ", -502);
        $query = "SELECT `corporationID` FROM `allowedList` WHERE `corporationID` = '$id'";
        if($this->db->hasRows($this->db->query($query))) throw new Exception("Corporation already in allowed list ", -501);
        if($alliname){
            try{
                $alliid = $this->apiUserManagement->getAllianceID($alliname);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            if($alliid < 1) throw new Exception("Incorrect alliance name ", -501);
        }
        if($alliid > 0) $supquery = ", `allianceID` = '$alliid'";
        $query = "INSERT INTO `allowedList` SET `corporationID` = '$id', `accessMask` = '$mask', `comment` = '$comment'" . $supquery;
        $result = $this->db->query($query);
    }

    public function addCharToAllowedList($name, $rawmask, $comment="", $corpname = NULL, $alliname = NULL){
        $perm = new permissions();
        $mask = $perm->convertPermissions($rawmask);
        $alliid = NULL;
        $corpid = NULL;
        $supquery = "";
        try{
            $id = $this->apiUserManagement->getCharacterID($name);
        } catch(\Pheal\Exceptions\PhealException $ex){
            throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
        }
        if($id < 1) throw new Exception("Incorrect character name ", -503);
        $query = "SELECT `characterID` FROM `allowedList` WHERE `characterID` = '$id'";
        if($this->db->hasRows($this->db->query($query))) throw new Exception("Character already in allowed list ", -501);
        if($corpname){
            try{
                $corpid = $this->apiUserManagement->getCorporationID($corpname);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            if($corpid < 1) throw new Exception("Incorrect corporation name ", -502);
        }
        if($alliname){
            try{
                $alliid = $this->apiUserManagement->getAllianceID($alliname);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            if($alliid < 1) throw new Exception("Incorrect alliance name ", -501);
        }
        if($alliid > 0) $supquery .= ", `allianceID` = '$alliid'";
        if($corpid > 0) $supquery .= ", `corporationID` = '$corpid'";
        $query = "INSERT INTO `allowedList` SET `characterID` = '$id', `accessMask` = '$mask', `comment` = '$comment'" . $supquery;
        $result = $this->db->query($query);
    }

    public function deleteFromAllowedList($id){
        $query = "DELETE FROM `allowedList` WHERE `id` = '$id'";
        $result = $this->db->query($query);
    }

    public function modifyAllowedList($id, $rawmask, $charname=NULL, $corpname=NULL, $alliname=NULL, $comment=""){
        $perm = new permissions();
        $mask = $perm->convertPermissions($rawmask);
        $query = "UPDATE `allowedList` SET `accessMask` = '$mask', `comment` = '$comment'";
        if($charname){
            try{
                $charid = $this->apiUserManagement->getCharacterID($charname);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            if($charid < 1) throw new Exception("Incorrect character name ", -503);
            $query .= ", `characterID` = '$charid'";
        }
        if($corpname){
            try{
                $corpid = $this->apiUserManagement->getCorporationID($corpname);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            if($corpid < 1) throw new Exception("Incorrect corporation name ", -502);
            $query .= ", `corporationID` = '$corpid'";
        }
        if($alliname){
            try{
                $alliid = $this->apiUserManagement->getAllianceID($alliname);
            } catch(\Pheal\Exceptions\PhealException $ex){
                throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
            }
            if($alliid < 1) throw new Exception("Incorrect alliance name ", -501);
            $query .= ", `allianceID` = '$alliid'";
        }
        $query .= " WHERE `id` = '$id'";
        $result = $this->db->query($query);
    }


    
    /*private function getAllAllowedList() {
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
    }*/
}

?>
