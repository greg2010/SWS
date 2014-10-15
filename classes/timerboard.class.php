<?php
/*
interface Itimerboard {
    public function getAllTimers();
    public function setNewTimer($timer, $system, $planet, $moon, $rfType, $friendly);
    public function deleteTimer($tID);
}*/

class timerboard{ //} implements Itimerboard {
    
    private $db;
    private $permissions;
    private $apiUserManagement;

    public function __construct() {
        $this->db = db::getInstance();
        /*$id = $_SESSION[userObject]->getID();
        if (!$id) {
            throw new Exception("User isn't logged in!");
        }
        $this->permissions = new permissions($id);*/
        $this->apiUserManagement = new APIUserManagement();
    }
    /*
    private function checkRights($action) {
        switch ($action) {
            case 'add':
                $requiredPermissions = array();
                break;
            case 'delete':
                $requiredPermissions = array();
                break;
        }
        foreach ($requiredPermissions as $permission) {
            if (!$this->permissions->hasPermission($permission)) {
                throw new Exception("Not enough permissions!", 11);
            }
        }
    }*/
    
    public function getTimers($active = true){
        $date = date("Y-m-d H:i:s");
        $query = ($active) ? ("SELECT * FROM `timerboard` WHERE `timer` > '$date'") : ("SELECT * FROM `timerboard` WHERE `timer` <= '$date'");
        $result = $this->db->query($query);
        $assocArray = ($this->db->countRows($result) == 1) ? array($this->db->fetchAssoc($result)) : $this->db->fetchAssoc($result);
        foreach ($assocArray as $timer){
            $owner = ($timer[ownerAlliance] != 0) ? $this->getOwner($timer[ownerCorporation], $timer[ownerAlliance]) : $this->getOwner($timer[ownerCorporation]);
            $timers[] = array(
                "id" => $timer[id],
                "Who" => ($timer[friendly]) ? true : false,
                "Type" => $timer[type],
                "Owner" => $owner,
                "Region" => $timer[region],
                "System" => $timer[system],
                "P-M" => ($timer[moon] != 0) ? $timer[planet] . " - " . $timer[moon] : $timer[planet],
                "Timer" => $timer[timer] . " ET",
                "Time left" => $this->getTimeLeft($timer[timer]),
                "RF" => ($timer[rfType] == 1) ? "Armor" : "Shield",
                "Status" => ($active) ? "Active" : "Past",
                "Comments" => $timer[comment]
            );
        }
        return $timers;
    }

    public function getOwner($corporation, $alliance = NULL){
        try {
            $owner = $this->apiUserManagement->getCorporationName($corporation);
            $owner .= " [" . $this->apiUserManagement->getCorporationTicker($corporation) . "]";
            if($alliance){
                $owner .= " (" . $this->apiUserManagement->getAllianceName($alliance);
                $owner .= " [" . $this->apiUserManagement->getAllianceTicker($alliance) . "])";
            }
            return $owner;
        } catch (\Pheal\Exceptions\PhealException $e) {
            throw new Exception($e->getMessage(), ($e->getCode())/-1000);
        }
    }

    public function getTimeLeft($time){
        $left = strtotime($time) - strtotime(date("Y-m-d H:i:s"));
        // доделать
        return date('d\d h\h i\m s\s', $left);
    }

    private function getRegion($solarSystemName){
        $query = "SELECT `a`.`itemName` FROM `mapDenormalize` AS `a` INNER JOIN `mapDenormalize` AS `b` ON `b`.`itemName` = '$solarSystemName' WHERE `a`.`itemID` = `b`.`regionID`";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result, 0);
    }
    
    public function setNewTimer($timer = array()){
        /**
         * Type = string structure type name
         * Corporation = string corp name
         * System = string system name
         * Planet = int
         * Moon = int
         * Timer = datetime
         * RF = Armor or Shield
         * Who = true if friendly
         * Comments = string
         */
        //$this->checkRights('add');
        try {
            $ownerCorporation = $this->apiUserManagement->getCorporationID($timer[Corporation]);
            $ownerAlliance = $this->apiUserManagement->getAllianceByCorporation($ownerCorporation);
        } catch (\Pheal\Exceptions\PhealException $e) {
            throw new Exception($ex->getMessage(), ($ex->getCode())/-1000);
        }
        $region = $this->getRegion($timer[System]);
        $rfType = ($timer[RF] == "Armor") ? 1 : 0;
        $friendly = ($timer[Who]) ? 1 : 0;
        $query = "INSERT INTO `timerboard` SET `type` = '{$timer[Type]}', `ownerCorporation` = '$ownerCorporation', `ownerAlliance` = '$ownerAlliance', `region` = '$region', `system` = '{$timer[System]}'";
        $query .= ", `planet` = '{$timer[Planet]}', `moon` = '{$timer[Moon]}', `timer` = '{$timer[Timer]}', `rfType` = '$rfType', `friendly` = '$friendly', `status` = '1', `comment` = '{$timer[Comments]}'";
        $result = $this->db->query($query);
    }
    /*
    public function deleteTimer($tID) {
        $this->checkRights('delete');
        $this->db->deleteTimer($tID);
    }*/
}

?>
