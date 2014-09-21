<?php

class registerNewUser {
    
    private $apiPilotInfo = array();
    private $apiKey;
    private $error;
    
    private $login;
    private $email;
    private $passwordHash;
    
    private $guiArray = array();
    private $registerArray = array();
    
    protected $id;
    protected $db;
    protected $permissions;
    protected $APIUserManagement;
    protected $userManagement;

    public function __construct() {
        $this->db = db::getInstance();
        $this->permissions = new permissions();
        $this->APIUserManagement = new APIUserManagement();
        $this->userManagement = new userManagement();
    }
    
    private function getInfoFromKey() {
        $this->apiPilotInfo = $this->APIUserManagement->getCharsInfo($this->apiKey[0], $this->apiKey[1]);
        if (!$this->apiPilotInfo) {
            $error = $this->APIUserManagement->log->get();
            $this->error = array (
                "status" => $error[getApiPilotInfo_code],
                "message" => ltrim($error[getApiPilotInfo], "err ")
            );
            return FALSE;
        } else {
            $this->error = array (
                "status" => 0,
                "message" => ""
                );
            return TRUE;
        }
    }
    
    private function makeRegisterArray() {
        if ($this->error[status] <> 0) {
            return FALSE;
        }
        foreach ($this->apiPilotInfo as $char) {
            $requestArray = array (
                "characterID" => $char[characterID],
                "corporationID" => $char[corporationID],
                "allianceID" => $char[allianceID]
            );

            $keyPermissions = $this->userManagement->getAllowedListMask($requestArray);
            if (!$keyPermissions) {
                $keyPermissions = 0;
            }
            $this->permissions->setUserMask($keyPermissions);
            if ($this->permissions->hasPermission("webReg_Valid") == FALSE) {
                $canRegister = 0;
            } else {
                $canRegister = 1;
            }
            $this->guiArray[] = array(
                "characterName" => $char[characterName],
                "valid" => $canRegister
            );
            $this->registerArray[$char[characterName]] = array (
                "characterID" => $char[characterID],
                "corporationID" => $char[corporationID],
                "corporationName" => $char[corporationName],
                "allianceID" => $char[allianceID],
                "allianceName" => $char[allianceName],
                "permissions" => $keyPermissions,
                "valid" => $canRegister
            );
        }
        return TRUE;
    }

    private function generateSalt() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < 4; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
    }
    
    public function AjaxAnswer() {
        $returnArray = array_merge($this->guiArray, $this->error);
        return json_encode($returnArray);
    }
    
    public function setUserApi($keyID, $vCode) {
        $this->apiKey[0] = $keyID;
        $this->apiKey[1] = $vCode;
        $this->getInfoFromKey();
        $this->makeRegisterArray();
    }
    
    public function setUserData($login, $password, $email = NULL) {
        $this->login = $login;
        $this->passwordHash = hash(config::password_hash_type, $password);
        if ($email) {
            $this->email = $email;
        }
    }
    
    public function register() {
        $keyStatus = 1; //Valid key status
        //check if everything ok
        try {
            if (!$this->apiKey[0] || !$this->apiKey[1]) {
                throw new Exception("There is something wrong with api. Values: " . $this->apiKey[0] . " ; " . $this->apiKey[1]);
            }
            if (!$this->registerArray[$this->login][characterID]) {
                throw new Exception("There is something wrong with characterID. Value: " . $this->registerArray[$this->login][characterID]);
            }
            if (!$this->login) {
                throw new Exception("There is something wrong with login. Value: " . $this->login);
            }
            
            if (!$this->registerArray[$this->login][corporationID]) {
                throw new Exception("There is something wrong with CorporationID. Value: " . $this->registerArray[$this->login][corporationID]);
            }
            
            if (!$this->registerArray[$this->login][corporationName]) {
                throw new Exception("There is something wrong with CorporationName. Value: " . $this->registerArray[$this->login][corporationName]);
            }
            
            if (!$this->registerArray[$this->login][allianceID]) {
                throw new Exception("There is something wrong with AllianceID. Value: " . $this->registerArray[$this->login][allianceID]);
            }
            
            if (!$this->registerArray[$this->login][allianceName]) {
                throw new Exception("There is something wrong with AllianceName. Value: " . $this->registerArray[$this->login][allianceName]);
            }
            
            if (!$this->passwordHash) {
                throw new Exception("There is something wrong with passwordHash. Value: " . $this->passwordHash);
            }
            
            if (!$this->registerArray[$this->login][permissions]) {
                throw new Exception("There is something wrong with permissions. Value: " . $this->registerArray[$this->login][permissions]);
            }
            $salt = $this->generateSalt();
            $this->db->registerNewUser($this->apiKey[0], $this->apiKey[1], $this->registerArray[$this->login][characterID], $keyStatus, $this->login, $this->registerArray[$this->login][corporationID], $this->registerArray[$this->login][corporationName], $this->registerArray[$this->login][allianceID], $this->registerArray[$this->login][allianceName], $this->passwordHash, $this->registerArray[$this->login][permissions], $this->email, $salt);
            return TRUE;
        } catch (Exception $ex) {
            echo $ex->getMessage();
            return FALSE;
        }
    }
}