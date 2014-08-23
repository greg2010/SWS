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
    
    private $dbPilotInfo;
    private $apiPilotInfo;
    private $apiKey;
    
    public function __construct($id, $accessMask = NULL) {
        parent::__construct($id);
        if ($accessMask) {
        $this->accessMask = $accessMask;
        }
        $this->db = db::getInstance();
        $this->permissions = new permissions($id);
        $this->UserManagement = new APIUserManagement($id);
    }

    protected function getPilotInfo() {
        return $this->dbPilotInfo;
    }
    
    private function getDbPilotInfo() {
        //Populates $dbPilotInfo
        try {
            $query = "SELECT * FROM `pilotInfo` WHERE `id` = '$this->id'";
            $result = $this->db->query($db);
            $dbPilotInfo = $this->db->fetchArray($result);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    private function getApiKey() {
        try {
            $query = "SELECT `keyID`, `vCode`, `characterID` FROM `apiList` WHERE `id` = '$this->id' AND `keyStatus` = '1'";
            $result = $this->db->query($db);
            $apiKey = $this->db->fetchRow($result);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
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

?>
