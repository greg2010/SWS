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

	public function __construct($id, $accessMask = NULL){
        if ($accessMask){
        $this->accessMask = $accessMask;
        }
        $this->id = $id;
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->permissions = new permissions($id);
        $this->userManagement = new userManagement($id);
        $this->apiUserManagement = new APIUserManagement($id);
        $this->dbPilotInfo = $this->userManagement->getPilotInfo();
        $this->apiPilotInfo = $this->apiUserManagement->getPilotInfo();
    }

    private function comparePilotInfo(){
    	if($this->apiPilotInfo[characterID] == NULL){
            return false;
        } elseif($this->apiPilotInfo[characterID] == $this->dbPilotInfo[characterID] && $this->apiPilotInfo[corporationID] == $this->dbPilotInfo[corporationID] && $this->apiPilotInfo[allianceID] == $this->dbPilotInfo[allianceID]){
            $this->log->put("comparePilotInfo", "ok: IDs match (char: " . $this->apiPilotInfo[characterID] . ", corp: " . $this->apiPilotInfo[corporationID] . ", alli: " . $this->apiPilotInfo[allianceID] . ")");
    		return true;
    	} else{
    		try {
            	$query = "UPDATE `pilotInfo` SET `characterID` = '{$this->apiPilotInfo[characterID]}', `characterName` = '{$this->apiPilotInfo[characterName]}', `corporationID` = '{$this->apiPilotInfo[corporationID]}',
            	 `corporationName` = '{$this->apiPilotInfo[corporationName]}', `allianceID` = '{$this->apiPilotInfo[allianceID]}', `allianceName` = '{$this->apiPilotInfo[allianceName]}' WHERE `id` = '$this->id'";
            	$this->db->query($query);
                $this->log->put("comparePilotInfo", "ok: don't match, db table updated");
            	return true;
        	} catch (Exception $ex) {
                $this->log->put("comparePilotInfo", "err: " . $ex->getMessage());
                return false;
        	}
    	}
    }

    public function verifyApiInfo(){
        if($this->apiUserManagement->log->get() != NULL){
            $this->log->initSub("APIUserManagement");
            $this->log->merge($this->apiUserManagement->log->get(), "APIUserManagement");
        }
        $cMask = $this->userManagement->getAllowedListMask();
        if($this->userManagement->log->get() != NULL){
            $this->log->initSub("userManagement");
            $this->log->merge($this->userManagement->log->get(), "userManagement");
        }
        if($this->comparePilotInfo()){
        	if($cMask != $this->accessMask){
        		try {
            		$query = "UPDATE `users` SET `accessMask` = '$cMask' WHERE `id` = '$this->id'";
            		$this->db->query($query);
                    $this->log->put("verifyApiInfo", "ok: don't match, db table updated, correct mask: " . $cMask);
        		} catch (Exception $ex) {
            		$this->log->put("verifyApiInfo", "err: " . $ex->getMessage());
        		}
                // TS ban method
        	}
            else{
                $this->log->put("verifyApiInfo", "ok: mask " . $cMask . " match");
            }
        } else{
            $ban_list = "";
            if($this->permissions->hasPermission("webReg_Valid") == false){
                $ban_list .= "web, ";
            }
            if($this->permissions->hasPermission("TS_Valid") == false){
                $ban_list .= "TS3, ";
                // TS ban method
            }
            if($this->permissions->hasPermission("XMPP_Valid") == false){
                $ban_list .= "Jabber, ";
                // XMPP ban method
            }
            if($ban_list != "") $this->log->put("verifyApiInfo", "ok: user banned in " . $ban_list . "db table updated");
            if($this->apiUserManagement->log->get() != NULL){
                $this->log->initSub("permissions");
                $this->log->merge($this->apiUserManagement->log->get(), "permissions");
            }
        }
        return $this->log->get();
    }
}

?>
