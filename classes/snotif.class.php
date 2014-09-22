<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class snotif {

    public $log;
    private $db;
    protected $OwnerCorporationID;
    protected $OwnerAllianceID;
    private $txtarr = array();

	public function __construct($text, $oc = NULL, $oa = NULL){
        $this->OwnerCorporationID = $oc;
        $this->OwnerAllianceID = $oa;
        $this->txtarr = yaml_parse($text);
        $this->db = db::getInstance();
        $this->log = new logging();
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        $this->supplementedNotifText();
    }

    private function aggressor(){
        $pheal = new Pheal($this->keyID, $this->vCode, "eve");
        try{
            $response = $pheal->CharacterName(array("IDs" => $this->txtarr[aggressorID]));
            $this->txtarr[aggressorName] = $response->characters[0]->name;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("aggressor", "err " . $e->getMessage());
        }
    }

    private function CorpID(){
        $pheal = new Pheal($this->keyID, $this->vCode, "corp");
        try{
            $response = $pheal->CorporationSheet(array("corporationID" => $this->txtarr[corpID]));
            $this->txtarr[corpName] = $response->corporationName;
            $this->txtarr[corpTicker] = $response->ticker;
        } catch (\Pheal\Exceptions\PhealException $e){
             $this->log->put("CorpID", "err " . $e->getMessage());
        }
        if($this->OwnerCorporationID != NULL){
            try{
                $response = $pheal->CorporationSheet(array("corporationID" => $this->OwnerCorporationID));
                $this->txtarr[OwnerCorpName] = $response->corporationName;
                $this->txtarr[OwnerCorpTicker] = $response->ticker;
            } catch (\Pheal\Exceptions\PhealException $e){
                $this->log->put("CorpID_owner", "err " . $e->getMessage());
            }
        }
    }

    private function AllianceID(){
        $pheal = new Pheal($this->keyID, $this->vCode, "eve");
        try{
            $response = $pheal->AllianceList();
            foreach($response->alliances as $row){
                if($this->OwnerAllianceID != NULL && $row->allianceID == $this->OwnerAllianceID){
                    $this->txtarr[OwnerAllyName] = $row->name;
                    $this->txtarr[OwnerAllyTicker] = $row->shortName;
                }
                if($row->allianceID == $this->txtarr[allianceID] || $row->allianceID == $this->txtarr[aggressorAllianceID]){
                    $this->txtarr[allyName] = $row->name;
                    $this->txtarr[allyTicker] = $row->shortName;
                }
            }
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("AllianceID", "err " . $e->getMessage());
        }
    }

    private function typeID(){
        try {
            $query = "SELECT `typeName` FROM `invTypes` WHERE `typeID`='{$this->txtarr[typeID]}' LIMIT 1";
            $result = $this->db->query($query);
            $this->txtarr[typeName] = $this->db->getMysqlResult($result);
        } catch (Exception $ex) {
            $this->log->put("typeID", "err " . $ex->getMessage());
        }
    }

    private function wants(){
        for($i=0; $i < count($this->txtarr[wants]); $i++){
            try {
                $query = "SELECT `typeName` FROM `invTypes` WHERE `typeID`='{$this->txtarr[wants][$i][typeID]}' LIMIT 1";
                $result = $this->db->query($query);
                $this->txtarr[wants][$i][typeName] = $this->db->getMysqlResult($result);
            } catch (Exception $ex) {
                $this->log->put("wants " . $i, "err " . $ex->getMessage());
            }
        }
    }

    private function moonID(){
        try {
            $query = "SELECT `itemName` FROM `mapDenormalize` WHERE `itemID`='{$this->txtarr[moonID]}' LIMIT 1";
            $result = $this->db->query($query);
            $this->txtarr[moonName] = $this->db->getMysqlResult($result);
        } catch (Exception $ex) {
            $this->log->put("moonID", "err " . $ex->getMessage());
        }
    }

    private function solarSystemID(){
        try {
            $query = "SELECT `solarSystemName` FROM `mapSolarSystems` WHERE `solarSystemID`='{$this->txtarr[solarSystemID]}' LIMIT 1";
            $result = $this->db->query($query);
            $this->txtarr[solarSystemName] = $this->db->getMysqlResult($result);
        } catch (Exception $ex) {
            $this->log->put("solarSystemID", "err " . $ex->getMessage());
        }
    }

    private function planetID(){
        try {
            $query = "SELECT `itemName` FROM `mapDenormalize` WHERE `itemID`='{$this->txtarr[planetID]}' LIMIT 1";
            $result = $this->db->query($query);
            $this->txtarr[planetName] = $this->db->getMysqlResult($result);
        } catch (Exception $ex) {
            $this->log->put("planetID", "err " . $ex->getMessage());
        }
    }
    
    private function supplementedNotifText(){
        if($this->txtarr[aggressorID]) $this->aggressor();
        if($this->txtarr[corpID] || $this->txtarr[aggressorCorpID] || $this->OwnerCorporationID != NULL) $this->CorpID();
        if($this->txtarr[allianceID] || $this->txtarr[aggressorAllianceID] || $this->OwnerAllianceID != NULL) $this->AllianceID();
        if($this->txtarr[typeID]) $this->typeID();
        if($this->txtarr[wants]) $this->wants();
        if($this->txtarr[moonID]) $this->moonID();
        if($this->txtarr[solarSystemID]) $this->solarSystemID();
        if($this->txtarr[planetID]) $this->planetID();
    }

    public function getText(){
        return yaml_emit($this->txtarr);
    }
}

?>
