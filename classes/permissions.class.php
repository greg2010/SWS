<?php
/**
 * Description of permissions
 *
 * @author greg2010
 */
class permissions {
    private $db;
    private $bitMap;
    private $userMask;
    private $maskLength;
    
    private $userPermissions = array();
    
    public function __construct($id) {
        try {
            $this->db = db::getInstance();
            $this->getBitMap();
            $this->getUserMask($id);
            $this->getUserPermissions();
            
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    private function getBitMap() {
        try {
            $query = "SELECT * FROM `bitMap`";
            $result = $this->db->query($query);
            $bitMapRaw = $this->db->fetchArray($result);
            $bitNames = array();
            foreach ($bitMapRaw as $rows) {
                $bitNames[$rows[bitPosition]] = $rows[name];
            }
            $this->bitMap = $bitNames;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function getUserMask($id) {
        try {
            $query = "SELECT `accessMask` FROM `users` WHERE `id` = '$id'";
            $result = $this->db ->query($db);
            $this->userMask = $this->db->getMysqlResult($result);
//            $this->userMask = 15731715; //Temp full mask for debug
            $this->maskLength = floor(log($this->userMask)/log(2)) + 1;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    private function getUserPermissions() {
        try {
            for ($i = 0; $i <= $this->maskLength; $i++) {
                $isSet = (($this->userMask >> $i)&1);
                if ($isSet) {
                    $this->userPermissions[$i] = $this->bitMap[$i];
                }
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    private function getPermissionsInRange($firstBit, $lastBit) {
        try {
            if ($firstBit > 62 || $lastBit > 63 || $firstBit > $lastBit) {
                throw new Exception("Invalid bit range!");
            }
            $rightsRequested = array();
            for ($i = $firstBit; $i <= $lastBit; $i++) {
                if ($this->userPermissions[$i]) {
                    $rightsRequested[] = $this->userPermissions[$i];
                }
            }
            return $rightsRequested;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }
    
    public function getWebPermissions() {
        //Web user permissions are defined by bits from 0 to 9.
        $firstBit = 0;
        $lastBit = 9;
        $webPermissions = $this->getPermissionsInRange($firstBit, $lastBit);
        return $webPermissions;
    }
    
    public function getXMPPPermissions() {
        //XMPP user permissions are defined by bits from 10 to 19.
        $firstBit = 10;
        $lastBit = 19;
        $XMPPPermissions = $this->getPermissionsInRange($firstBit, $lastBit);
        return $XMPPPermissions;
    }
    
    public function getTSPermissions() {
        //TS user permissions are defined by bits from 20 to 29.
        $firstBit = 20;
        $lastBit = 29;
        $TSPermissions = $this->getPermissionsInRange($firstBit, $lastBit);
        return $TSPermissions;
    }
    
    public function getAllPermissions() {
        $permissions = array();
        foreach ($this->getWebPermissions() as $permission) {
            $permissions[] = $permission;
        }
        foreach ($this->getXMPPPermissions() as $permission) {
            $permissions[] = $permission;
        }
        foreach ($this->getTSPermissions() as $permission) {
            $permissions[] = $permission;
        }
        return $permissions;
    }
}