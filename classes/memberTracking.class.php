<?php

use Pheal\Pheal;
use Pheal\Core\Config;

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
        Config::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        Config::getInstance()->access = new \Pheal\Access\StaticCheck();
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
                foreach ($tmparr as $value) {
                    if(isset($value[characterID])) $tmpre[] = $value[characterID];
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
        return ($row[shipType] == "Avatar" OR $row[shipType] == "Erebus" OR $row[shipType] == "Leviathan" OR $row[shipType] == "Ragnarok") ? "Titan" : "Mothership";
    }

    private function getRegion($locationID){
        try {
            $query = "SELECT `regionID` FROM `mapDenormalize` WHERE `solarSystemID` = '$locationID' LIMIT 1";
            $result = $this->db->query($query);
            $regionID = $this->db->getMysqlResult($result, 0);
            $query = "SELECT `itemName` FROM `invNames` WHERE `itemID` = '$regionID' LIMIT 1";
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
                    $shipType = $this->getShipClass($superCap[shipType]);
                    if($pilots != NULL && in_array($superCap[characterID], $pilots)){
                        if($this->checkSuperCapChanged($superCap)){
                            $SS = $this->getSS($superCap[locationID]);
                            $region = $this->getRegion($superCap[locationID]);
                            $query = "UPDATE `superCapitalList` SET `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}', `corporationName` = '{$this->keyInfo[corporationName]}',
                             `allianceName` = '{$this->keyInfo[allianceName]}', `logonDateTime` = '{$superCap[logonDateTime]}', `logoffDateTime` = '{$superCap[logoffDateTime]}', `locationID` = '{$superCap[locationID]}',
                             `SS` = '$SS', `locationName` = '{$superCap[location]}', `regionName` = '$region', `shipTypeID` = '{$superCap[shipTypeID]}', `shipTypeName` = '{$superCap[shipType]}', `shipClass` = '$shipClass'
                             WHERE `characterID`='{$superCap[characterID]}'";
                            $result = $this->db->query($query);
                        }
                    } else{
                        $SS = $this->getSS($superCap[locationID]);
                        $region = $this->getRegion($superCap[locationID]);
                        $query = "INSERT INTO `superCapitalList` SET `characterName` = '{$superCap[name]}', `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}',
                             `corporationName` = '{$this->keyInfo[corporationName]}', `allianceName` = '{$this->keyInfo[allianceName]}', `logonDateTime` = '{$superCap[logonDateTime]}', `logoffDateTime` = '{$superCap[logoffDateTime]}',
                              `locationID` = '{$superCap[locationID]}', `SS` = '$SS', `locationName` = '{$superCap[location]}', `regionName` = '$region', `shipTypeID` = '{$superCap[shipTypeID]}', `shipTypeName` = '{$superCap[shipType]}',
                               `shipClass` = '$shipClass', `characterID`='{$superCap[characterID]}'";
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
