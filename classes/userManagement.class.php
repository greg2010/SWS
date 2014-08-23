<?php

interface IuserManagement {
    function getUserInfo();
    function getUserCorpName();
    function getUserAllianceName();
    function setNewPassword();
    function setUserInfo();
}
/**
 * Description of userManagement
 *
 * @author greg02010
 */
class userManagement implements IuserManagement {
    
    protected $id;
    protected $db;
    protected $permissions;
    protected $APIUserManagement;
    protected $pilotInfo;

    public function __construct($id) {
        $this->id = $id;
        $this->db = db::getInstance();
        $this->permissions = new permissions($id);
//        $this->APIUserManagement = new APIUserManagement($id);
        $this->getDbPilotInfo();
    }

    private function getDbPilotInfo() {
        //Populates $dbPilotInfo
        try {
            $query = "SELECT * FROM `pilotInfo` WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->pilotInfo = $this->db->fetchAssoc($result);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function getPilotInfo() {
        return $this->pilotInfo;
    }
    
    public function setPilotInfo($characterID, $corporationID, $allianceID) {
        $query = "UPDATE ";
    }

    public function getApiKey($keyStatus) {
        try {
            $query = "SELECT `keyID`, `vCode`, `characterID` FROM `apiList` WHERE `id` = '$this->id' AND `keyStatus` = '$keyStatus'";
            $result = $this->db->query($query);
            $apiKey = $this->db->fetchRow($result);
            return $apiKey;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function getAllowedListMask() {
        try {
            $characterID = $this->pilotInfo[characterID];
            $corporationID = $this->pilotInfo[corporationID];
            $allianceID = $this->pilotInfo[allianceID];
            $query = "SELECT `accessMask` FROM `allowedList`"
                    . " WHERE `characterID` = '$characterID' OR `corporationID` = '$corporationID' OR `allianceID` = '$allianceID'";
            $result = $this->db->query($query);
            $userMasks = $this->db->fetchRow($result);
            foreach ($userMasks as $userMask) {
                $accessMask = $accessMask | $userMask;
            }
            return $accessMask;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function getUserInfo() {
        
    }
    
    public function getUserCorpName() {
        
    }
    
    public function getUserAllianceName(){
        
    }
    
    public function setNewPassword(){
        
    }
    
    public function setUserInfo() {
        
    }
}