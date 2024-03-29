<?php

interface IuserManagement {
    function getApiKey($keyStatus);
    function getAllowedListMask($maskOwner);
    function setNewPassword($password, $passwordRepeat);
}

class userManagement implements IuserManagement {
    
    
    protected $id;
    protected $db;
    protected $permissions;

    public function __construct($id = NULL) {
        $this->db = db::getInstance();
        if (!isset($id)) {
            $this->id = -1;
        } else {
            $this->id = $id;
            $this->permissions = new permissions($id);
        }
    }

    private function banQuery($id) {
        try {
            $query = "UPDATE `apiPilotList` SET `keyStatus` = '0' WHERE `id` = '$id'";
            $this->db->query($query);
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
        $permissions = new permissions($id);
        $permissions->unsetPermissions(array('webReg_Valid', 'TS_Valid', 'XMPP_Valid'));
    }

    public function tsReg() {
        
    }
    
    public function getApiKey($keyStatus) {
        $query = "SELECT `keyID`, `vCode`, `characterID` FROM `apiPilotList` WHERE `id` = '$this->id' AND `keyStatus` = '$keyStatus'";
        $result = $this->db->query($query);
        $apiKey = $this->db->fetchRow($result);
        return $apiKey;
    }
    
    public function getAllowedListMask($maskOwner) {
        $query = "SELECT `accessMask` FROM `allowedList` WHERE (`characterID` = '{$maskOwner[characterID]}' AND `corporationID` IS NULL AND `allianceID` IS NULL)"
         . " OR (`characterID` IS NULL AND `corporationID` = '{$maskOwner[corporationID]}' AND `allianceID` IS NULL)"
         . " OR (`characterID` IS NULL AND `corporationID` IS NULL AND `allianceID` = '{$maskOwner[allianceID]}')"
         . " OR (`characterID` = '{$maskOwner[characterID]}' AND `corporationID` = '{$maskOwner[corporationID]}' AND `allianceID` IS NULL)"
         . " OR (`characterID` = '{$maskOwner[characterID]}' AND `corporationID` IS NULL AND `allianceID` = '{$maskOwner[allianceID]}')"
         . " OR (`characterID` IS NULL AND `corporationID` = '{$maskOwner[corporationID]}' AND `allianceID` = '{$maskOwner[allianceID]}')"
         . " OR (`characterID` = '{$maskOwner[characterID]}' AND `corporationID` = '{$maskOwner[corporationID]}' AND `allianceID` IS NULL)"
         . " OR (`characterID` = '{$maskOwner[characterID]}' AND `corporationID` = '{$maskOwner[corporationID]}' AND `allianceID` = '{$maskOwner[allianceID]}')";
        $result = $this->db->query($query);
        $userMasks = $this->db->fetchRow($result);
        foreach ($userMasks as $userMask) {
            $accessMask = (count($userMasks) > 1) ? ($accessMask | $userMask[0]) : ($accessMask | $userMask);
        }
        if ($accessMask == '') {
            $accessMask = 0;
        }
        return $accessMask;
    }
    
    public function changeMainAPI($keyID, $vCode, $characterName) {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode. Can't change API.", 30);
        }
        if (!isset($_SESSION[regArray])) {
            throw new Exception('Please select character firstly!', 31);
        }
        if ($_SESSION[regArray][$characterName][valid] <> 1) {
            throw new Exception("Not valid character!", 20);
        }
        try {
            $query = "SELECT `banID`, `banMessage` FROM `users` WHERE `id` = $this->id";
            $result = $this->db->fetchAssoc($this->db->query($query));
            if ($result[banID] == -1) {
                throw new Exception("You're banned forever! Ban message: " . $result[banMessage], 11);
            }
            $this->db->changeMainAPI($this->id, $keyID, $vCode, $_SESSION[regArray][$characterName][characterID]);
            $_SESSION[userObject]->updateUserInfo();
            $pilotInfo = $_SESSION[userObject]->getApiPilotInfo();
            $newMask = $this->getAllowedListMask($pilotInfo[mainAPI]);
            $this->db->changeLogin($this->id, $characterName, $newMask);
            $this->db->query($query);
            $this->deleteFromTeamspeak();
        } catch (Exception $ex) {
            switch ($ex->getCode()) {
                case 22:
                    throw new Exception($ex->getMessage(), 22);
                default:
                    throw new Exception($ex->getMessage(), 30);
            }
        }
    }
    
    public function addSecAPI($keyID, $vCode, $characterName) {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode. Can't add API.", 30);
        }
        if (!isset($_SESSION[regArray])) {
            throw new Exception('Please select character firstly!', 31);
        }
        if ($_SESSION[regArray][$characterName][valid] <> 1) {
            throw new Exception("Not valid character!", 20);
        }
        try {
            $this->db->addSecAPI($this->id, $keyID, $vCode, $_SESSION[regArray][$characterName][characterID]);
            $_SESSION[userObject]->updateUserInfo();
        } catch (Exception $ex) {
            switch ($ex->getCode()) {
                case 22:
                    throw new Exception($ex->getMessage(), 22);
                default:
                    throw new Exception($ex->getMessage(), 30);
            }
        }
    }
    
    public function deleteSecAPI($characterID) {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode. Can't delete API.", 30);
        }
        try {
        $this->db->deleteAPI($this->id, $characterID);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage(), 30);
        }
    }
    
    public function ban($id = null, $message = null) {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode. Can't ban user.", 30);
        }
        if ($id) {
            if (!$this->permissions->hasPermission("webReg_AdminPanel")) {
                throw new Exception("Can't ban. Not enough permissions.", 12);
            }
            $this->banQuery($id);
            try {
                $query = "UPDATE `users` SET `banID` = '-1', `banMessage` = '$message' WHERE `id` = '$id'";
                $this->db->query($query);
            } catch (Exception $ex) {
                throw new Exception("MySQL error: " . $ex->getMessage(), 30);
            }
            $this->deleteFromTeamspeak($id);
        } else {
            $this->banQuery($this->id);
            $this->deleteFromTeamspeak();
        }
    }
    
    public function setNewEmail($email) {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode. Can't change email.", 30);
        }
        $verify = new registerNewUser();
        $verify->testEmail($email);
        
        try {
            $query = "SELECT `email` FROM `users` WHERE `id` = '$this->id'";
            $oldEmail = $this->db->getMySQLResult($this->db->query($query));
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
        if ($email == $oldEmail) {
            throw new Exception("Email hasn't changed. Skipping...", -1);
        }
        try {
            $this->db->updateEmail($this->id, $email);
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
    }

    public function setNewPassword($password, $passwordRepeat){
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode.", 30);
        }
        if ($passwordRepeat <> $password) {
            throw new Exception("Passwords don't match!", 11);
        }
        $verify = new registerNewUser();
        $verify->testPassword($password);
        try {
            $query = "SELECT `salt` FROM `users` WHERE `id` = '$this->id'";
            $salt = $this->db->getMySQLResult($this->db->query($query));
            $passwordHash = hash(config::password_hash_type, $password . $salt);
            $query = "SELECT `passwordHash` FROM `users` WHERE `id` = '$this->id'";
            $oldPasswordHash = $this->db->getMySQLResult($this->db->query($query));
            if ($oldPasswordHash == $passwordHash) {
                throw new Exception("New password is the same as old password!", 11);
            }
            $query = "UPDATE `users` SET `passwordHash` = '$passwordHash' WHERE `id` = '$this->id'";
            $this->db->query($query);
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
    }

    public function registerInTeamspeak($UID = NULL) {
        $ts3_debug = config::ts3_debug;
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode.", 30);
        }
        $ts3 = new ts3();
        
        
        if ($ts3_debug==1){
	file_put_contents ("debug.txt", "Before IF Unique ID: $UID \n", FILE_APPEND);
	}
        
        
        if (!$UID) {
            $UID = $ts3->getUid($ts3->nickname($this->id));
            
        if ($ts3_debug==1){
	file_put_contents ("debug.txt", "IN IF Unique ID: $UID \n", FILE_APPEND);
	}
        
            
            
        }
        $this->db->registerInTeamspeak($this->id, $UID);
        $ts3->validate($this->id);
    }
    
    public function deleteFromTeamspeak($id = NULL) {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode.", 30);
        }
        if (!$id) {
            $id = $this->id;
        }
        $ts3 = new ts3();
        $ts3->deleteTsUser($id);
        $this->db->deleteFromTeamspeak($id);
    }

    public function getCharacterName($id){
        $query = "SELECT `characterName` FROM `apiPilotList` WHERE `characterID` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    public function getCharacterID($name){
        $query = "SELECT `characterID` FROM `apiPilotList` WHERE `characterName` = '$name'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }
}