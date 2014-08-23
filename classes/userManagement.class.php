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
class userManagement extends APIUserManagement implements IuserManagement {
    
    protected $pilotInfo;
    protected $permissions;
    protected $APIUserManagement;

    public function __construct($id) {
        $this->id = $id;
        $this->db = db::getInstance();
        $this->permissions = new permissions($id);
        $this->APIUserManagement = new APIUserManagement($id);
        $this->pilotInfo = $this->getPilotInfo();
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