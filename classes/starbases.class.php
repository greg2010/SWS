<?php

use Pheal\Pheal;
use Pheal\Core\Config;

class starbases {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $poslist = array();

	public function __construct($keyInfo = array()){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyInfo = $keyInfo;
        Config::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        Config::getInstance()->access = new \Pheal\Access\StaticCheck();
    }

    private function getStarbaseList(){
        $pheal = new Pheal($this->keyInfo[keyID], $this->keyInfo[vCode], "corp");
        try{
            $response = $pheal->StarbaseList();
            foreach($response->starbases as $row){
                if($this->checkStarbaseNotChanged($row) == false){
                    $this->poslist[] = array(
                        'posID' => $row[itemID],
                        'typeID' => $row[typeID],
                        'locationID' => $row[locationID],
                        'moonID' => $row[moonID],
                        'state' => $row[state],
                        'stateTimestamp' => $row[stateTimestamp]
                    );
                }
            }
            return ($this->poslist != NULL) ? true : false;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getStarbaseList", "err " . $e->getMessage());
            return false;
        }
    }

    private function getPosIds(){
        try {
            $query = "SELECT `posID`, `typeID`, `locationID` FROM `posList` WHERE `corporationID` = '{$this->keyInfo[corporationID]}'";
            $result = $this->db->query($query);
            return $this->db->fetchRow($result);
        } catch (Exception $ex) {
            $this->log->put("getPosIds", "err " . $ex->getMessage());
        }
    }

    private function checkStarbaseAlive(){
        $dbposarr = $this->getPosIds();
        foreach ($dbposarr as $dbposlist) {
            try {
                for($j=0; $j<count($this->poslist); $j++){
                    if($this->poslist[$j][posID] == $dbposlist[0]) break;
                    if($j == (count($this->poslist)-1)){
                        $query = "DELETE FROM `posList` WHERE `posID`='{$dbposlist[0]}'";
                        $res2 = $this->db->query($query);
                        //if($dbposlist[0] == "1") var_dump($dbposarr);
                        //echo "------> " . $dbposlist[0] . "\n";
                        //var_dump($this->poslist);
                        $this->log->put("checkStarbaseAlive " . $dbposlist[0], "ok delete");
                    }
                }
            } catch (Exception $ex) {
                $this->log->put("checkStarbaseAlive " . $dbposlist[0], "err " . $ex->getMessage());
            }
        }
    }

    private function checkStarbaseNotChanged($pos = array()){
        try {
            $query = "SELECT `locationID`, `moonID`, `state` FROM `posList` WHERE `posID`='{$pos[itemID]}'";
            $result = $this->db->query($query);
            $dbpos = $this->db->fetchRow($result);
            //if($pos[locationID] != $dbpos[0] || $pos[moonID] != $dbpos[1] || $pos[state] != $dbpos[2] || $pos[stateTimestamp] != $dbpos[3]) var_dump($dbpos);
            return ($pos[locationID] == $dbpos[0] && $pos[moonID] == $dbpos[1] && $pos[state] == $dbpos[2]) ? true : false;
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

    public function updateStarbaseList(){ // stateTimestamp и state не нужны, либо только при вставке нового поса
        $notempty = $this->getStarbaseList();
        $this->checkStarbaseAlive();
        if($notempty){
            foreach($this->poslist as $pos){
                try {
                    $query = "SELECT `posID` FROM `posList` WHERE `posID`='{$pos[posID]}' LIMIT 1";
                    $result = $this->db->query($query);
                    $moonName = $this->getMoonName($pos[moonID]);
                    $typeName = $this->getTypeName($pos[typeID]);
                    if($this->db->hasRows($result)){
                        $query = "UPDATE `posList` SET  `locationID` = '{$pos[locationID]}', `moonID` = '{$pos[moonID]}', `state` = '{$pos[state]}', `stateTimestamp` = '{$pos[stateTimestamp]}',
                         `moonName` = '$moonName', `typeName` = '$typeName', `corporationID` = '{$this->keyInfo[corporationID]}', `corporationName`= '{$this->keyInfo[corporationName]}',
                         `allianceID` = '{$this->keyInfo[allianceID]}', `allianceName`= '{$this->keyInfo[allianceName]}' WHERE `posID`='{$pos[posID]}'";
                        $result = $this->db->query($query);
                        $this->log->put("updateStarbaseList " . $pos[posID], "ok update");
                    } else{
                        $query = "INSERT INTO `posList` SET `posID`= '{$pos[posID]}', `typeID` = '{$pos[typeID]}', `locationID` = '{$pos[locationID]}', `moonID` = '{$pos[moonID]}', `state` = '{$pos[state]}',
                         `stateTimestamp` = '{$pos[stateTimestamp]}', `moonName` = '$moonName', `typeName` = '$typeName', `corporationID` = '{$this->keyInfo[corporationID]}',
                         `corporationName`= '{$this->keyInfo[corporationName]}',`allianceID` = '{$this->keyInfo[allianceID]}', `allianceName`= '{$this->keyInfo[allianceName]}'";
                        $result = $this->db->query($query);
                        $this->log->put("updateStarbaseList " . $pos[posID], "ok insert");
                    } 
                } catch (Exception $ex) {
                    $this->log->put("updateStarbaseList " . $pos[posID], "err " . $ex->getMessage());
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
            $pos[stateTimestamp] = $response->stateTimestamp;
            foreach ($response->fuel as $fuel) {
                if($fuel->typeID == 16275){
                    $pos[stront] = $fuel->quantity;
                    $pos[rfTime] = $this->calcFuelTime($type, $location, 16275, $fuel->quantity);
                } else{
                    $pos[fuel] = $fuel->quantity;
                    $pos[fuelph] = $this->calcFuelTime($type, $location, $fuel->typeID, $fuel->quantity);
                    $pos[time] = floor($pos[fuel] / $pos[fuelph]);
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
        $dbposarr = $this->getPosIds();
        $fulllog->merge($this->log->get(true));
        foreach ($dbposarr as $dbposlist) {
            if(count($dbposlist) == 3){
                $pos = $this->getStarbaseDetail($dbposlist[0], $dbposlist[1], $dbposlist[2]);
                try{
                    if($pos[state] == 3) $pos[stront] = 0;
                    $query = "UPDATE `posList` SET `state` = '{$pos[state]}', `stateTimestamp` = '{$pos[stateTimestamp]}', `fuel` = '{$pos[fuel]}', `stront` = '{$pos[stront]}', `fuelph` = '{$pos[fuelph]}',
                     `time` = '{$pos[time]}', `rfTime` = '{$pos[rfTime]}' WHERE `posID`='{$dbposlist[0]}'";
                    $result = $this->db->query($query);
                } catch (Exception $ex) {
                    $this->log->put("updateStarbaseDetail", "err " . $ex->getMessage());
                }
                $fulllog->merge($this->log->get(true), $dbposlist[0]);
            }
        }
        return $fulllog->get();
    }

}

?>
