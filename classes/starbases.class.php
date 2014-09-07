<?php

use Pheal\Pheal;

class starbases {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $poslist;

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
                if($this->checkStarbaseNotChanged($row)){
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
            if(gettype($result) == "string") throw new Exception($result);
            while($dbposlist = $this->db->fetchRow($result)){
                for($j=0; $j<count($this->poslist); $j++){
                    if($this->poslist[$j][posID] == $dbposlist[0]) break;
                    if($j == (count($this->poslist)-1)){
                        $query = "DELETE FROM `posList` WHERE `posID`='{$dbposlist[0]}' AND `corporationID` = '{$this->keyInfo[corporationID]}'";
                        $res2 = $this->db->query($query);
                        if(gettype($res2) == "string"){
                            $this->log->put("checkStarbaseAlive " . $dbposlist[0], "err " . $res2);
                        } else{
                            throw new Exception("id: " . $dbposlist[0] . " " . $res2);
                            $this->log->put("checkStarbaseAlive " . $dbposlist[0], "ok delete");
                        } 
                    }
                }
            }
        } catch (Exception $ex) {
            $this->log->put("checkStarbaseAlive", "err " . $ex->getMessage());
        }
    }

    private function checkStarbaseNotChanged($pos = array()){
        try {
            $query = "SELECT `locationID`, `moonID`, `state`, `stateTimestamp` FROM `posList` WHERE `posID`='{$pos[itemID]}' AND `corporationID` = '{$this->keyInfo[corporationID]}'";
            $result = $this->db->query($query);
            if(gettype($result) == "string") throw new Exception($result);
            $dbpos = $this->db->fetchRow($result);
            return ($pos[locationID] == $dbpos[locationID] && $pos[moonID] == $dbpos[moonID] && $pos[state] == $dbpos[state] && $pos[stateTimestamp] == $dbpos[stateTimestamp]) ? false : true;
        } catch (Exception $ex) {
            $this->log->put("checkStarbaseNotChanged " . $pos[itemID], "err " . $ex->getMessage());
            return true;
        }
    }

    private function getMoonName($id){
        try {
            $query = "SELECT `itemName` FROM `mapDenormalize` WHERE `itemID`='$id' LIMIT 1";
            $result = $this->db->query($query);
            if(gettype($result) == "string") throw new Exception($result); 
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
            if(gettype($result) == "string") throw new Exception($result);
            $row = $this->db->fetchAssoc($result);
            return $row[typeName];
        } catch (Exception $ex) {
            $this->log->put("getTypeName " . $id, "err " . $ex->getMessage());
        }
    }

    public function updateStarbaseList(){
        if($this->getStarbaseList()){
            $this->checkStarbaseAlive();
            foreach($pos as $this->poslist){
                try {
                    $query = "SELECT `posID` FROM `posList` WHERE `posID`='{$pos[posID]}' LIMIT 1";
                    $result = $this->db->query($query);
                    if(gettype($result) == "string") throw new Exception($result);
                    $moonName = $this->getMoonName($pos[moonID]);
                    $typeName = $this->getTypeName($pos[typeID]);
                    if($this->db->hasRows){
                        $query = "UPDATE `posList` SET  `locationID` = '{$pos[locationID]}', `moonID` = '{$pos[moonID]}', `state` = '{$pos[state]}', `stateTimestamp` = '{$pos[stateTimestamp]}',
                         `moonName` = '$moonName', `typeName` = '$typeName', `corporationID` = '{$this->keyInfo[corporationID]}', `corporationName`= '{$this->keyInfo[corporationName]}',
                         `allianceID` = '{$this->keyInfo[allianceID]}', `allianceName`= '{$this->keyInfo[allianceName]}' WHERE `posID`='{$pos[posID]}'";
                        $result = $this->db->query($query);
                        if(gettype($result) == "string") throw new Exception($result);
                        $this->log->put("updateStarbaseList " . $pos[posID], "ok update");
                    } else{
                        $query = "INSERT INTO `posList` SET `posID`= '{$pos[posID]}', `typeID` = '{$pos[typeID]}', `locationID` = '{$pos[locationID]}', `moonID` = '{$pos[moonID]}', `state` = '{$pos[state]}',
                         `stateTimestamp` = '{$pos[stateTimestamp]}', `moonName` = '$moonName', `typeName` = '$typeName', `corporationID` = '{$this->keyInfo[corporationID]}',
                         `corporationName`= '{$this->keyInfo[corporationName]}',`allianceID` = '{$this->keyInfo[allianceID]}', `allianceName`= '{$this->keyInfo[allianceName]}'";
                        $result = $this->db->query($query);
                        if(gettype($result) == "string") throw new Exception($result);
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
