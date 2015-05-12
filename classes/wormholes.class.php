<?php

class wormholes{
    
    private $db;
    private $user;

    public function __construct($user){
        $this->db = db::getInstance();
        $this->user = $this->getUserName($user);
    }

    private function getUserName($id){
        $query = "SELECT `login` FROM `users` WHERE `id`='$id'";
        $result = $this->db->query($query);
        return $this->db->hasRows($result);
    }

    private function getType($name){
        $query = "SELECT `dgmTypeAttributes`.`attributeID`, `dgmTypeAttributes`.`valueInt`, `dgmTypeAttributes`.`valueFloat` FROM `dgmTypeAttributes` INNER JOIN `invTypes` ON `dgmTypeAttributes`.`typeID` = `invTypes`.`typeID` WHERE `invTypes`.`typeName` = 'Wormhole $name'";
        $result = $this->db->query($query);
        $tmparr = $this->db->fetchAssoc($result);
        $type = array();
        foreach ($tmparr as $tmp){
            if($tmp[attributeID] == "1381"){
                switch($tmp[valueInt]){
                    case 1: $type["Leads To"] = "Class 1"; break;
                    case 2: $type["Leads To"] = "Class 2"; break;
                    case 3: $type["Leads To"] = "Class 3"; break;
                    case 4: $type["Leads To"] = "Class 4"; break;
                    case 5: $type["Leads To"] = "Class 5"; break;
                    case 6: $type["Leads To"] = "Class 6"; break;
                    case 7: $type["Leads To"] = "High-Sec"; break;
                    case 8: $type["Leads To"] = "Low-Sec"; break;
                    case 9: $type["Leads To"] = "Null-Sec"; break;
                }
            }
            if($tmp[attributeID] == "1382"){
                $type["Life"] = $tmp[valueInt]/60;
            }
            if($tmp[attributeID] == "1383"){
                $type["Max Mass"] = ($tmp[valueInt]) ? $tmp[valueInt] : $tmp[valueFloat];
            }
            /*if($tmp[attributeID] == "1384"){
                $type["Mass Regeneration"] = ($tmp[valueInt]) ? $tmp[valueInt] : $tmp[valueFloat];
            }*/
            if($tmp[attributeID] == "1385"){
                $type["Max Jumpable"] = ($tmp[valueInt]) ? $tmp[valueInt] : $tmp[valueFloat];
            }
            /*if($tmp[attributeID] == "1457"){
                $type["Target Distribution ID"] = $tmp[valueInt];
            }*/
        }
        return $type;
    }

    private function getSystemInfo($id){
        $query = "SELECT `regionID`, `constellationID`, `solarSystemName`, `security` FROM `mapSolarSystems` WHERE `solarSystemID`='$id'";
        $result = $this->db->query($query);
        $tmparr = $this->db->fetchAssoc($result);
        $system = array();
        $system["Name"] = $tmparr["solarSystemName"];
        $query = "SELECT `class`, `static1`, `static1` FROM `mapWHConstellations` WHERE `id`='{$tmparr[constellationID]}'";
        $result = $this->db->query($query);
        if($this->db->hasRows($result)){
            $tmparr2 = $this->db->fetchAssoc($result);
            $system["Wormhole Class"] = $tmparr2["class"];
            $system["Static1"] = $this->getType($tmparr2["static1"]);
            if($tmparr2["static2"]) $system["Static2"] = $this->getType($tmparr2["static2"]);
            $query = "SELECT `invTypes`.`typeName` FROM `mapDenormalize` LEFT JOIN `invTypes` ON `mapDenormalize`.`typeid` = `invTypes`.`typeID` WHERE `mapDenormalize`.`solarSystemID` = '$id' AND `mapDenormalize`.`groupID` = '995'";
            $result = $this->db->query($query);
            if($this->db->hasRows($result)) $type["System Effect"] = $this->db->getMysqlResult($result);
        } else{
            $query = "SELECT `itemName` FROM `mapDenormalize` WHERE `itemID` = '{$tmparr[regionID]}' OR `itemID` = '{$tmparr[constellationID]}'";
            $result = $this->db->query($query);
            $tmparr2 = $this->db->fetchAssoc($result);
            $system["Region"] = $tmparr2[0][itemName];
            $system["Constellation"] = $tmparr2[1][itemName];
            $system["Security Level"] = round($tmparr[security], 1);
        }
        return $system;
    }

    private function getAge($created, $life){
        $age = 24;
        return $age;
    }

    private function getSystemID($name){
        $query = "SELECT `solarSystemID` FROM `mapSolarSystems` WHERE `solarSystemName`='$name' LIMIT 1";
        $result = $this->db->query($query);
        return ($this->db->hasRows($result)) ? $this->db->getMysqlResult($result) : 0;
    }

    private function checkWH($name){
        $query = "SELECT `typeName` FROM `invTypes` WHERE `typeName` REGEXP '^Wormhole [A-Z]{1}[0-9]{3}$'";
        $result = $this->db->query($query);
        $tmparr = $this->db->fetchRow($result);
        foreach ($tmparr as $tmp) if($tmp[0] == "Wormhole $name") return true;
        return false;
    }

    public function getWHList(){
        $query = "SELECT * FROM `wormholes`";
        $result = $this->db->query($query);
        $assocArray = ($this->db->countRows($result) == 1) ? array($this->db->fetchAssoc($result)) : $this->db->fetchAssoc($result);
        foreach ($assocArray as $wh){
            $type = $this->getType($wh[type]);
            $age = $this->getAge($wh[created], $wh[life]);
            $system1 = $this->getSystemInfo($wh[system1]);
            $system2 = $this->getSystemInfo($wh[system2]);
            if($wh[mass] == 0) $mass = "Critical";
            elseif($wh[mass] == 1) $mass = "Destab";
            else $mass = "Stable";
            $wormholes[] = array(
                "wh_id" => $wh[id],
                "ID" => $wh[signature],
                "Scanned by" => $wh[user],
                "Type" => $type,
                "Age" => $age,
                "Created" => $wh[created],
                "Last Modified" => $wh[modified],
                "System" => $system1,
                "Leads To" => $system2,
                "Life" => ($wh[life] == 0) ? "Critical" : "Stable",
                "Mass" => $mass
            );
        }
        return $wormholes;
    }

    public function deleteWH($wh_id){
        $query = "DELETE FROM `wormholes` WHERE `id` = '$wh_id'";
        $result = $this->db->query($query);
    }

    public function updateWH($wh_id = NULL, $ID, $Type, $System, $Leads, $Life, $Mass){
        if(!preg_match('/^\d{3}$/', $ID)) throw new Exception("Invalid signature id!", 31);
        if(!$this->checkWH($Type)) throw new Exception("Invalid wormhole type!", 32);
        $intSystem = $this->getSystemID($System);
        if($intSystem == 0) throw new Exception("Invalid system name!", 33);
        $intLeads = $this->getSystemID($Leads);
        if($intLeads == 0) throw new Exception("Invalid target system name!", 34);
        $date = date("Y-m-d H:i:s");
        $intLife = ($Life == "Critical") ? 0 : 1;
        if($Mass == "Critical") $intMass = 0;
        elseif($Mass == "Destab") $intMass = 1;
        else $intMass = 2;
        if($wh_id == NULL){
            $query = "INSERT INTO `wormholes` SET `user` = '{$this->user}', `signature` = '$ID', `type` = '$Type', `created` = '$date', `system1` = '$intSystem', `system2` = '$intLeads', `life` = '$intLife', `mass` = '$intMass'";
        } else{
            $query = "UPDATE `wormholes` SET `user` = '{$this->user}', `signature` = '$ID', `type` = '$Type', `modified` = '$date', `system1` = '$intSystem', `system2` = '$intLeads', `life` = '$intLife', `mass` = '$intMass' WHERE `id` = '$wh_id'";
        }
        $result = $this->db->query($query);
    }
}

?>
