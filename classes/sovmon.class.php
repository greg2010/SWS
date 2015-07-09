<?php

class sovmon {

    private $db;

    public function __construct() {
        $this->db = db::getInstance();
    }

    public function getSovInfo() {
        $query = "SELECT * FROM `sovmon`";
        $dbInfo = $this->db->fetchAssoc($this->db->query($query));
        return $dbInfo;
    }

    public function updateSovInfo() {
        $json = json_decode(file_get_contents("https://public-crest-duality.testeveonline.com/sovereignty/structures/"), true); //To be changed once CCP deploys fozziesov to tranquility

        $this->db->query("TRUNCATE `sovmon`");

        foreach ($json[items] as $row) {
            $query = "INSERT INTO `sovmon` SET `allianceID` = '{$row[alliance][id]}', `allianceName` = '" . $this->db->real_escape_string($row[alliance][name]) . "', `structureID` = '{$row[structureID]}', `typeID` = '{$row[type][id]}', `typeName` = '{$row[type][name]}', `vulnerabilityOccupancyLevel` = '{$row[vulnerabilityOccupancyLevel]}', `vulnerableStartTime` = '{$row[vulnerableStartTime]}', `vulnerableEndTime` = '{$row[vulnerableEndTime]}', `systemID` = '{$row[solarSystem][id]}', `systemName` = '{$row[solarSystem][name]}'";
            $this->db->query($query);
        }
    }
}