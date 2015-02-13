<?php

/**
 * Description of posmon
 *
 * @author greg2010
 */
class posmon {
    
    private $db;


    public function __construct() {
        $this->db = db::getInstance();
    }

    private function hoursToDays($inputTime) {
        $hoursInADay = 24;
        $days = floor($inputTime / $hoursInADay);
        $hoursLeft = $inputTime - $days * $hoursInADay;
        $result = array (
            'd' => $days,
            'h' => $hoursLeft
        );
        return $result;
    }
    
    private function getPosList() {
        
    $affiliation = $_SESSION[userObject]->getUserAffiliations();
    if ($_SESSION[userObject]->permissions->hasPermission('webReg_AdminPanel')) {
        $query = "SELECT * FROM `posList`";
    } elseif ($_SESSION[userObject]->permissions->hasPermission('XMPP_Overmind')) {
        $i = 0;
        $query = "SELECT * FROM `posList` WHERE ";
        foreach ($affiliation[alliance] as $allianceID) {
            $query .= "`allianceID` = '$allianceID'";
            $i++;

            if ($i < count($affiliation[alliance])) {
                $query .= " OR ";
            }
        }
    } else {
        $i = 0;
        $query = "SELECT * FROM `posList` WHERE ";
        foreach ($affiliation[corporation] as $corporationID) {
            $query .= "`corporationID` = '$corporationID'";
            $i++;

            if ($i < count($affiliation[corporation])) {
                $query .= " OR ";
            }
        }
    }
    return $this->db->fetchAssoc($this->db->query($query));
    }
    
    private function getSiloInformation($posID, $posType) {
        $query = "SELECT * FROM `siloList` WHERE `posID` = '$posID'";
        $result = $this->db->query($query);
        if ($this->db->countRows($result) > 1) {
            $siloList = $this->db->fetchAssoc($result);
        } elseif ($this->db->countRows($result) == 1) {
            $siloList[0] = $this->db->fetchAssoc($result);
        }
        
        switch ($posType) {
            case "Minmatar":
            case "Angel":
            case "Domination":
                $siloMax = "20000";
                break;
            case "Caldari":
            case "Guristas":
            case "Dread":
                $siloMax = "20000";
                break;
            case "Amarr":
            case "True":
            case "Dark":
            case "Sansha":
            case "Blood":
                $siloMax = "30000";
                break;
            case "Gallente":
            case "Shadow":
            case "Serpentis":
                $siloMax = "40000";
                break;
            default:
                $siloMax = "0";
                break;
        }
        
        if (is_array($siloList)) {
            foreach ($siloList as $i => $silo) {
                $siloList[$i]['Percentage'] = round($silo[mmvolume]*$silo[quantity]*100/$siloMax);
                $siloList[$i]['siloMax'] = $siloMax;
            }
        }
        return $siloList;
    }  
    
    public function checkIfHasApiKey($corporationID) {
        $query = "SELECT * FROM `apiCorpList` WHERE `corporationID` = '$corporationID' AND `keyStatus` = '1' LIMIT 1";
        if (!$this->db->hasRows($this->db->query($query))) {
            throw new Exception("No API key for main corp!", 26);
        }
        $roles = $this->db->fetchAssoc($this->db->query($query));
        if ((($roles[accessMask] & 16777216) == 0) && (($roles[accessMask] & 524288) == 0) && (($roles[accessMask] & 2) == 0)) {
            throw new Exception("Invalid API key for main corp!", 27);
        }
    }
    
    public function updateSiloOwner($siloID, $newPosID) {
        $query = "SELECT `corporationID`,`locationID` FROM `posList` WHERE `posID` = '$newPosID' LIMIT 1";
        $newPosInfo = $this->db->fetchAssoc($this->db->query($query));
        
        $query = "SELECT `corporationID`,`locationID` FROM `siloList` WHERE `siloID` = '$siloID' LIMIT 1";
        $siloInfo = $this->db->fetchAssoc($this->db->query($query));
        if ($siloInfo[locationID] <> $newPosInfo[locationID]) {
            throw new Exception("Wrong system!", 13);
        }
        if ($siloInfo[corporationID] <> $newPosInfo[corporationID]) {
            throw new Exception("Wrong corporation!", 13);
        }
        
        $query = "UPDATE `siloList` SET `posID` = '$newPosID' WHERE `siloID` = '$siloID'";
        $this->db->query($query);
    }
    
    public function getSortedPosList() {
        $posList = $this->getPosList();
        $org = new orgManagement();
        $posListRender = array();
        foreach ($posList as $pos) {
            if ($pos[allianceID] == NULL) {
                $allianceName = "Without Alliance";
            } else {
                $allianceName = $org->getAllianceName($pos[allianceID]);
            }
            $posListRender[$allianceName][$org->getCorporationName($pos[corporationID])][] = $pos;
        }
        ksort($posListRender);
        foreach ($posListRender as $alliance => $corpList) {
            ksort($posListRender[$alliance]);
            foreach ($corpList as $corporation => $list) {
                for ($i = 0; $i < count($list); $i++) {
                    $query = "SELECT `a`.`itemName` FROM `mapDenormalize` AS `a` INNER JOIN `mapDenormalize` AS `b` ON `b`.`itemID` = '{$posListRender[$alliance][$corporation][$i][locationID]}' WHERE `a`.`itemID` = `b`.`regionID`";
                    $posListRender[$alliance][$corporation][$i]["region"] = $this->db->getMySQLResult($this->db->query($query));
                    $query = "SELECT `a`.`itemName` FROM `mapDenormalize` AS `a` INNER JOIN `mapDenormalize` AS `b` ON `b`.`itemID` = '{$posListRender[$alliance][$corporation][$i][locationID]}' WHERE `a`.`itemID` = `b`.`constellationID`";
                    $posListRender[$alliance][$corporation][$i]["constellation"] = $this->db->getMySQLResult($this->db->query($query));
                    $posListRender[$alliance][$corporation][$i][time] = $this->hoursToDays($posListRender[$alliance][$corporation][$i][time]);
                    
                    $posType = explode(" ", $posListRender[$alliance][$corporation][$i][typeName]);
                    $posListRender[$alliance][$corporation][$i]['silo'] = $this->getSiloInformation( $posListRender[$alliance][$corporation][$i][posID], $posType[0]);
                    
                    $locationName = explode(" ", $posListRender[$alliance][$corporation][$i][moonName]);
                    $posListRender[$alliance][$corporation][$i]["locationName"] = $locationName[0];
                    if ($posListRender[$alliance][$corporation][$i][state] == 3) {
                        $posListRender[$alliance][$corporation][$i][rfTime] = $posListRender[$alliance][$corporation][$i][stateTimestamp];
                    } else {
                         $posListRender[$alliance][$corporation][$i][rfTime] = $this->hoursToDays($posListRender[$alliance][$corporation][$i][rfTime]);
                    }
                    
                    $query = "SELECT `moonName`, `posID` FROM `posList` WHERE `corporationID` = '{$posListRender[$alliance][$corporation][$i][corporationID]}' AND `locationID` = '{$posListRender[$alliance][$corporation][$i][locationID]}'";
                    $result = $this->db->query($query);
                    if ($this->db->countRows($result) > 1) {
                        $altPoses = $this->db->fetchAssoc($result);
                        for ($j=0;$j<=count($altPoses);$j++) {
                            if ($altPoses[$j][moonName] == $posListRender[$alliance][$corporation][$i][moonName]) {
                                unset($altPoses[$j]);
                            }
                        }
                        if (count($altPoses) == 1) {
                            $altPoses['switchable'] = 1;
                        }
                    } elseif ($this->db->countRows($result) == 1) {
                        $altPoses[0] = $this->db->fetchAssoc($result);
                    }
                    $posListRender[$alliance][$corporation][$i][silo][altPoses] = $altPoses;
                    unset($altPoses);
                }
            }
        }
        return $posListRender;
    }
}
