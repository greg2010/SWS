<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class memberTracking {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $superCapList = array();
    private $superCapitalTypeIDs = array();

	public function __construct($keyInfo = array()){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyInfo = $keyInfo;
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
    }

    private function getSuperCapitalTypeIDs(){
        try {
            $query = "SELECT `typeID` FROM `invTypes` WHERE `groupID` IN ('659', '30')";
            $result = $this->db->query($query);
            $tmparr = $this->db->fetchAssoc($result);
            if(isset($tmparr)){
                foreach ($tmparr as $value) {
                    if(isset($value[typeID])) $tmpre[] = $value[typeID];
                }
                return $tmpre;
            }
        } catch (Exception $ex) {
            $this->log->put("getSuperCapitalTypeIDs", "err " . $ex->getMessage());
        }
    }

    private function getSuperCapitalPilots(){
        try {
            $query = "SELECT `characterID` FROM `superCapitalList` WHERE `corporationID`='{$this->keyInfo[corporationID]}'";
            $result = $this->db->query($query);
            $tmparr = $this->db->fetchAssoc($result);
            if(isset($tmparr)){
                if(count($tmparr) == 1){
                    if(isset($tmparr[characterID])) $tmpre[] = $tmparr[characterID];
                }else{
                    foreach ($tmparr as $value) {
                        if(isset($value[characterID])) $tmpre[] = $value[characterID];
                    }
                }
                return $tmpre;
            }
        } catch (Exception $ex) {
            $this->log->put("getSuperCapitalPilots", "err " . $ex->getMessage());
        }
    }

    private function getSuperCapList(){
        $pheal = new Pheal($this->keyInfo[keyID], $this->keyInfo[vCode], "corp");
        try{
            $response = $pheal->MemberTracking(array("extended" => "1"));
            foreach ($response->members as $member) {
                if(in_array($member->shipTypeID, $this->superCapitalTypeIDs)){
                    $this->superCapList[] = array(
                        'characterID' => $member->characterID,
                        'name' => $member->name,
                        'logonDateTime' => $member->logonDateTime,
                        'logoffDateTime' => $member->logoffDateTime,
                        'locationID' => $member->locationID,
                        'location' => $member->location,
                        'shipTypeID' => $member->shipTypeID,
                        'shipType' => $member->shipType
                    );
                }
            }
            return ($this->superCapList != NULL) ? true : false;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getSuperCapList", "err " . $e->getMessage());
            return false;
        }
    }

    private function checkSuperCapChanged($superCap = array()){
        try {
            $query = "SELECT `corporationID`, `allianceID`, `logonDateTime`, `logoffDateTime`, `locationID`, `shipTypeID` FROM `superCapitalList` WHERE `characterID`='{$superCap[characterID]}'";
            $result = $this->db->query($query);
            $dbpos = $this->db->fetchRow($result);
            return ($this->keyInfo[corporationID] == $dbpos[0] && $this->keyInfo[allianceID] == $dbpos[1] && $superCap[logonDateTime] == $dbpos[2] && $superCap[logoffDateTime] == $dbpos[3]
             && $superCap[locationID] == $dbpos[4] && $superCap[shipTypeID] == $dbpos[5]) ? false : true;
        } catch (Exception $ex) {
            $this->log->put("checkSuperCapChanged " . $superCap[characterID], "err " . $ex->getMessage());
            return false;
        }
    }

    private function getShipClass($shipType){
        return ($shipType == "Avatar" OR $shipType == "Erebus" OR $shipType == "Leviathan" OR $shipType == "Ragnarok") ? "Titan" : "Mothership";
    }

    private function getRegion($locationID){
        try {
            $query = "SELECT `a`.`itemName` FROM `mapDenormalize` AS `a` INNER JOIN `mapDenormalize` AS `b` ON `b`.`itemID` = '$locationID' WHERE `a`.`itemID` = `b`.`regionID`";
            $result = $this->db->query($query);
            return $this->db->getMysqlResult($result, 0);
        } catch (Exception $ex) {
            $this->log->put("getRegion " . $locationID, "err " . $ex->getMessage());
        }
    }

    private function getSS($locationID){
        try {
            $query = "SELECT `security` FROM `mapSolarSystems` WHERE `solarSystemID` = '$locationID' LIMIT 1";
            $result = $this->db->query($query);
            return ($this->db->getMysqlResult($result, 0) < 0) ? 0.0 : round($this->db->getMysqlResult($result, 0), 1);
        } catch (Exception $ex) {
            $this->log->put("getSS " . $locationID, "err " . $ex->getMessage());
        }
    }

    public function updateSuperCapList(){
        $this->superCapitalTypeIDs = $this->getSuperCapitalTypeIDs();
        $pilots = $this->getSuperCapitalPilots();
        if($this->getSuperCapList()){
            foreach($this->superCapList as $superCap){
                try {
                    $shipClass = $this->getShipClass($superCap[shipType]);
                    if($pilots != NULL && in_array($superCap[characterID], $pilots)){
                        if($this->checkSuperCapChanged($superCap)){
                            $SS = $this->getSS($superCap[locationID]);
                            $region = $this->getRegion($superCap[locationID]);
                            $query = "UPDATE `superCapitalList` SET `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}', `logonDateTime` = '{$superCap[logonDateTime]}',
                             `logoffDateTime` = '{$superCap[logoffDateTime]}', `locationID` = '{$superCap[locationID]}', `SS` = '$SS', `locationName` = '{$superCap[location]}', `regionName` = '$region',
                              `shipTypeID` = '{$superCap[shipTypeID]}', `shipTypeName` = '{$superCap[shipType]}', `shipClass` = '$shipClass' WHERE `characterID`='{$superCap[characterID]}'";
                            $result = $this->db->query($query);
                        }
                    } else{
                        $SS = $this->getSS($superCap[locationID]);
                        $region = $this->getRegion($superCap[locationID]);
                        $charName = $this->db->real_escape_string($superCap[name]);
                        $query = "INSERT INTO `superCapitalList` SET `characterID` = '{$superCap[characterID]}', `characterName` = '$charName', `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}',
                             `logonDateTime` = '{$superCap[logonDateTime]}', `logoffDateTime` = '{$superCap[logoffDateTime]}', `locationID` = '{$superCap[locationID]}', `SS` = '$SS', `locationName` = '{$superCap[location]}',
                             `regionName` = '$region', `shipTypeID` = '{$superCap[shipTypeID]}', `shipTypeName` = '{$superCap[shipType]}', `shipClass` = '$shipClass'";
                        $result = $this->db->query($query);
                    }

                } catch (Exception $ex) {
                    $this->log->put("updateSuperCapList " . $superCap[characterID], "err " . $ex->getMessage());
                }
            }
        }
        return $this->log->get();
    }

}

?>
