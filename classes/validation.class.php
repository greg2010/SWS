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
    //private $permissions;
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
        //$this->permissions = new permissions($id);
        $this->userManagement = new userManagement($id);
        $this->apiUserManagement = new APIUserManagement($id);
        $this->dbPilotInfo = $this->userManagement->getPilotInfo();
        $this->apiPilotInfo = $this->apiUserManagement->getPilotInfo();
    }

    private function comparePilotInfo(){
    	if($this->apiPilotInfo[characterID] == NULL){
            return false;
        } elseif($this->apiPilotInfo[characterID] == $this->dbPilotInfo[characterID] && $this->apiPilotInfo[corporationID] == $this->dbPilotInfo[corporationID] && $this->apiPilotInfo[allianceID] == $this->dbPilotInfo[allianceID]){
            $this->log->put("All IDs match (char: " . $this->apiPilotInfo[characterID] . ", corp: " . $this->apiPilotInfo[corporationID] . ", alli: " . $this->apiPilotInfo[allianceID] . ")");
    		return true;
    	} else{
    		try {
            	$query = "UPDATE `pilotInfo` SET `characterID` = '{$this->apiPilotInfo[characterID]}', `characterName` = '{$this->apiPilotInfo[characterName]}', `corporationID` = '{$this->apiPilotInfo[corporationID]}',
            	 `corporationName` = '{$this->apiPilotInfo[corporationName]}', `allianceID` = '{$this->apiPilotInfo[allianceID]}', `allianceName` = '{$this->apiPilotInfo[allianceName]}' WHERE `id` = '$this->id'";
            	$this->db->query($query);
                $this->log->put("IDs don't match, db table updated");
            	return true;
        	} catch (Exception $ex) {
                $this->log->put("IDs don't match, db table update fail: " . $ex->getMessage());
                return false;
        	}
    	}
    }

    /*private function RemoveTSRoles(){
        $ts3 = new ts3;
        $sgid=array(44, 45, 46, 47, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60);
        if($ts3->delGruser($sgid,$this->id)){
            $this->log->put("TS3 roles removed");
        } else{
            $this->log->put("TS3 roles removing faild");
        }
    }*/

    public function verifyApiInfo(){
        $this->log->put($this->apiUserManagement->log->get(), false);
        $this->log->put($this->userManagement->log->get(), false);
        $this->userManagement->log->rm();
        if($this->comparePilotInfo()){
        	$cMask = $this->userManagement->getAllowedListMask();
            $this->log->put($this->userManagement->log->get(), false);
        	if($cMask != $this->accessMask){
        		try {
            		$query = "UPDATE `users` SET `accessMask` = '$cMask' WHERE `id` = '$this->id'";
            		$this->db->query($query);
                    $this->log->put("Access mask " . $cMask . " updated");
        		} catch (Exception $ex) {
            		$this->log->put("Access mask don't match, db table update fail: " . $ex->getMessage());
        		}
                //RemoveTSRoles();
        	}
            else{
                $this->log->put("Access mask " . $cMask . " match");
            }
        }
        return $this->log->get();
    }

}

?>
