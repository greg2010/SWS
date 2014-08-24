<?php

use Pheal\Pheal;

interface IAPIUserManagement {
    function getPilotInfo();
    function getUserKeyMask();
    function changeUserApiKey($keyID, $vCode);
    
}

class APIUserManagement implements IAPIUserManagement {
    
    protected $accessMask;
    protected $allowedList;
    protected $id;
    private $db;    
    private $permissions;
    private $userManagement;
    private $dbPilotInfo;
    private $apiPilotInfo;
    private $apiKey;
    
    public function __construct($id) {
        $this->id = $id;
        $this->db = db::getInstance();
        $this->permissions = new permissions($id);
        $this->userManagement = new UserManagement($id);
        $this->dbPilotInfo = $this->userManagement->getPilotInfo();
        $this->apiKey = $this->userManagement->getApiKey(1);
        $this->getApiPilotInfo();
    }

    private function getApiPilotInfo() {
        //Populates $apiPrivateInfo
        $pheal = new Pheal($apiKey[0], $apiKey[1]);
        try {
            $response = $pheal->APIKeyInfo();
            // $response->key->accessMask == 0
            // $response->key->type == Account
            // $this->unsetPermissions(array('webReg_Valid'))
            for($i=0; $i<sizeof($response->key->characters); $i++){
                if($response->key->characters[$i]->characterID === $apiKey[2]){
                    $apiPilotInfo = $response->key->characters[$i];
                }
            }
        } catch (\Pheal\Exceptions\PhealException $e) {
            return $e->getMessage();
        }
    }

    public function getPilotInfo() {
        return $this->apiPilotInfo;
    }

    public function changeUserApiKey($keyID, $vCode) {
        
    }
    
    public function getUserKeyMask() {
        
    }
}
