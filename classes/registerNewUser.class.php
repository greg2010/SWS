<?php

class registerNewUser {
    
    private $apiPilotInfo = array();
    private $apiKey;
    private $error;
    
    private $login;
    private $email;
    private $passwordHash;
    private $salt;
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
    
    public function __sleep() {
         unset($this->db);
         unset($this->permissions);
         unset($this->APIUserManagement);
         unset($this->userManagement);
         return array('apiPilotInfo', 'apiKey', 'error', 'login', 'email', 'passwordHash', 'salt', 'guiArray', 'guiArray', 'registerArray', 'id');
     }
     
    public function __wakeup() {
        $this->db = db::getInstance();
        $this->permissions = new permissions();
        $this->APIUserManagement = new APIUserManagement();
        $this->userManagement = new userManagement();
    }
    
    private function getInfoFromKey() {
        try {
            $this->apiPilotInfo = $this->APIUserManagement->getCharsInfo($this->apiKey[0], $this->apiKey[1]);
            $this->error = array (
                "status" => 0,
                "message" => ""
                );
            return TRUE;
        } catch (Exception $ex) {
            $this->error = array (
                "status" => -30000,
                "message" => $ex->getMessage()
            );
            return FALSE;
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
                "accessMask" => $char[accessMask],
                "characterID" => $char[characterID],
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
    
    private function checkIfRegistered() {
        if ($this->db->getIDByName($this->login) OR $this->db->checkIfCharRegistered($this->registerArray[$this->login][characterID])) {
            throw new Exception("User is already registered!", 21);
        }
    }
    
    public function testPassword($password) {
        $numbers = '/\d/';
        $lower = '/[a-z]/';
        $upper = '/[A-Z]/';
        
        if (strlen($password) < 8) {
            throw new Exception("Your password have to have at least 8 characters in it!", 11);
        }
        
        if (!preg_match($numbers, $password)) {
            throw new Exception("You have to have at least 1 number in your password!", 11);
        }
        
        if (!preg_match($lower, $password)) {
            throw new Exception("You have to have at least 1 lower-case in your password!", 11);
        }
        
        if (!preg_match($upper, $password)) {
            throw new Exception("You have to have at least 1 upper-case in your password!", 11);
        }
    }
    
    public function testEmail($email) {
        $pattern = "/^([a-z0-9_\.-])+@[a-z0-9-]+\.([a-z]{2,4}\.)?[a-z]{2,4}$/i";
        if (!preg_match($pattern, $email)) {
            throw new Exception("Your e-mail is invalid!", 11);
        }
        if ($this->db->getIDByEmail($email)) {
            throw new Exception("This email is already used!", 11);
        }
    }

    public function AjaxAnswer() {
        $_SESSION['regArray'] = $this->registerArray;
        
        $returnArray = array_merge($this->guiArray, $this->error);
        return json_encode($returnArray);
    }
    
    public function setUserApi($keyID, $vCode) {
        $this->apiKey[0] = $keyID;
        $this->apiKey[1] = $vCode;
        $this->getInfoFromKey();
        $this->makeRegisterArray();
    }
    
    public function setUserData($login, $password, $passwordRepeat, $email = NULL) {
        $this->login = $login;
        
        $_SESSION[logObject]->setRegistrationInfo('characterID', $this->registerArray[$this->login][characterID]);
        $_SESSION[logObject]->setRegistrationInfo('keyID', $this->apiKey[0]);
        $_SESSION[logObject]->setRegistrationInfo('vCode', $this->apiKey[1]);
        $_SESSION[logObject]->setRegistrationInfo('accessMask', $this->registerArray[$this->login][permissions]);
        
        $this->testPassword($password);
        if (!$passwordRepeat) {
            throw new Exception("Password repeat", 10);
        }
        if ($passwordRepeat <> $password) {
            throw new Exception("Passwords don't match!", 11);
        }
        if ($email) {
            $this->testEmail($email);
            $this->email = $email;
            $_SESSION[logObject]->setRegistrationInfo('email', $this->email);
        }
        if ($this->registerArray[$this->login][valid] <> 1) {
            throw new Exception("Not valid character!", 20);
        }
        $this->salt = $this->generateSalt();
        $passwordWithSalt = $password . $this->salt;
        $this->passwordHash = hash(config::password_hash_type, $passwordWithSalt);
    }
    
    public function register() {
        $keyStatus = 1; //Valid key status
        //check if everything ok
        if (!$this->apiKey[0] || !$this->apiKey[1]) {
            throw new Exception("There is something wrong with api. Values: " . $this->apiKey[0] . " ; " . $this->apiKey[1], 10);
        }
        if (!$this->registerArray[$this->login][characterID]) {
            throw new Exception("There is something wrong with characterID. Value: " . $this->registerArray[$this->login][characterID], 15);
        }
        
        if (!$this->login) {
            throw new Exception("There is something wrong with login. Value: " . $this->login, 10);
        }
        $this->checkIfRegistered();

        if (!$this->passwordHash) {
            throw new Exception("There is something wrong with passwordHash. Value: " . $this->passwordHash, 10);
        }

        if (!isset($this->registerArray[$this->login][permissions])) {
            throw new Exception("There is something wrong with permissions. Value: " . $this->registerArray[$this->login][permissions], 30);
        }
        $this->db->registerNewUser($this->apiKey[0], $this->apiKey[1], $this->registerArray[$this->login][characterID], $this->login, $this->passwordHash, $this->registerArray[$this->login][permissions], $this->salt, $this->email);
        
        $query = "SELECT * from `apiPilotList` WHERE `characterID` = '{$this->registerArray[$this->login][characterID]}'";
        $result = $this->db->fetchAssoc($this->db->query($query));
        $_SESSION[logObject]->setRegistrationInfo('characterName', $result[characterName]);
        $_SESSION[logObject]->setRegistrationInfo('corporationID', $result[corporationID]);
        $_SESSION[logObject]->setRegistrationInfo('allianceID', $result[allianceID]);
        $_SESSION[logObject]->setRegistrationInfo('apiKeyMask', $result[accessMask]);
        
        return TRUE;
    }
}