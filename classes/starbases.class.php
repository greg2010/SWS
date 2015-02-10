<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class starbases {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $poslist = array();

	public function __construct($keyInfo = array()){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyInfo = $keyInfo;
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
    }

    private function getStarbaseList(){
        $pheal = new Pheal($this->keyInfo[keyID], $this->keyInfo[vCode], "corp");
        try{
            $response = $pheal->StarbaseList();
            foreach($response->starbases as $row){
                $this->poslist[] = array(
                    'posID' => $row[itemID],
                    'typeID' => $row[typeID],
                    'locationID' => $row[locationID],
                    'moonID' => $row[moonID],
                    'state' => $row[state],
                    'stateTimestamp' => $row[stateTimestamp]
                );
            }
            if($this->poslist != NULL){
                $this->getLocations();
                for($i=0; $i<count($this->poslist); $i++){
                    $this->poslist[$i][changed] = $this->checkStarbaseChanged($this->poslist[$i]);
                }
                return true;
            } else return false;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getStarbaseList", "err " . $e->getMessage());
            return false;
        }
    }

    private function getPosIds(){
        try {
            $query = "SELECT `posID` FROM `posList` WHERE `corporationID` = '{$this->keyInfo[corporationID]}'";
            $result = $this->db->query($query);
            $tmparr = $this->db->fetchAssoc($result);
            if(isset($tmparr)){
                foreach ($tmparr as $value) {
                    if(isset($value[posID])) $tmpre[] = $value[posID];
                }
                return $tmpre;
            }
        } catch (Exception $ex) {
            $this->log->put("getPosIds", "err " . $ex->getMessage());
        }
    }

    private function getPosList(){
        try {
            $query = "SELECT `posID`, `typeID`, `locationID`, `state` FROM `posList` WHERE `corporationID` = '{$this->keyInfo[corporationID]}'";
            $result = $this->db->query($query);
            return $this->db->toArray($result);
        } catch (Exception $ex) {
            $this->log->put("getPosList", "err " . $ex->getMessage());
        }
    }

    private function checkStarbaseAlive(){
        $ids = $this->getPosIds();
        if($ids != NULL){
            foreach ($ids as $posID) {
                try {
                    for($j=0; $j<count($this->poslist); $j++){
                        if($this->poslist[$j][posID] == $posID) break;
                        if($j == (count($this->poslist)-1)){
                            $query = "DELETE FROM `posList` WHERE `posID`='$posID'";
                            $result = $this->db->query($query);
                            $this->log->put("checkStarbaseAlive " . $posID, "ok delete");
                        }
                    }
                } catch (Exception $ex) {
                    $this->log->put("checkStarbaseAlive " . $posID, "err " . $ex->getMessage());
                }
            }
        }
    }

    private function checkStarbaseChanged($pos = array()){
        try {
            $query = "SELECT `locationID`, `moonID`, `corporationID`, `allianceID`, `x`, `y`, `z`, `name` FROM `posList` WHERE `posID`='{$pos[itemID]}'";
            $result = $this->db->query($query);
            $dbpos = $this->db->fetchRow($result);
            return ($pos[locationID] == $dbpos[0] && $pos[moonID] == $dbpos[1] && $this->keyInfo[corporationID] == $dbpos[2] && $this->keyInfo[allianceID] == $dbpos[3]
             && $pos[x] == $dbpos[4] && $pos[y] == $dbpos[5] && $pos[z] == $dbpos[6] && $pos[name] == $dbpos[7]) ? false : true;
        } catch (Exception $ex) {
            $this->log->put("checkStarbaseNotChanged " . $pos[itemID], "err " . $ex->getMessage());
            return false;
        }
    }

    private function getMoonName($id){
        try {
            $query = "SELECT `itemName` FROM `mapDenormalize` WHERE `itemID`='$id' LIMIT 1";
            $result = $this->db->query($query); 
            $row = $this->db->fetchAssoc($result);
            return $row[itemName];
        } catch (Exception $ex) {
            $this->log->put("getMoonName " . $id, "err " . $ex->getMessage());
        }
    }

    private function getTypeName($id){
        try {
            $query = "SELECT `typeName` FROM `invTypes` WHERE `typeID`='$id' LIMIT 1";
            $result = $this->db->query($query);
            $row = $this->db->fetchAssoc($result);
            return $row[typeName];
        } catch (Exception $ex) {
            $this->log->put("getTypeName " . $id, "err " . $ex->getMessage());
        }
    }

    private function getLocations(){
        try {
            foreach($this->poslist as $pos) $ids[] = $pos[posID];
            $apiOrgManagement = new APIOrgManagement();
            $tmparr = $apiOrgManagement->getLocations($this->keyInfo[keyID], $this->keyInfo[vCode], $ids);
            for($i=0; $i<count($this->poslist); $i++){
                foreach($tmparr as $apipos){
                    if($apipos[id] == $this->poslist[$i][posID]){
                        $this->poslist[$i][x] = $apipos[x];
                        $this->poslist[$i][y] = $apipos[y];
                        $this->poslist[$i][z] = $apipos[z];
                        $this->poslist[$i][name] = $apipos[name];
                        break;
                    }
                }
            }
        } catch (Exception $ex) {
            $this->log->put("getLocations", "err " . $ex->getMessage());
        }
    }

    public function updateStarbaseList(){
        if($this->getStarbaseList()){
            $this->checkStarbaseAlive();
            foreach($this->poslist as $pos){
                if($pos[changed]){
                    try {
                        $query = "SELECT `posID` FROM `posList` WHERE `posID`='{$pos[posID]}' LIMIT 1";
                        $result = $this->db->query($query);
                        $moonName = $this->getMoonName($pos[moonID]);
                        $typeName = $this->getTypeName($pos[typeID]);
                        if($this->db->hasRows($result)){
                            $query = "UPDATE `posList` SET `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}', `locationID` = '{$pos[locationID]}', `moonID` = '{$pos[moonID]}',
                             `moonName` = '$moonName', `x` = '{$pos[x]}', `y` = '{$pos[y]}', `z` = '{$pos[z]}', `name` = '{$pos[name]}' WHERE `posID`='{$pos[posID]}'";
                            $result = $this->db->query($query, "utf8");
                            $this->log->put("updateStarbaseList " . $pos[posID], "ok update");
                        } else{
                            $query = "INSERT INTO `posList` SET `posID` = '{$pos[posID]}', `typeID` = '{$pos[typeID]}', `locationID` = '{$pos[locationID]}', `moonID` = '{$pos[moonID]}', `state` = '{$pos[state]}',
                             `stateTimestamp` = '{$pos[stateTimestamp]}', `moonName` = '$moonName', `typeName` = '$typeName', `corporationID` = '{$this->keyInfo[corporationID]}',`allianceID` = '{$this->keyInfo[allianceID]}',
                             `x` = '{$pos[x]}', `y` = '{$pos[y]}', `z` = '{$pos[z]}', `name` = '{$pos[name]}'";
                            $result = $this->db->query($query, "utf8");
                            $this->log->put("updateStarbaseList " . $pos[posID], "ok insert");
                        }
                    } catch (Exception $ex) {
                        $this->log->put("updateStarbaseList " . $pos[posID], "err " . $ex->getMessage());
                    }
                }
            }
        }
        return $this->log->get();
    }

    private function getStarbaseDetail($id, $type, $location){
        $pheal = new Pheal($this->keyInfo[keyID], $this->keyInfo[vCode], "corp");
        try{
            $response = $pheal->Starbasedetail(array("itemID" => $id));
            $pos[state] = $response->state;
            if($pos[state] == 3) $pos[stateTimestamp] = $response->stateTimestamp;
            if($pos[state] == 4){
                foreach ($response->fuel as $fuel) {
                    if($fuel->typeID == 16275){ // Strontium Clathrates
                        $pos[stront] = $fuel->quantity;
                        $pos[rfTime] = $this->calcFuelTime($type, $location, 16275, $fuel->quantity);
                    } elseif($fuel->typeID == 4051 || $fuel->typeID == 4247 || $fuel->typeID == 4312 || $fuel->typeID == 4246){ // Fuel Blocks
                        $pos[fuel] = $fuel->quantity;
                        $pos[fuelph] = $this->calcFuelTime($type, $location, $fuel->typeID, $fuel->quantity);
                        $pos[time] = floor($pos[fuel] / $pos[fuelph]);
                    }
                }
            }
            return $pos;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getStarbaseDetail", "err " . $e->getMessage());
        }
    }

    private function calcFuelTime($controlTowerTypeID, $systemID, $resourceTypeID, $resourceQuantity){
        try{
            $query = "SELECT `quantity` FROM `invControlTowerResources` WHERE `controlTowerTypeID` = '$controlTowerTypeID' AND `resourceTypeID` = '$resourceTypeID'";
            $result = $this->db->query($query);
            $quantity = $this->db->getMysqlResult($result, 0);
            $div = ($this->keyInfo[allianceID] != $this->getSolarSystemOwner($systemID)) ? $quantity : $quantity*0.75;
            $time = ($div == 0) ? 0 : ($resourceQuantity / $div);
            return floor($time);
        } catch (Exception $ex) {
            $this->log->put("calcFuelTime" . $resourceTypeID, "err " . $ex->getMessage());
        }
    }

    private function getSolarSystemOwner($id){
        $pheal = new Pheal(NULL, NULL, "map");
        try{
            $response = $pheal->Sovereignty();
            foreach ($response->solarSystems as $system) {
                if($system->solarSystemID == $id) return $system->allianceID;
            }
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("checkSov", "err " . $e->getMessage());
        }
    }

    public function updateStarbaseDetail(){
        $fulllog = new logging();
        $dbposarr = $this->getPosList();
        $fulllog->merge($this->log->get(true));
        if($dbposarr != NULL){
            foreach ($dbposarr as $dbpos) {
                $pos = $this->getStarbaseDetail($dbpos[posID], $dbpos[typeID], $dbpos[locationID]);
                try{
                    if($pos[state] == 3){
                        if($dbpos[state] != $pos[state]){
                            $query = "UPDATE `posList` SET `state` = '{$pos[state]}', `stateTimestamp` = '{$pos[stateTimestamp]}', `stront` = '0', `rfTime` = '0' WHERE `posID`='{$dbpos[posID]}'";
                            $result = $this->db->query($query);
                        }
                    } elseif($pos[state] == 4){
                        $query = "UPDATE `posList` SET `state` = '{$pos[state]}', `fuel` = '{$pos[fuel]}', `stront` = '{$pos[stront]}', `fuelph` = '{$pos[fuelph]}',`time` = '{$pos[time]}',
                         `rfTime` = '{$pos[rfTime]}' WHERE `posID`='{$dbpos[posID]}'";
                        $result = $this->db->query($query);
                    } else{
                        if($dbpos[state] != $pos[state]){
                            $query = "UPDATE `posList` SET `state` = '{$pos[state]}' WHERE `posID`='{$dbpos[posID]}'";
                            $result = $this->db->query($query);
                        }
                    }
                } catch (Exception $ex) {
                    $this->log->put("updateStarbaseDetail", "err " . $ex->getMessage());
                }
                $fulllog->merge($this->log->get(true), $dbpos[posID]);
            }
        }
        return $fulllog->get();
    }

}

?>
