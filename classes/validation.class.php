<?php

class validation {

    private $log;
    private $db;
    private $apiPilotInfo;

	public function __construct(){
        $this->db = db::getInstance();
        $this->log = new logging();
    }

    private function comparePilotInfo($dbPilotInfo =array()){
    	if($this->apiPilotInfo[characterID] == NULL){
            return false;
        } elseif($this->apiPilotInfo[characterID] == $dbPilotInfo[characterID] && $this->apiPilotInfo[corporationID] == $dbPilotInfo[corporationID]
         && $this->apiPilotInfo[allianceID] == $dbPilotInfo[allianceID] && $this->apiPilotInfo[accessMask] == $dbPilotInfo[accessMask]){
            //$this->log->put("comparePilotInfo", "ok: IDs match (char: " . $this->apiPilotInfo[characterID] . ", corp: " . $this->apiPilotInfo[corporationID] . ", alli: " . $this->apiPilotInfo[allianceID] . ")");
    		return true;
    	} else{
    		try {
                $this->updatePilotInfo();
                $this->log->put("comparePilotInfo", "ok update");
            	return true;
        	} catch (Exception $ex) {
                $this->log->put("comparePilotInfo", "err " . $ex->getMessage());
                return false;
        	}
    	}
    }

    private function getUserAccessMask($id){
        try {
            $query = "SELECT `accessMask` FROM `users` WHERE `id` = '$id'";
            $result = $this->db->query($query);
            return $this->db->getMysqlResult($result);
        } catch (Exception $ex) {
            $this->log->put("getUserAccessMask", "err " . $ex->getMessage());
        }
    }

    private function checkManuallyBanned($id){
        try {
            $query = "SELECT `banID` FROM `users` WHERE `id` = '$id'";
            $result = $this->db->query($query);
            return ($this->db->getMysqlResult($result) == "-1") ? true : false;
        } catch (Exception $ex) {
            $this->log->put("checkManuallyBanned", "err " . $ex->getMessage());
        }
    }

    private function setBanMessage($id, $code, $text){
        $query = "UPDATE `users` SET `banID` = '$code', `banMessage` = '$text' WHERE `id` = '$id'";
        $result = $this->db->query($query);
    }

    private function ban($id){
        try {
            $permissions = new permissions($id);
            $permissions->unsetPermissions(array('webReg_Valid', 'TS_Valid', 'XMPP_Valid'));
            $this->ts3Ban($id);
            $this->xmppBan($id);
        } catch (Exception $ex) {
            $this->log->put("ban", "err " . $ex->getMessage());
        }
    }
    
    private function ts3Ban($id){
        try {
            $ts3 = new ts3;
            $wow = $ts3->validate($id);
            if(!$wow) throw new Exception($wow);
        } catch (Exception $ex) {
            $this->log->put("ts3", "err " . $ex->getMessage());
        }
    }
    
    private function xmppBan($id){
        try {
            $query = "SELECT `login` FROM `users` WHERE `id` = '$id'";
            $result = $this->db->query($query);
            $xmpp_result = json_decode(file_get_contents("http://". config::xmpp_address . "/delete/" . rawurlencode($this->db->getMysqlResult($result))), true);
            if(!$xmpp_result[removed]) throw new Exception("Deleting user failed");
        } catch (Exception $ex) {
            $this->log->put("xmpp", "err " . $ex->getMessage());
        }
    }

    private function showBans($id){
        try {
            $permissions = new permissions($id);
            $ban_list = "";
            if($permissions->hasPermission("webReg_Valid") == false){
                $ban_list .= "web ";
            }
            if($permissions->hasPermission("TS_Valid") == false){
                $ban_list .= "TS3 ";
                $this->ts3Ban($id);
            }
            if($permissions->hasPermission("XMPP_Valid") == false){
                $ban_list .= "Jabber ";
                $this->xmppBan($id);
            }
            if($ban_list != "") return "ok update ban in " . $ban_list;
        } catch (Exception $ex) {
            $this->log->put("showBans", "err " . $ex->getMessage());
        }
    }

    public function updatePilotInfo($characterID = NULL, $keyID = NULL, $vCode = NULL){
        if($characterID != NULL){
            $apiUserManagement = new APIUserManagement();
            $this->apiPilotInfo = $apiUserManagement->getPilotInfo($characterID, $keyID, $vCode);
        }
        $query = "UPDATE `apiPilotList` SET `characterName` = '{$this->apiPilotInfo[characterName]}', `corporationID` = '{$this->apiPilotInfo[corporationID]}',
         `allianceID` = '{$this->apiPilotInfo[allianceID]}', `accessMask` = '{$this->apiPilotInfo[accessMask]}' WHERE `characterID` = '{$this->apiPilotInfo[characterID]}'";
        $result = $this->db->query($query);   
    }

    public function verifyPilotApiInfo($dbPilot = array()){
        if($this->checkManuallyBanned($dbPilot[id])) return $this->log->get();
        try {
            $userManagement = new userManagement();
            $cMask = $userManagement->getAllowedListMask($dbPilot);
        } catch (Exception $ex) {
            $this->log->put("getAllowedListMask", "err " . $ex->getMessage(), "userManagement");
            return $this->log->get();
        }
        try {
            $apiUserManagement = new APIUserManagement();
            $this->apiPilotInfo = $apiUserManagement->getPilotInfo($dbPilot[characterID], $dbPilot[keyID], $dbPilot[vCode]);
        } catch (Exception $ex) {
            $this->log->put("getPilotInfo", "err " . $ex->getMessage(), "apiUserManagement");
            $c = $ex->getCode();
            if($c == 105 || $c == 106 || $c == 108 || $c == 112 || $c == 201 || $c == 202 || $c == 203 || $c == 204 || $c == 205 || $c == 210 || $c == 211 || $c == 212 ||
             $c == 221 || $c == 222 || $c == 223 || $c == 516 || $c == 522 || $c == -201 || $c == -202 || $c == -203 || $c == -204 || $c == -205){
                if($dbPilot[keyStatus] == 1){
                    $this->ban($dbPilot[id]);
                    $this->setBanMessage($dbPilot[id], $c, $ex->getMessage());
                }
                // if not 1 ?
            }
            return $this->log->get();
        }
        if($this->comparePilotInfo($dbPilot)){
            if($dbPilot[keyStatus] == 1){
                $UserAccessMask = $this->getUserAccessMask($dbPilot[id]);
                if($cMask != $UserAccessMask){
                    try {
                        $query = "UPDATE `users` SET `accessMask` = '$cMask' WHERE `id` = '{$dbPilot[id]}'";
                        $result = $this->db->query($query);
                        $this->log->put("verifyPilotApiInfo", "ok update correct mask " . $cMask);
                    } catch (Exception $ex) {
                        $this->log->put("verifyPilotApiInfo", "err " . $ex->getMessage());
                    }
                }
                $ban_list = $this->showBans($dbPilot[id]);
                if($ban_list != NULL) $this->log->put("verifyPilotApiInfo", $ban_list);
            }
        }
        return $this->log->get();
    }

    public function verifyCorpApiInfo($dbCorp = array()){
        try {
            $apiUserManagement = new APIUserManagement();
            $apiCorp = $apiUserManagement->getCorpInfo($dbCorp[keyID], $dbCorp[vCode]);
        } catch (Exception $ex) {
            $this->log->put("getCorpInfo", "err " . $ex->getMessage(), "apiUserManagement");
            return $this->log->get();
        }
        if($dbCorp[accessMask] != $apiCorp[accessMask] || $dbCorp[corporationID] != $apiCorp[corporationID] || $dbCorp[allianceID] != $apiCorp[allianceID]){
            try {
                $this->ts3Ban($dbCorp[keyID]);
                $query = "UPDATE `apiCorpList` SET `accessMask` = '{$apiCorp[accessMask]}', `corporationID` = '{$apiCorp[corporationID]}', `allianceID` = '{$apiCorp[allianceID]}' WHERE `keyID` = '{$dbCorp[keyID]}'";
                $result = $this->db->query($query);
                $this->log->put("verifyCorpApiInfo", "ok update");
            } catch (Exception $ex) {
                $this->log->put("verifyCorpApiInfo", "err " . $ex->getMessage());
            }
        }// else $this->log->put("verifyCorpApiInfo", "ok: match");
        return $this->log->get();
    }
}

?>
