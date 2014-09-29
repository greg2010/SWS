<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class notif_text {

    public $log;
    private $db;
    private $txtarr = array();

	public function __construct($text, $cid, $aid, $isf){
        $this->txtarr = yaml_parse($text);
        $this->db = db::getInstance();
        $this->log = new logging();
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');

        $this->Owner($cid, $aid);
        if($this->txtarr[aggressorID]) $this->aggressor();
        if(!$isf){
            if($this->txtarr[corpID] || $this->txtarr[aggressorCorpID]) $this->CorpID();
            if($this->txtarr[allianceID] || $this->txtarr[aggressorAllianceID]) $this->AllianceID();
        }
        if($this->txtarr[typeID]) $this->typeID();
        if($this->txtarr[wants]) $this->wants();
        if($this->txtarr[moonID]) $this->moonID();
        if($this->txtarr[solarSystemID]) $this->solarSystemID();
        if($this->txtarr[planetID]) $this->planetID();
    }

    private function Owner($cid, $aid){
        try {
            $query = "SELECT `name`, `ticker` FROM `corporationList` WHERE `id`='$cid'";
            $result = $this->db->query($query);
            $resarr = $this->db->fetchAssoc($result);
            $this->txtarr[OwnerCorpName] = $resarr[name];
            $this->txtarr[OwnerCorpTicker] = $resarr[ticker];
        } catch (Exception $ex) {
            $this->log->put("Owner Corp", "err " . $ex->getMessage());
        }
        try {
            $query = "SELECT `name`, `ticker` FROM `allianceList` WHERE `id`='$aid'";
            $result = $this->db->query($query);
            $resarr = $this->db->fetchAssoc($result);
            $this->txtarr[OwnerAllyName] = $resarr[name];
            $this->txtarr[OwnerAllyTicker] = $resarr[ticker];
        } catch (Exception $ex) {
            $this->log->put("Owner Alli", "err " . $ex->getMessage());
        }
    }

    private function aggressor(){
        $pheal = new Pheal(NULL, NULL, "eve");
        try{
            $response = $pheal->CharacterName(array("IDs" => $this->txtarr[aggressorID]));
            $this->txtarr[aggressorName] = $response->characters[0]->name;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("aggressor", "err " . $e->getMessage());
        }
    }

    private function CorpID(){
        $pheal = new Pheal(NULL, NULL, "corp");
        try{
            $response = ($this->txtarr[corpID]) ? ($pheal->CorporationSheet(array("corporationID" => $this->txtarr[corpID]))) : ($pheal->CorporationSheet(array("corporationID" => $this->txtarr[aggressorCorpID])));
            $this->txtarr[corpName] = $response->corporationName;
            $this->txtarr[corpTicker] = $response->ticker;
        } catch (\Pheal\Exceptions\PhealException $e){
             $this->log->put("CorpID", "err " . $e->getMessage());
        }
    }

    private function AllianceID(){
        $pheal = new Pheal(NULL, NULL, "eve");
        try{
            $response = $pheal->AllianceList();
            foreach($response->alliances as $row){
                if($row->allianceID == $this->txtarr[allianceID] || $row->allianceID == $this->txtarr[aggressorAllianceID]){
                    $this->txtarr[allyName] = $row->name;
                    $this->txtarr[allyTicker] = $row->shortName;
                    break;
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

    public function getText(){
        return yaml_emit($this->txtarr);
    }
}

?>
