<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

interface IAPIUserManagement {
    function getPilotInfo();
    function getUserKeyMask();
    function changeUserApiKey($keyID, $vCode, $characterID);
    function getCharsInfo($keyID, $vCode); 
    function getCorpInfo($keyID, $vCode);   
}

class APIUserManagement implements IAPIUserManagement {
    
    protected $accessMask;
    protected $allowedList;
    protected $id;
    public $log; 
    private $db;    
    private $permissions;
    private $userManagement;
    private $apiPilotInfo;
    private $apiCharsInfo;
    private $apiKey;
    
    public function __construct($id = NULL) {
        $this->db = db::getInstance();
        $this->log = new logging();
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        $this->permissions = new permissions($id);
        $this->userManagement = new userManagement($id);
        if(!isset($id)) {
            $this->id = -1;
        } else {
            $this->id = $id;
            $this->apiKey = $this->userManagement->getApiKey(1);
            $this->getApiPilotInfo();
        }
    }

    private function getApiPilotInfo($keyID = NULL, $vCode = NULL, $corp = false){
        $pheal = (isset($keyID) && isset($vCode)) ? (new Pheal($keyID, $vCode)) : (new Pheal($this->apiKey[0], $this->apiKey[1]));
        $correctKeyMask = config::correctKeyMask;
        try {
            $response = $pheal->APIKeyInfo();
            if($corp){
                if($response->key->type != "Corporation") throw new \Pheal\Exceptions\PhealException("Not corporation key", -20);
            } else{
                if($correctKeyMask > 0 && ($response->key->accessMask & $correctKeyMask) == 0) throw new \Pheal\Exceptions\PhealException("Incorrect key mask", -10);
                if($response->key->type != "Account") throw new \Pheal\Exceptions\PhealException("Not account key", -20);
            }
            if(isset($keyID) && isset($vCode)){
                if($corp){
                    $this->apiCharsInfo = $response->key->characters[0];
                    $this->apiCharsInfo[accessMask] = $response->key->accessMask;
                } else{
                    foreach($response->key->characters as $char){
                        $this->apiCharsInfo[] = $char;
                    }
                }
                return true;
            } else{
                for($i=0; $i<sizeof($response->key->characters); $i++){
                    if($response->key->characters[$i]->characterID == $this->apiKey[2]){
                        //$isChar = true;
                        $this->apiPilotInfo = $response->key->characters[$i];
                        $this->apiPilotInfo[accessMask] = $response->key->accessMask;
                        return true;
                    }/* else{
                        $isChar = false;
                    }*/
                }
                //if(!$isChar) 
                throw new \Pheal\Exceptions\PhealException("Not found the right character", -30);
            }
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getApiPilotInfo", "err " . $e->getMessage());
            $c = $e->getCode();
            $this->log->put("getApiPilotInfo_code", $c);
            if(isset($keyID) && isset($vCode)){
                if($c == 105 || $c == 106 || $c == 108 || $c == 112 || $c == 201 || $c == 202 || $c == 203 || $c == 204 || $c == 205 || $c == 210
                 || $c == 211 || $c == 212 || $c == 221 || $c == 222 || $c == 223 || $c == 516 || $c == 522 || $c == -10 || $c == -20 || $c == -30){
                    $this->permissions->unsetPermissions(array('webReg_Valid', 'TS_Valid', 'XMPP_Valid'));
                }
            }
        }
        if($this->permissions->log->get() != NULL) $this->log->merge($this->permissions->log->get(true), "permissions");
        return false;
    }
    
    private function getCorporationTicker($id){
        //
    }

    private function getAllianceTicker($id){
        //
    }

    public function getCorpInfo($keyID, $vCode) {
        if($this->getApiPilotInfo($keyID, $vCode, true)) return $this->apiCharsInfo;
    }

    public function getPilotInfo() {
        return $this->apiPilotInfo;
    }

    public function getCharsInfo($keyID, $vCode) {
        if($this->getApiPilotInfo($keyID, $vCode)) return $this->apiCharsInfo;
    }

    public function changeUserApiKey($keyID, $vCode, $characterID) {
        
    }
    
    public function getUserKeyMask() {
        
    }
}
