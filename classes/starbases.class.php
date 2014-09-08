<?php

use Pheal\Pheal;

class starbases {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $poslist = array();

	public function __construct($keyInfo = array()){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyInfo = $keyInfo;
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

    private function checkStarbaseAlive(){
        try {
            $query = "SELECT `posID` FROM `posList` WHERE `corporationID` = '{$this->keyInfo[corporationID]}'";
            $result = $this->db->query($query);
            $dbposarr = $this->db->fetchRow($result);
            foreach ($dbposarr as $dbposlist) {
                for($j=0; $j<count($this->poslist); $j++){
                    if($this->poslist[$j][posID] == $dbposlist[0]) break;
                    if($j == (count($this->poslist)-1)){
                        $query = "DELETE FROM `posList` WHERE `posID`='{$dbposlist[0]}'";
                        $res2 = $this->db->query($query);
                        var_dump($dbposlist[0]);
                        $this->log->put("checkStarbaseAlive " . $dbposlist[0], "ok delete");
                    }
                }
            }
        } catch (Exception $ex) {
            $this->log->put("checkStarbaseAlive", "err " . $ex->getMessage());
        }
    }

    private function checkStarbaseNotChanged($pos = array()){
        try {
            $query = "SELECT `locationID`, `moonID`, `state`, `stateTimestamp` FROM `posList` WHERE `posID`='{$pos[itemID]}'";
            $result = $this->db->query($query);
            $dbpos = $this->db->fetchRow($result);
            //if($pos[locationID] != $dbpos[0] || $pos[moonID] != $dbpos[1] || $pos[state] != $dbpos[2] || $pos[stateTimestamp] != $dbpos[3]) var_dump($dbpos);
            return ($pos[locationID] == $dbpos[0] && $pos[moonID] == $dbpos[1] && $pos[state] == $dbpos[2] && $pos[stateTimestamp] == $dbpos[3]) ? true : false;
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

    public function updateStarbaseList(){
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

    public function updateStarbaseDetail(){
        return $this->log->get();
    }

}

?>
