<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class APIUserManagement{
    
    private $userManagement;
    private $apiPilotInfo;
    private $apiCharsInfo;
    
    public function __construct() {
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        PhealConfig::getInstance()->cache = new \Pheal\Cache\NullStorage();
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
            if($correctKeyMask > 0 && ($response->key->accessMask & $correctKeyMask) != $correctKeyMask) throw new \Pheal\Exceptions\PhealException("Incorrect key mask", -204);
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
        $APIrgManagement = new APIOrgManagement();
        $charArray = array(
            'accessMask' => $mask,
            'characterID' => $char->characterID,
            'characterName' => $char->characterName,
            'corporationID' => $char->corporationID,
            'corporationName' => $char->corporationName,
            'corporationTicker' => $APIrgManagement->getCorporationTicker($char->corporationID)
        );
        if($char->allianceID <> 0){
            $charArray[allianceID] = $char->allianceID;
            $charArray[allianceName] = $char->allianceName;
            $charArray[allianceTicker] = $APIrgManagement->getAllianceTicker($char->allianceID);
        }
        return $charArray;
    }

    public function getCharacterName($id){
        $Name = NULL;
        try{
            $Name = $this->userManagement->getCharacterName($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getCharacterName " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Name != NULL){
            return $Name;
        } else{
            $pheal = new Pheal(NULL, NULL, "eve");
            $response = $pheal->CharacterName(array("IDs" => $id));
            return $response->characters[0]->name;
        }
    }

    public function getCharacterID($name){
        $ID = NULL;
        try{
            $ID = $this->userManagement->getCharacterID($name);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getCharacterID " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($ID != NULL){
            return $ID;
        } else{
            $pheal = new Pheal(NULL, NULL, "eve");
            $response = $pheal->CharacterID(array("names" => $name));
            return $response->characters[0]->characterID;
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
    
    public function getAllianceStandings() {
        $db = db::getInstance();
        $apiOrgManagement = new APIOrgManagement();
        
        $query = "SELECT * FROM `apiPilotList` WHERE `keyStatus` > '0'";
        $apis = $db->fetchAssoc($db->query($query));
        //TODO: EXCEPTIONS
        $apiCorrect = array();
        $allianceIDList = array();
        foreach ($apis as $api) {
            if ($api[accessMask] & 16 == TRUE) {
                //Filter APIs without access to the designated XML
                
                $requestArray = array (
                    "characterID" => "",
                    "corporationID" => "",
                    "allianceID" => "$api[allianceID]"
                );
                $keyPermissions = $this->userManagement->getAllowedListMask($requestArray);
                if (!$keyPermissions) {
                    $keyPermissions = 0;
                }
                $permissions = new permissions();
                $permissions->setUserMask($keyPermissions);
                
                if ($permissions->hasPermission("webReg_Valid") == TRUE) {
                    //Filter alliances with access to the resources
                    if (array_search($api[allianceID], $allianceIDList) === FALSE) {
                        //Only one API per alliance
                        $apiCorrect[] = $api;
                        $allianceIDList[] = $api[allianceID];
                    }
                }
            }
        }
        $output = array();
        
        try {
            foreach ($apiCorrect as $api) {
                //TODO: REWRITE
                $pheal = new Pheal($api[keyID], $api[vCode], "char");
                $response = $pheal->contactList(array("characterID" => $api[characterID]));
                foreach ($response->allianceContactList as $row) {
                    switch ($row->contactTypeID) {
                        case 2:
                            $type = "corp";
                            $ticker = $apiOrgManagement->getCorporationTicker($apiOrgManagement->getCorporationID($row->contactName));
                            break;
                        case 16159:
                            $type = "alliance";
                            $ticker = $apiOrgManagement->getAllianceTicker($apiOrgManagement->getAllianceID($row->contactName));
                            break;
                        default:
                            $type = "char";
                            break;
                    }
                    $output[$apiOrgManagement->getAllianceName($api[allianceID])][$type][$row->contactName]["actualStanding"] = $row->standing;
                    $output[$apiOrgManagement->getAllianceName($api[allianceID])][$type][$row->contactName]["contactTicker"] = $ticker;
                    if ($row->standing > 5) {
                        $picStanding = 10;
                    } elseif ($row->standing < -5) {
                        $picStanding = -10;
                    } elseif ($row->standing > 0) {
                        $picStanding = 5;
                    } elseif ($row->standing < 0) {
                        $picStanding = -5;
                    } else {
                        $picStanding = 0;
                    }
                    
                    $output[$apiOrgManagement->getAllianceName($api[allianceID])][$type][$row->contactName]["picStanding"] = $picStanding;
                    
                    unset($picStanding);
                    
                    $gotTypes = array_keys($output[$apiOrgManagement->getAllianceName($api[allianceID])]);
                    foreach ($gotTypes as $type) {
                         arsort($output[$apiOrgManagement->getAllianceName($api[allianceID])][$type]);
                    }
                }
            }
        } catch(\Pheal\Exceptions\PhealException $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }
        
        return $output;
    }
    
    public function getServerStatus() {
        $pheal = new Pheal();
        $response = $pheal->serverScope->ServerStatus();
        if ($response->serverOpen) {
            $arr['status'] = 'Online';
        } else {
            $arr['status'] = 'Offline';
        }
        $arr['online'] = $response->onlinePlayers;
        return $arr;
    }
}
