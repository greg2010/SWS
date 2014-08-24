<?php

interface Ivalidation {
    function verifyApiInfo();

    
}

class validation implements Ivalidation {

    protected $accessMask;
    protected $allowedList;
    protected $id;
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
        $this->permissions = new permissions($id);
        $this->userManagement = new userManagement($id);
        $this->apiUserManagement = new APIUserManagement($id);
        $this->dbPilotInfo = $this->userManagement->getPilotInfo();
        $this->apiPilotInfo = $this->apiUserManagement->getPilotInfo();
    }

    private function comparePilotInfo(){
    	if($this->apiPilotInfo[characterID] == $this->dbPilotInfo[characterID] && $this->apiPilotInfo[corporationID] == $this->dbPilotInfo[corporationID] && $this->apiPilotInfo[allianceID] == $this->dbPilotInfo[allianceID]){
    		return false;
    	}
    	else{
    		try {
            	$query = "UPDATE `pilotInfo` SET `characterID` = '{$this->apiPilotInfo[characterID]}', `characterName` = '{$this->apiPilotInfo[characterName]}', `corporationID` = '{$this->apiPilotInfo[corporationID]}',
            	 `corporationName` = '{$this->apiPilotInfo[corporationName]}', `allianceID` = '{$this->apiPilotInfo[allianceID]}', `allianceName` = '{$this->apiPilotInfo[allianceName]}' WHERE `id` = '$this->id'";
            	$this->db->query($query);
            	return true;
        	} catch (Exception $ex) {
            	return $ex->getMessage();
        	}
    	}
    }

    public function verifyApiInfo(){
        if($this->comparePilotInfo()){
        	$cMask = $this->userManagement->getAllowedListMask();
        	if($cMask != $this->accessMask){
        		try {
            		$query = "UPDATE `users` SET `accessMask` = '$cMask' WHERE `id` = '$this->id'";
            		$this->db->query($query);
        		} catch (Exception $ex) {
            		return $ex->getMessage();
        		}
        	}
        }
    }

}

?>
