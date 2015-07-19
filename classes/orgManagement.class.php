<?php

class orgManagement {
    
    public function __construct() {
        $this->db = db::getInstance();
    }

    public function getCorporationTicker($id){
        $query = "SELECT `ticker` FROM `corporationList` WHERE `id` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    public function getCorporationName($id){
        $query = "SELECT `name` FROM `corporationList` WHERE `id` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    public function getCorporationID($name){
        $query = "SELECT `id` FROM `corporationList` WHERE `name` = '". $this->db->real_escape_string($name) . "'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }
    
    public function getAllianceTicker($id){
        $query = "SELECT `ticker` FROM `allianceList` WHERE `id` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    public function getAllianceName($id){
        $query = "SELECT `name` FROM `allianceList` WHERE `id` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    public function getAllianceID($name){
        $query = "SELECT `id` FROM `allianceList` WHERE `name` = '". $this->db->real_escape_string($name) . "'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    public function recordCorporationInfo($id, $name, $ticker){
        $query = "INSERT INTO `corporationList` SET `id` = '$id', `name` = '". $this->db->real_escape_string($name) . "', `ticker` = '$ticker'";
        $result = $this->db->query($query);
    }
    
    public function recordAllianceInfo($id, $name, $ticker){
        $query = "INSERT INTO `allianceList` SET `id` = '$id', `name` = '". $this->db->real_escape_string($name) . "', `ticker` = '$ticker'";
        $result = $this->db->query($query);
    }
}

?>
