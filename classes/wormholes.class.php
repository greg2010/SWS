<?php

class wormholes{
    
    private $db;
    private $user;

    public function __construct($user){
        $this->db = db::getInstance();
        $this->user = $user;
    }

    private function getUserName($id){
        $query = "SELECT `login` FROM `users` WHERE `id` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    private function getType($name){
        $query = "SELECT `dgmTypeAttributes`.`attributeID`, `dgmTypeAttributes`.`valueInt`, `dgmTypeAttributes`.`valueFloat` FROM `dgmTypeAttributes` INNER JOIN `invTypes` ON `dgmTypeAttributes`.`typeID` = `invTypes`.`typeID` WHERE `invTypes`.`typeName` = 'Wormhole $name'";
        $result = $this->db->query($query);
        $tmparr = $this->db->fetchAssoc($result);
        $type = array();
        $type["Name"] = $name;
        foreach ($tmparr as $tmp){
            if($tmp[attributeID] == "1381"){
                switch($tmp[valueInt]){
                    case 1: $type["Leads_To"] = "Class 1"; break;
                    case 2: $type["Leads_To"] = "Class 2"; break;
                    case 3: $type["Leads_To"] = "Class 3"; break;
                    case 4: $type["Leads_To"] = "Class 4"; break;
                    case 5: $type["Leads_To"] = "Class 5"; break;
                    case 6: $type["Leads_To"] = "Class 6"; break;
                    case 7: $type["Leads_To"] = "High-Sec"; break;
                    case 8: $type["Leads_To"] = "Low-Sec"; break;
                    case 9: $type["Leads_To"] = "Null-Sec"; break;
                    case 12: $type["Leads_To"] = "Thera"; break;
                    case 13: $type["Leads_To"] = "Shattered Wormhole Systems"; break;
                }
            }
            if($tmp[attributeID] == "1382"){
                $type["Life"] = $tmp[valueInt]/60;
            }
            if($tmp[attributeID] == "1383"){
                $type["Max_Mass"] = ($tmp[valueInt]) ? $tmp[valueInt] : $tmp[valueFloat];
            }
            /*if($tmp[attributeID] == "1384"){
                $type["Mass Regeneration"] = ($tmp[valueInt]) ? $tmp[valueInt] : $tmp[valueFloat];
            }*/
            if($tmp[attributeID] == "1385"){
                $type["Max_Jumpable"] = ($tmp[valueInt]) ? $tmp[valueInt] : $tmp[valueFloat];
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
        $query = "SELECT `class`, `static1`, `static2` FROM `mapWHConstellations` WHERE `id`='{$tmparr[constellationID]}'";
        $result = $this->db->query($query);
        if($this->db->hasRows($result)){
            $tmparr2 = $this->db->fetchAssoc($result);
            $system["Wormhole_Class"] = $tmparr2["class"];
            if($tmparr2["static1"]) $system["Static1"] = $this->getType($tmparr2["static1"]);
            if($tmparr2["static2"]) $system["Static2"] = $this->getType($tmparr2["static2"]);
            $query = "SELECT `invTypes`.`typeName` FROM `mapDenormalize` LEFT JOIN `invTypes` ON `mapDenormalize`.`typeid` = `invTypes`.`typeID` WHERE `mapDenormalize`.`solarSystemID` = '$id' AND `mapDenormalize`.`groupID` = '995'";
            $result = $this->db->query($query);
            if($this->db->hasRows($result)) $system["System_Effect"] = $this->db->getMysqlResult($result);
        } else{
            $query = "SELECT `itemName` FROM `mapDenormalize` WHERE `itemID` = '{$tmparr[regionID]}' OR `itemID` = '{$tmparr[constellationID]}'";
            $result = $this->db->query($query);
            $tmparr2 = $this->db->fetchAssoc($result);
            $system["Region"] = $tmparr2[0][itemName];
            $system["Constellation"] = $tmparr2[1][itemName];
            $system["Security_Level"] = round($tmparr[security], 1);
        }
        return $system;
    }

    private function getAge($created, $modified, $life, $status){
        $cdif = round(((mktime() - strtotime($created))/60)/60);
        if($status == 0){
            if($modified){
                $mdif = round(((mktime() - strtotime($modified))/60)/60);
                if((4 - $cdif) > (4 - $mdif)) return intval(4 - $mdif);
            }
            return intval(4 - $cdif);
        } else return intval($life - $cdif);
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
            $type = yaml_parse($wh[type]);
            $age = $this->getAge($wh[created], $wh[modified], $type[Life], $wh[life]);
            if($age < 0){
                //$query = "DELETE FROM `wormholes` WHERE `id` = '{$wh[id]}'";
                //$result = $this->db->query($query);
            } else{
                if($age < 4 && $wh[life] == 1){
                    $query = "UPDATE `wormholes` SET `life` = '0' WHERE `id` = '{$wh[id]}'";
                    $result = $this->db->query($query);
                    $life = 0;
                } else $life = $wh[life];
                $system1 = yaml_parse($wh[system1]);
                $system2 = yaml_parse($wh[system2]);
                $userName = $this->getUserName($wh[user]);
                if($wh[mass] == 0) $mass = "Critical";
                elseif($wh[mass] == 1) $mass = "Destab";
                else $mass = "Stable";
                $wormholes[] = array(
                    "wh_id" => $wh[id],
                    "ID" => $wh[signature],
                    "Scanned_by" => $userName,
                    "Type" => $type,
                    "Age" => $age,
                    "Created" => $wh[created],
                    "Last_Modified" => $wh[modified],
                    "System" => $system1,
                    "Leads_To" => $system2,
                    "Life" => ($life == 0) ? "Critical" : "Stable",
                    "Mass" => $mass
                );
            }
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
        $yaml_type = yaml_emit($this->getType($Type));
        $intSystem = $this->getSystemID($System);
        if($intSystem == 0) throw new Exception("Invalid system name!", 33);
        $yaml_system = yaml_emit($this->getSystemInfo($intSystem));
        $intLeads = $this->getSystemID($Leads);
        if($intLeads == 0) throw new Exception("Invalid target system name!", 34);
        $yaml_leads = yaml_emit($this->getSystemInfo($intLeads));
        $date = date("Y-m-d H:i:s");
        $intLife = ($Life == "Critical") ? 0 : 1;
        if($Mass == "Critical") $intMass = 0;
        elseif($Mass == "Destab") $intMass = 1;
        else $intMass = 2;
        if($wh_id == NULL){
            $query = "INSERT INTO `wormholes` SET `user` = '{$this->user}', `signature` = '$ID', `type` = '$yaml_type', `created` = '$date', `system1` = '$yaml_system', `system2` = '$yaml_leads', `life` = '$intLife', `mass` = '$intMass'";
        } else{
            $query = "UPDATE `wormholes` SET `signature` = '$ID', `type` = '$yaml_type', `modified` = '$date', `system1` = '$yaml_system', `system2` = '$yaml_leads', `life` = '$intLife', `mass` = '$intMass' WHERE `id` = '$wh_id'";
        }
        $result = $this->db->query($query);
    }
}

?>
