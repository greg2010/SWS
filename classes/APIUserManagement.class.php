<?php

interface IAPIUserManagement {
    
    function getUserKeyMask();
    function changeUserApiKey($keyID, $vCode);
    
}

class APIUserManagement implements IAPIUserManagement {

    use Pheal\Pheal;
    
    protected $accessMask;
    protected $allowedList;
    protected $pilotInfo;
    protected $id;
    
    private $permissions;
    private $userManagement;
    private $dbPilotInfo;
    private $apiPilotInfo;
    private $apiKey;
    
    public function __construct($id, $accessMask = NULL) {
        if ($accessMask) {
        $this->accessMask = $accessMask;
        }
        $this->id = $id;
        $this->db = db::getInstance();
        $this->permissions = new permissions($id);
        $this->userManagement = new APIUserManagement($id);
        $this->dbPilotInfo = $this->userManagement->getPilotInfo();
        $this->apiKey = $this->userManagement->getApiKey(1);
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
    
    private function comparePilotInfo () {
        //Compare IDs, if not compare with AllowedList
    }

    public function changeUserApiKey($keyID, $vCode) {
        
    }
    
    public function getUserKeyMask() {
        
    }
    
    private function verifyApiInfo() {
        
    }
}
