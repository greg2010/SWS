<?php


use Pheal\Pheal;

class registerNewUser {
    
    private $apiPilotInfo = array();
    private $userPermissionsInfo = array();
    
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
        $pheal = new Pheal($this->apiKey, $this->vCode);
        try {
            $response = $pheal->APIKeyInfo();
            for($i=0; $i<sizeof($response->key->characters); $i++){
                $this->apiPilotInfo[] = $response->key->characters[$i];
            }
        } catch (\Pheal\Exceptions\PhealException $e) {
            //Authentication failure etc
            return $e->getMessage();
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
            $apiError = $this->getInfoFromKey();
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
}
