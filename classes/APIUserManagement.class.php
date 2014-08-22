<?php


/**
 * Description of APIUserManagement
 *
 * @author greg2010
 */


interface IAPIUserManagement {
    
    function getUserKeyMask();
    function changeUserApiKey($keyID, $vCode);
    
}

class APIUserManagement extends permissions implements IAPIUserManagement {
    
    protected $accessMask;
    protected $userInfo;
    protected $allowedList;
    protected $pilotInfo;
    
    private $dbPilotInfo;
    private $apiPilotInfo;
    
    public function __construct($id, $accessMask = NULL) {
        parent::__construct($id);
        if ($accessMask) {
        $this->accessMask = $accessMask;
        }
    }
    
    private function getPilotInfo() {
        //Populates $dbPilotInfo
    }
    
    private function getApiInfo () {
        //Populates $apiPrivateInfo
    }
    
    private function comparePilotInfo () {
        //Compare IDs, if not compare with AllowedList
    }
    
    protected function getAllowedList() {
        //Save to $this->allowedList
    }

    public function changeUserApiKey($keyID, $vCode) {
        
    }
    
    public function getUserKeyMask() {
        
    }
    
    private function verifyApiInfo() {
        
    }
}