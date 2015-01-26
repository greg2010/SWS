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
            'h' =>$hoursLeft
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
        } else {
            
        }
        return $siloList;
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
                    $query = "SELECT `itemName` FROM `mapDenormalize` WHERE `itemID` = (SELECT  `regionID` FROM  `mapSolarSystems` WHERE  `solarSystemID` =  '{$posListRender[$alliance][$corporation][$i][locationID]}' LIMIT 1)";
                    $posListRender[$alliance][$corporation][$i]["region"] = $this->db->getMySQLResult($this->db->query($query));
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
                }
            }
        }
        return $posListRender;
    }
}
