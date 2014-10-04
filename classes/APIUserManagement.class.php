<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class APIUserManagement{
    
    private $userManagement;
    private $apiPilotInfo;
    private $apiCharsInfo;
    
    public function __construct() {
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        $this->userManagement = new userManagement();
    }

    private function getApiPilotInfo($characterID, $keyID, $vCode, $corp = false){
        if(isset($keyID) && isset($vCode)){
            $pheal = new Pheal($keyID, $vCode);
        } else{
            throw new \Pheal\Exceptions\PhealException("Incorrect keyID or vCode", -201);
        }
        $correctKeyMask = config::correctKeyMask;
        $response = $pheal->APIKeyInfo();
        if($corp){
            if($response->key->type != "Corporation") throw new \Pheal\Exceptions\PhealException("Not corporation key", -202);
            $this->apiPilotInfo = $this->makeCharArray($response->key->accessMask, $response->key->characters[0]);
        } else{
            if($correctKeyMask > 0 && ($response->key->accessMask & $correctKeyMask) == 0) throw new \Pheal\Exceptions\PhealException("Incorrect key mask", -204);
            if($response->key->type != "Account") throw new \Pheal\Exceptions\PhealException("Not account key", -203);
            if(isset($characterID)){
                for($i=0; $i<sizeof($response->key->characters); $i++){
                    if($response->key->characters[$i]->characterID == $characterID){
                        $this->apiPilotInfo = $this->makeCharArray($response->key->accessMask, $response->key->characters[$i]);
                        return;
                    }
                }
                throw new \Pheal\Exceptions\PhealException("Not found the right character", -205);
            } else{
                foreach($response->key->characters as $char){
                    $this->apiCharsInfo[] = $this->makeCharArray($response->key->accessMask, $char);
                }
            }
        }
    }

    private function makeCharArray($mask, $char){
        $charArray = array(
            'accessMask' => $mask,
            'characterID' => $char->characterID,
            'characterName' => $char->characterName,
            'corporationID' => $char->corporationID,
            'corporationName' => $char->corporationName,
            'corporationTicker' => $this->getCorporationTicker($char->corporationID)
        );
        if($char->allianceID <> 0){
            $charArray[allianceID] = $char->allianceID;
            $charArray[allianceName] = $char->allianceName;
            $charArray[allianceTicker] = $this->getAllianceTicker($char->allianceID);
        }
        return $charArray;
    }
    
    private function getCorporationTicker($id){
        $Ticker = NULL;
        try{
            $Ticker = $this->userManagement->getCorporationTicker($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getCorporationTicker " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Ticker != NULL){
            return $Ticker;
        } else{
            $pheal = new Pheal(NULL, NULL, "corp");
            $response = $pheal->CorporationSheet(array("corporationID" => $id));
            try{
                $this->userManagement->recordCorporationInfo($id, $response->corporationName, $response->ticker);
            } catch(Exception $ex){
                throw new \Pheal\Exceptions\PhealException("recordCorporationInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
            }
            return $response->ticker;
        }
    }

    private function getAllianceTicker($id){
        $Ticker = NULL;
        try{
            $Ticker = $this->userManagement->getAllianceTicker($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getAllianceTicker " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Ticker != NULL){
            return $Ticker;
        } else{
            $pheal = new Pheal(NULL, NULL, "eve");
            $response = $pheal->AllianceList();
            foreach($response->alliances as $row){
                if($row->allianceID == $id){
                    try{
                        $this->userManagement->recordAllianceInfo($id, $row->name, $row->shortName);
                    } catch(Exception $ex){
                        throw new \Pheal\Exceptions\PhealException("recordAllianceInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
                    }
                    return $row->shortName;
                }
            }
        }
    }

    public function getCorpInfo($keyID, $vCode) {
        try{
            $this->getApiPilotInfo(NULL, $keyID, $vCode, true);
            return $this->apiPilotInfo;
        } catch(\Pheal\Exceptions\PhealException $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getPilotInfo($characterID, $keyID, $vCode) {
        try{
            $this->getApiPilotInfo($characterID, $keyID, $vCode, false);
            return $this->apiPilotInfo;
        } catch(\Pheal\Exceptions\PhealException $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function getCharsInfo($keyID, $vCode) {
        try{
            $this->getApiPilotInfo(NULL, $keyID, $vCode, false);
            return $this->apiCharsInfo;
        } catch(\Pheal\Exceptions\PhealException $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public function changeUserApiKey($keyID, $vCode, $characterID) {
        
    }
    
    public function getUserKeyMask() {
        
    }
}
