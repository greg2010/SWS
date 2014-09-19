<?php


use Pheal\Pheal;

class registerNewUser {
    
    private $apiPilotInfo = array();
    
    private $error;
    private $errorType;
    
    protected $id;
    protected $db;
    protected $permissions;
    protected $APIUserManagement;
    protected $userManagement;

    public function __construct() {
        $this->db = db::getInstance();
        $this->permissions = new permissions();
        $this->APIUserManagement = new APIUserManagement();
        $this->userManagement = new userManagement;
    }
    
    private function getInfoFromKey() {
        $this->apiPilotInfo = $this->APIUserManagement->getCharsInfo();
        if ($this->apiPilotInfo === NULL) {
            $errorArray = $this->APIUserManagement->log->get();
            $this->error = $errorArray[getApiPilotInfo];
            throw new Exception($this->error);
        }
    }
    
    private function makeRegisterArray() {
        $i = 0;
        for ($i = 0; $i < count($this->apiPilotInfo); $i++) {
            $mask = $this->userManagement->getAllowedListMask($this->apiPilotInfo[$i]);
            $this->permissions->setCustomMask($mask);
            $this->apiPilotInfo[$i]['canRegister'] = $this->permissions->hasPermission('webReg_Valid');
            $this->apiPilotInfo[$i]['permissions'] = $this->permissions->getAllPermissions();
        }
        print_r($this->apiPilotInfo);
    }
    
    public function setUserData($login, $password, $apiKey, $vCode) {
        try {
            $this->login = $login;
            $this->passwordHash = hash(sha512, $password);
            $this->apiKey = $apiKey;
            $this->vCode = $vCode;
            $this->getInfoFromKey();
            $apiError = $this->error;
            if ($apiError) {
                throw new Exception('Here is problem with your api: ' . $apiError);
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function registerNewUser () {
        $regCheck = $this->makeRegisterArray();
    }
    
    public function getError() {
        return $this->error;
    }
    
    public function getErrorType() {
        return $this->errorType;
    }
}