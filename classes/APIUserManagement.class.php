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
    public $log; 
    private $db;    
    private $permissions;
    private $userManagement;
    //private $dbPilotInfo;
    private $apiPilotInfo;
    private $apiCharsInfo;
    private $apiKey;
    
    public function __construct($id) {
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->permissions = new permissions($id);
        $this->userManagement = new userManagement($id);
        if(!isset($id)) {
            $this->id = -1;
        } else {
            $this->id = $id;
            //$this->dbPilotInfo = $this->userManagement->getPilotInfo();
            $this->apiKey = $this->userManagement->getApiKey(1);
            $this->getApiPilotInfo();
        }
    }

    private function getApiPilotInfo(){
        $pheal = new Pheal($this->apiKey[0], $this->apiKey[1]);
        $correctKeyMask = config::correctKeyMask;
        try {
            $response = $pheal->APIKeyInfo();
            if($correctKeyMask > 0 && ($response->key->accessMask & $correctKeyMask) == 0) throw new \Pheal\Exceptions\PhealException("Incorrect key mask", -10);
            if($response->key->type != "Account") throw new \Pheal\Exceptions\PhealException("Not account key", -20);
            if($this->id == -1){
                foreach($response->key->characters as $char) {
                    $this->apiCharsInfo[] = $char;
                }
                return true;
            } else{
                for($i=0; $i<sizeof($response->key->characters); $i++){
                    if($response->key->characters[$i]->characterID == $this->apiKey[2]){
                        $isChar = true;
                        $this->apiPilotInfo = $response->key->characters[$i];
                        return true;
                    } else{
                        $isChar = false;
                    }
                }
                if(!$isChar) throw new \Pheal\Exceptions\PhealException("Not found the right character", -30);
            }
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getApiPilotInfo", "err: " . $e->getMessage());
            $c = ($this->id == -1) ? 0 : $e->getCode();
            if($c == 105 || $c == 106 || $c == 108 || $c == 112 || $c == 201 || $c == 202 || $c == 203 || $c == 204 || $c == 205 || $c == 210
             || $c == 211 || $c == 212 || $c == 221 || $c == 222 || $c == 223 || $c == 516 || $c == 522 || $c == -10 || $c == -20 || $c == -30){
                $this->permissions->unsetPermissions(array('webReg_Valid', 'TS_Valid', 'XMPP_Valid'));
            }
        }
        if($this->permissions->log->get() != NULL) $this->log->merge($this->permissions->log->get(true), "permissions");
        return false;
    }
    
    public function getPilotInfo() {
        return $this->apiPilotInfo;
    }

    public function getCharsInfo($keyID, $vCode) {
        $this->apiKey = array($keyID, $vCode);
        if($this->getApiPilotInfo()) return $this->apiCharsInfo;
    }

    public function changeUserApiKey($keyID, $vCode, $characterID) {
        
    }
    
    public function getUserKeyMask() {
        
    }
}
