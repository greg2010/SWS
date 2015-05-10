<?php

class wormholes{
    
    private $db;
    private $user;

    public function __construct($user){
        $this->db = db::getInstance();
        $this->user = $this->getUserName($user);
    }

    private function getUserName($id){
        return "fofofo";
    }

    private function getType($name){
        $type = array(
            "Name" => $name,
            "Life" => "24",
            "Leads To" => "high",
            "Max Mass" => "22222",
            "Max Jumpable" => "1111"
        );
        return $type;
    }

    private function getSystemInfo($id){
        //проверка на вх
        //если вх
        $system = array(
            "Name" => "J123",
            "Wormhole Class" => "c7",
            "Static" => "u210",
            "System Effect" => "none"
        );
        //если не вх
        $system = array(
            "Name" => "ABC",
            "Region" => "DEF",
            "Constellation" => "GHI",
            "Security Level" => "0"
        );
        return $system;
    }

    private function getAge($created, $life){
        $age = 24;
        return $age;
    }

    private function getSystemID($name){
        return 0;
        //и проверку на реальность системы
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
        //проверка что $ID состоит из цифр иначе возвращает некий код ошибки
        //проверка на реальность $Type иначе возвращает некий код ошибки
        $date = date("Y-m-d H:i:s");
        $intSystem = $this->getSystemID($System);
        $intLeads = $this->getSystemID($Leads);
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
