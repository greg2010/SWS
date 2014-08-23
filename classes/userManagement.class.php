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
    
    protected $pilotInfo;
    protected $permissions;
    protected $APIUserManagement;

    public function __construct($id) {
        $this->id = $id;
        $this->db = db::getInstance();
        $this->permissions = new permissions($id);
        $this->APIUserManagement = new APIUserManagement($id);
        $this->pilotInfo = $this->getDbPilotInfo();
    }
    
    public function getUserInfo() {
        
    }
    
    private function getDbPilotInfo() {
        //Populates $dbPilotInfo
        try {
            $query = "SELECT * FROM `pilotInfo` WHERE `id` = '$this->id'";
            $result = $this->db->query($db);
            $dbPilotInfo = $this->db->fetchArray($result);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function getPilotInfo() {
        return $this->pilotInfo;
    }
    
    public function getApiKey($keyStatus) {
        try {
            $query = "SELECT `keyID`, `vCode`, `characterID` FROM `apiList` WHERE `id` = '$this->id' AND `keyStatus` = '$keyStatus'";
            $result = $this->db->query($db);
            $apiKey = $this->db->fetchRow($result);
            return $apiKey;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    protected function getAllowedList() {
        //Save to $this->allowedList
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