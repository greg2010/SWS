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
    private $apiOrgManagement;

    public function __construct() {
        $this->db = db::getInstance();
        /*$id = $_SESSION[userObject]->getID();
        if (!$id) {
            throw new Exception("User isn't logged in!");
        }
        $this->permissions = new permissions($id);*/
        $this->apiOrgManagement = new APIOrgManagement();
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
            $owner = $this->getOwner($timer[ownerCorporation], $timer[ownerAlliance]);
            if($timer[status] == 0) $status = "Active";
            elseif($timer[status] == 1) $status = "Killed";
            elseif($timer[status] == 1) $status = "Saved";
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
                "Status" => $status,
                "Comments" => $timer[comment]
            );
        }
        return $timers;
    }

    private function getOwner($corporation, $alliance){
        try {
            if($corporation > 0){
                $owner .= $this->apiOrgManagement->getCorporationName($corporation);
                $owner .= " [" . $this->apiOrgManagement->getCorporationTicker($corporation) . "]";
            }
            if($alliance > 0){
                if($corporation > 0) $owner .= " (";
                $owner .= $this->apiOrgManagement->getAllianceName($alliance);
                $owner .= " [" . $this->apiOrgManagement->getAllianceTicker($alliance) . "]";
                if($corporation > 0) $owner .= ")";
            }
            return $owner;
        } catch (\Pheal\Exceptions\PhealException $e) {
            throw new Exception($e->getMessage(), ($e->getCode())/-1000);
        }
    }

    private function getTimeLeft($time){
        $left = strtotime($time) - strtotime(date("Y-m-d H:i:s"));
        return date('d\d h\h i\m s\s', $left);
    }

    private function getRegion($solarSystemName){
        $query = "SELECT `a`.`itemName` FROM `mapDenormalize` AS `a` INNER JOIN `mapDenormalize` AS `b` ON `b`.`itemName` = '$solarSystemName' WHERE `a`.`itemID` = `b`.`regionID`";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result, 0);
    }

    private function convertTimerArr($timer = array()){
        try {
            if(isset($timer[Corporation])){
                $ctimer[ownerCorporation] = $this->apiOrgManagement->getCorporationID($timer[Corporation]);
                $ctimer[ownerAlliance] = $this->apiOrgManagement->getAllianceByCorporation($ctimer[ownerCorporation]);
            } elseif(isset($timer[Alliance])){
                $ctimer[ownerCorporation] = 0;
                $ctimer[ownerAlliance] = $this->apiOrgManagement->getAllianceID($timer[Alliance]);
            }
        } catch (\Pheal\Exceptions\PhealException $e) {
            throw new Exception($e->getMessage(), ($e->getCode())/-1000);
        }
        $ctimer[region] = $this->getRegion($timer[System]);
        $ctimer[rfType] = ($timer[RF] == "Armor") ? 1 : 0;
        $ctimer[friendly] = ($timer[Who]) ? 1 : 0;
        if($timer[Status] == "Active") $ctimer[status] = 0;
        elseif($timer[Status] == "Killed") $ctimer[status] = 1;
        elseif($timer[Status] == "Saved") $ctimer[status] = 2;
        return $ctimer;
    }
    
    public function setNewTimer($timer = array()){
        /**
         * Type = string structure type name
         * Corporation = string corp name
         * Alliance = string alliance name
         * System = string system name
         * Planet = int
         * Moon = int
         * Timer = datetime
         * RF = Armor or Shield
         * Who = true if friendly
         * Comments = string
         * Status = string Active = 0, Killed = 1, Saved = 2
         */
        //$this->checkRights('add');
        $ctimer = $this->convertTimerArr($timer);
        $query = "INSERT INTO `timerboard` SET `type` = '{$timer[Type]}', `ownerCorporation` = '{$ctimer[ownerCorporation]}', `ownerAlliance` = '{$ctimer[ownerAlliance]}', `region` = '{$ctimer[region]}', `system` = '{$timer[System]}'";
        $query .= ", `planet` = '{$timer[Planet]}', `moon` = '{$timer[Moon]}', `timer` = '{$timer[Timer]}', `rfType` = '{$ctimer[rfType]}', `friendly` = '{$ctimer[friendly]}', `status` = '{$ctimer[status]}', `comment` = '{$timer[Comments]}'";
        $result = $this->db->query($query);
    }

    public function deleteTimer($tID) {
        //$this->checkRights('delete');
        $query = "DELETE FROM `timerboard` WHERE `id` = '$tID'";
        $result = $this->db->query($query);
    }

    public function editTimer($tID, $timer = array()) {
        //$this->checkRights('edit');
        $ctimer = $this->convertTimerArr($timer);
        $query = "UPDATE `timerboard` SET `type` = '{$timer[Type]}', `ownerCorporation` = '{$ctimer[ownerCorporation]}', `ownerAlliance` = '{$ctimer[ownerAlliance]}', `region` = '{$ctimer[region]}', `system` = '{$timer[System]}'";
        $query .= ", `planet` = '{$timer[Planet]}', `moon` = '{$timer[Moon]}', `timer` = '{$timer[Timer]}', `rfType` = '{$ctimer[rfType]}', `friendly` = '{$ctimer[friendly]}', `status` = '{$ctimer[status]}'";
        $query .= ", `comment` = '{$timer[Comments]}' WHERE `id` = '$tID'";
        $result = $this->db->query($query);
    }
}

?>
