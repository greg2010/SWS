<?php

use Pheal\Pheal;
use Pheal\Core\Config;

class assetList {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $silolist = array();

	public function __construct($keyInfo = array()){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyInfo = $keyInfo;
        Config::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        Config::getInstance()->access = new \Pheal\Access\StaticCheck();
    }

    private function getSiloList(){
        $pheal = new Pheal($this->keyInfo[keyID], $this->keyInfo[vCode], "corp");
        try{
            $response = $pheal->AssetList();
            foreach ($response->assets as $assets) {
                if($assets->typeID == 14343 && $assets->flag == 0 && $assets->singleton == 1 && $assets->contents[0]->typeID != 0){
                    $this->silolist[] = array(
                        'siloID' => $assets->itemID,
                        'locationID' => $assets->locationID,
                        'moonmatType' => $assets->contents[0]->typeID,
                        'moonmatQuantity' => $assets->contents[0]->quantity
                    );
                }
            }
            return ($this->silolist != NULL) ? true : false;
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

    private function getFirstPOSinSystem($location){
        try {
            $query = "SELECT `posID` FROM `posList` WHERE `locationID`='$location' AND `corporationID` = '{$this->keyInfo[corporationID]}' LIMIT 1";
            $result = $this->db->query($query); 
            return $this->db->getMysqlResult($result, 0);
        } catch (Exception $ex) {
            $this->log->put("getFirstPOSinSystem", "err " . $ex->getMessage());
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
                    if($this->db->hasRows($result)){
                        if(isset($moonMatInfo)){
                            $query = "UPDATE `siloList` SET `locationID` = '{$silo[locationID]}', `typeID` = '{$silo[moonmatType]}', `quantity` = '{$silo[moonmatQuantity]}', `mmname` = '{$moonMatInfo[typeName]}',
                             `mmvolume` = '{$moonMatInfo[volume]}', `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}' WHERE `siloID`='{$silo[siloID]}'";
                        } else{
                            $query = "UPDATE `siloList` SET `locationID` = '{$silo[locationID]}', `quantity` = '{$silo[moonmatQuantity]}',
                             `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}' WHERE `siloID`='{$silo[siloID]}'";
                        }
                        $result = $this->db->query($query);
                    } else{
                        $posID = $this->getFirstPOSinSystem($silo[locationID]);
                        $query = "INSERT INTO `siloList` SET `locationID` = '{$silo[locationID]}', `siloID`='{$silo[siloID]}', `typeID` = '{$silo[moonmatType]}', `quantity` = '{$silo[moonmatQuantity]}',
                         `mmname` = '{$moonMatInfo[typeName]}', `mmvolume` = '{$moonMatInfo[volume]}', `posID` = '$posID', `corporationID` = '{$this->keyInfo[corporationID]}', `allianceID` = '{$this->keyInfo[allianceID]}'";
                        $result = $this->db->query($query);
                        $this->log->put("updateSiloList " . $silo[posID], "ok insert");
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
