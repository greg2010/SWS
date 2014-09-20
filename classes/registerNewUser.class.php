<?php


use Pheal\Pheal;

class registerNewUser {
    
    private $apiPilotInfo = array();
    private $apiKey;
    private $error;
    
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
    //          "corporationName" => $char[corporationName],
    //          "allianceName" => $char[allianceName],
                "valid" => $canRegister
            );
            $this->registerArray[$char[characterName]] = array (
                "characterID" => $char[characterID],
                "corporationID" => $char[corporationID],
                "corporationName" => $char[corporationName],
                "allianceID" => $char[allianceID],
                "allianceName" => $char[allianceName],
                "permissions" => $keyPermissions
            );
        }
        return TRUE;
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
    
    public function setUserData($login, $password) {
        try {
            $this->login = $login;
            $this->passwordHash = hash(sha512, $password);
            
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function registerNewUser () {
        $regCheck = $this->makeRegisterArray();
    }
}