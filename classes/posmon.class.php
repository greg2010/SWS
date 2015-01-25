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
    
    public function getSortedPosList() {
        $posList = $this->getPosList();
        $org = new orgManagement();
        $posListRender = array();
        foreach ($posList as $pos) {
            $posListRender[$org->getAllianceName($pos[allianceID])][$org->getCorporationName($pos[corporationID])][] = $pos;
        }
        ksort($posListRender);
        foreach ($posListRender as $alliance => $corpList) {
            ksort($posListRender[$alliance]);
            foreach ($corpList as $corporation => $list) {
                for ($i = 0; $i < count($list); $i++) {
                    $posListRender[$alliance][$corporation][$i][time] = $this->hoursToDays($posListRender[$alliance][$corporation][$i][time]);
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
