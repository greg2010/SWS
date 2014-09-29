<?php

interface Ivalidation {
    function verifyApiInfo();
}

class validation implements Ivalidation {

    protected $accessMask;
    protected $allowedList;
    protected $id;
    private $log;
    private $db;    
    private $permissions;
    private $userManagement;
    private $apiUserManagement;
    private $dbPilotInfo;
    private $apiPilotInfo;
    private $apiKey;

	public function __construct($id = NULL, $accessMask = NULL){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->apiUserManagement = new APIUserManagement($id);
        if(isset($accessMask)) $this->accessMask = $accessMask;
        if(isset($id)){
            $this->id = $id;
            $this->permissions = new permissions($id);
            $this->userManagement = new userManagement($id);
            $this->dbPilotInfo = $this->userManagement->getPilotInfo();
            $this->apiPilotInfo = $this->apiUserManagement->getPilotInfo();
        }
    }

    private function comparePilotInfo(){
    	if($this->apiPilotInfo[characterID] == NULL){
            return false;
        } elseif($this->apiPilotInfo[characterID] == $this->dbPilotInfo[characterID] && $this->apiPilotInfo[corporationID] == $this->dbPilotInfo[corporationID]
         && $this->apiPilotInfo[allianceID] == $this->dbPilotInfo[allianceID] && $this->apiPilotInfo[accessMask] == $this->dbPilotInfo[accessMask]){
            //$this->log->put("comparePilotInfo", "ok: IDs match (char: " . $this->apiPilotInfo[characterID] . ", corp: " . $this->apiPilotInfo[corporationID] . ", alli: " . $this->apiPilotInfo[allianceID] . ")");
    		return true;
    	} else{
    		try {
            	$query = "UPDATE `apiPilotList` SET `characterID` = '{$this->apiPilotInfo[characterID]}', `characterName` = '{$this->apiPilotInfo[characterName]}', `corporationID` = '{$this->apiPilotInfo[corporationID]}',
            	 `allianceID` = '{$this->apiPilotInfo[allianceID]}' WHERE `id` = '$this->id'";
            	$result = $this->db->query($query);
                if($this->apiPilotInfo[accessMask] != $this->dbPilotInfo[accessMask]){
                    $query = "UPDATE `apiPilotList` SET `accessMask` = '{$this->apiPilotInfo[accessMask]}' WHERE `id` = '$this->id'";
                    $result = $this->db->query($query);
                }
                $this->log->put("comparePilotInfo", "ok update");
            	return true;
        	} catch (Exception $ex) {
                $this->log->put("comparePilotInfo", "err " . $ex->getMessage());
                return false;
        	}
    	}
    }

    public function verifyApiInfo(){
        if($this->apiUserManagement->log->get() != NULL){
            $this->apiUserManagement->log->rm("getApiPilotInfo_code");
            $this->log->merge($this->apiUserManagement->log->get(true), "APIUserManagement");
        }
        $cMask = $this->userManagement->getAllowedListMask();
        $this->log->merge($this->userManagement->log->get(true), "userManagement");
        if($this->comparePilotInfo()){
        	if($cMask != $this->accessMask){
        		try {
            		$query = "UPDATE `users` SET `accessMask` = '$cMask' WHERE `id` = '$this->id'";
            		$result = $this->db->query($query);
                    $this->log->put("verifyApiInfo", "ok update correct mask " . $cMask);
        		} catch (Exception $ex) {
            		$this->log->put("verifyApiInfo", "err " . $ex->getMessage());
        		}
                // TS ban method
        	}// else $this->log->put("verifyApiInfo", "ok: mask " . $cMask . " match");
        } else{
            $ban_list = "";
            if($this->permissions->hasPermission("webReg_Valid") == false){
                $ban_list .= "web ";
            }
            if($this->permissions->hasPermission("TS_Valid") == false){
                $ban_list .= "TS3 ";
                // TS ban method
            }
            if($this->permissions->hasPermission("XMPP_Valid") == false){
                $ban_list .= "Jabber ";
                // XMPP ban method
            }
            if($ban_list != "") $this->log->put("verifyApiInfo", "ok update ban in " . $ban_list);
            $this->log->merge($this->permissions->log->get(true), "permissions");
        }
        return $this->log->get();
    }

    public function verifyCorpApiInfo($dbCorp = array()){
        $apiCorp = $this->apiUserManagement->getCorpInfo($dbCorp[keyID], $dbCorp[vCode]);
        if($this->apiUserManagement->log->get() != NULL){
            $this->apiUserManagement->log->rm("getApiPilotInfo_code");
            $this->log->merge($this->apiUserManagement->log->get(true), "APIUserManagement");
        }
        if($dbCorp[accessMask] != $apiCorp[accessMask] || $dbCorp[corporationID] != $apiCorp[corporationID] || $dbCorp[allianceID] != $apiCorp[allianceID]){
            try {
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
