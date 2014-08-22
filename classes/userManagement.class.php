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
    public function __construct($id) {
        parent::__construct($id);
    }
    
    
    
    
    
    
    
    public function getUserInfo() {
        
    }
    public function getUserCorpName() {
        
    }
    public function getUserAllianceName(){
        
    }
    public function setNewPassword(){
        
    }
}
