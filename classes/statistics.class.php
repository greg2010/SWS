<?php

/**
 * Description of statistics
 *
 * @author greg2010
 */
class statistics {
    
    private $db;
    
    public function __construct() {
        $this->db = db::getInstance();
    }
    
    public function getRegistrationStats() {
        $query = "SELECT `allianceID` FROM `allowedList` WHERE `characterID` IS NULL AND `corporationID` IS NULL and `allianceID` IS NOT NULL";
        $allianceList = $this->db->fetchRow($this->db->query($query));
        
        $orgManagement = new orgManagement();
        
        $total = 0;
        foreach ($allianceList as $alliance) {
            $query = "SELECT * FROM `apiPilotList` WHERE `allianceID` = '$alliance[0]'";
            $output[$orgManagement->getAllianceName($alliance[0])] = $this->db->countRows($this->db->query($query));
            $total = $total+$output[$orgManagement->getAllianceName($alliance[0])];
        }
        $output['Total'] = $total;
        return $output;
    }
}
