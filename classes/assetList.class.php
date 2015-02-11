<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class assetList {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $silolist = array();

	public function __construct($keyInfo = array()){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyInfo = $keyInfo;
        //PhealConfig::getInstance()->cache = new \Pheal\Cache\PdoStorage("mysql:host=" . config::hostname . ";dbname=" . config::database, config::username, config::password, "phealng-cache");
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
    }

    private function getSiloList(){
        $pheal = new Pheal($this->keyInfo[keyID], $this->keyInfo[vCode], "corp");
        try{
            $response = $pheal->AssetList();
            foreach ($response->assets as $assets) {
                if($assets->typeID == 14343 && $assets->flag == 0 && $assets->singleton == 1 && $assets->contents[0]->typeID != 0 && $assets->locationID < 40000000){ // 60000000
                    $this->silolist[] = array(
                        'siloID' => $assets->itemID,
                        'locationID' => $assets->locationID,
                        'moonmatType' => $assets->contents[0]->typeID,
                        'moonmatQuantity' => $assets->contents[0]->quantity
                    );
                }
            }
            if($this->silolist != NULL){
                $this->getLocations();
                return true;
            } else return false;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getSiloList", "err " . $e->getMessage());
            return false;
        }
    }

    private function checkSiloAlive(){
        $ids = $this->getSiloIds();
        if($ids != NULL){
            foreach ($ids as $siloID) {
                try {
                    for($j=0; $j<count($this->silolist); $j++){
                        if($this->silolist[$j][siloID] == $siloID) break;
                        if($j == (count($this->silolist)-1)){
                            $query = "DELETE FROM `siloList` WHERE `siloID`='$siloID'";
                            $result = $this->db->query($query);
                            $this->log->put("checkSiloAlive " . $siloID, "ok delete");
                        }
                    }
                } catch (Exception $ex) {
                    $this->log->put("checkSiloAlive " . $siloID, "err " . $ex->getMessage());
                }
            }
        }
    }

    private function getSiloIds(){
        try {
            $query = "SELECT `siloID` FROM `siloList` WHERE `corporationID` = '{$this->keyInfo[corporationID]}'";
            $result = $this->db->query($query);
            $tmparr = $this->db->fetchAssoc($result);
            if(isset($tmparr)){
                foreach ($tmparr as $value) {
                    if(isset($value[siloID])) $tmpre[] = $value[siloID];
                }
                return $tmpre;
            }
        } catch (Exception $ex) {
            $this->log->put("getSiloIds", "err " . $ex->getMessage());
        }
    }

    private function getMoonMatInfo($id){
        try {
            $query = "SELECT `typeName`, `volume` FROM `invTypes` WHERE `typeID`='$id' LIMIT 1";
            $result = $this->db->query($query); 
            return $this->db->fetchAssoc($result);
        } catch (Exception $ex) {
            $this->log->put("getMoonMatInfo", "err " . $ex->getMessage());
        }
    }

    private function getPOSforSilo($location, $x, $y, $z){
        try {
            $delta = 400000;
            $xd = $x - $delta; $xu = $x + $delta; $yd = $y - $delta; $yu = $y + $delta; $zd = $z - $delta; $zu = $z + $delta;
            $query = "SELECT `posID` FROM `posList` WHERE `locationID`='$location' AND `corporationID` = '{$this->keyInfo[corporationID]}' AND `x`>'$xd' AND `x`<'$xu' AND `y`>'$yd' AND `y`<'$yu' AND `z`>'$zd' AND `z`<'$zu' LIMIT 1";
            $result = $this->db->query($query); 
            return $this->db->getMysqlResult($result, 0);
        } catch (Exception $ex) {
            $this->log->put("getFirstPOSinSystem", "err " . $ex->getMessage());
        }
    }

    private function getLocations(){
        try {
            foreach($this->silolist as $silo) $ids[] = $silo[siloID];
            $apiOrgManagement = new APIOrgManagement();
            $tmparr = $apiOrgManagement->getLocations($this->keyInfo[keyID], $this->keyInfo[vCode], $ids);
            for($i=0; $i<count($this->silolist); $i++){
                foreach($tmparr as $apisilo){
                    if($apisilo[id] == $this->silolist[$i][siloID]){
                        $this->silolist[$i][x] = $apisilo[x];
                        $this->silolist[$i][y] = $apisilo[y];
                        $this->silolist[$i][z] = $apisilo[z];
                        $this->silolist[$i][name] = $apisilo[name];
                        break;
                    }
                }
            }
        } catch (Exception $ex) {
            $this->log->put("getLocations", "err " . $ex->getMessage());
        }
    }

    public function updateSiloList(){
        if($this->getSiloList()){
            $this->checkSiloAlive();
            foreach($this->silolist as $silo){
                try {
                    $query = "SELECT `siloID`, `typeID` FROM `siloList` WHERE `siloID`='{$silo[siloID]}' LIMIT 1";
                    $result = $this->db->query($query);
                    unset($moonMatInfo);
                    if($this->db->getMysqlResult($result, 1) != $silo[moonmatType]){
                        $moonMatInfo = $this->getMoonMatInfo($silo[moonmatType]);
                    }
                    $posID = $this->getPOSforSilo($silo[locationID], $silo[x], $silo[y], $silo[z]);
                    if($this->db->hasRows($result)){
                        if(isset($moonMatInfo)){
                            $query = "UPDATE `siloList` SET `typeID` = '{$silo[moonmatType]}', `quantity` = '{$silo[moonmatQuantity]}', `mmname` = '{$moonMatInfo[typeName]}',
                             `mmvolume` = '{$moonMatInfo[volume]}', `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}',
                             `locationID` = '{$silo[locationID]}', `x` = '{$silo[x]}', `y` = '{$silo[y]}', `z` = '{$silo[z]}', `name` = '{$silo[name]}', `posID` = '$posID' WHERE `siloID`='{$silo[siloID]}'";
                        } else{
                            $query = "UPDATE `siloList` SET `quantity` = '{$silo[moonmatQuantity]}', `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}',
                             `locationID` = '{$silo[locationID]}', `x` = '{$silo[x]}', `y` = '{$silo[y]}', `z` = '{$silo[z]}', `name` = '{$silo[name]}', `posID` = '$posID' WHERE `siloID`='{$silo[siloID]}'";
                        }
                        $result = $this->db->query($query, "utf8");
                    } else{
                        $query = "INSERT INTO `siloList` SET `locationID` = '{$silo[locationID]}', `siloID`='{$silo[siloID]}', `typeID` = '{$silo[moonmatType]}', `quantity` = '{$silo[moonmatQuantity]}',
                         `mmname` = '{$moonMatInfo[typeName]}', `mmvolume` = '{$moonMatInfo[volume]}', `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}',
                         `x` = '{$silo[x]}', `y` = '{$silo[y]}', `z` = '{$silo[z]}', `name` = '{$silo[name]}', `posID` = '$posID'";
                        $result = $this->db->query($query, "utf8");
                        $this->log->put("updateSiloList " . $silo[siloID], "ok insert");
                    } 
                } catch (Exception $ex) {
                    $this->log->put("updateSiloList " . $silo[siloID], "err " . $ex->getMessage());
                }
            }
        } else{
            try {
                $list = $this->getSiloIds();
                if(isset($list)){
                    $query = "DELETE FROM `siloList` WHERE `corporationID`='{$this->keyInfo[corporationID]}'";
                    $result = $this->db->query($query);
                    $this->log->put("updateSiloList delete", "ok delete");
                }
            } catch (Exception $ex) {
                $this->log->put("updateSiloList delete", "err " . $ex->getMessage());
            }
        }
        return $this->log->get();
    }

}

?>
