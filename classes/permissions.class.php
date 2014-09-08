<?php
/**
 * Description of permissions
 *
 * @author greg2010
 */
class permissions {
    protected $id;
    protected $db;
    protected $bitMap;
    protected $userPermissions = array();
    public $log;
    private $userMask;
    private $maskLength;
    
    function __construct($id) {
        $this->log = new logging();
        try {
            $this->db = db::getInstance();
            $this->getBitMapFromDb();
            if (!isset($id)) {
                $this->id = -1;
            } else {
                $this->id = $id;
                $this->getUserMask();
                $this->getUserPermissions();
            }
            return true;
        } catch (Exception $ex) {
            $this->log->put("construct", "err " . $ex->getMessage());
            return false;
        }
    }
    
    private function getBitMapFromDb() {
        try {
            $query = "SELECT * FROM `bitMap`";
            $result = $this->db->query($query);
            $bitMapRaw = $this->db->fetchArray($result);
            $bitNames = array();
            foreach ($bitMapRaw as $rows) {
                $bitNames[$rows[bitPosition]] = $rows[name];
            }
            $this->bitMap = $bitNames;
            return true;
        } catch (Exception $ex) {
            $this->log->put("getBitMapFromDb", "err " . $ex->getMessage());
            return false;
        }
    }

    private function getUserMask() {
        try {
            $query = "SELECT `accessMask` FROM `users` WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->userMask = $this->db->getMysqlResult($result);
            //$this->userMask = 15731715; //Temp full mask for debug
            $this->maskLength = floor(log($this->userMask)/log(2)) + 1;
            return true;
        } catch (Exception $ex) {
            $this->log->put("getUserMask", "err " . $ex->getMessage());
            return false;
        }
    }
    
    protected function returnUserMask() {
        return $this->userMask;
    }

    private function getUserPermissions() {
        try {
            for ($i = 0; $i <= $this->maskLength; $i++) {
                $isSet = (($this->userMask >> $i)&1);
                if ($isSet) {
                    $this->userPermissions[$i] = $this->bitMap[$i];
                }
            }
            return true;
        } catch (Exception $ex) {
            $this->log->put("getUserPermissions", "err " . $ex->getMessage());
            return false;
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
            $this->log->put("getPermissionsInRange", "err " . $ex->getMessage());
        }
    }
    
    private function updateUserMask() {
        try {
            $query = "UPDATE `users` SET `accessMask` = '$this->userMask' WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            return true;
        } catch (Exception $ex) {
            $this->log->put("updateUserMask", "err " . $ex->getMessage());
            return false;
        }
    }
    
    public function hasPermission($permission) {
        try {
            $permissionBit = array_search($permission, $this->bitMap);
            if ($permissionBit === False) {
                throw new Exception("Invalid permission!");
            }
            $hasPermission = $this->getPermissionsInRange($permissionBit, $permissionBit);
            if (count($hasPermission) === 1) {
                return True;
            } else {
                return False;
            }
        } catch (Exception $ex) {
            $this->log->put("hasPermission", "err " . $ex->getMessage());
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
    
    public function setPermissions($newPermissions = array()) {
        try {
            $userMaskBeforeChanges = $this->userMask;
            if (count($newPermissions) < 1) {
                throw new Exception("No permissions to set!");
            }
            $newBits = array();
            foreach ($newPermissions as $permission) {
                $newBits[] = array_search($permission, $this->bitMap);
            }
            foreach ($newBits as $setBit) {
                $this->userMask = ($this->userMask |  pow(2, $setBit));
            }
            if ($userMaskBeforeChanges === $this->userMask) {
                throw new Exception("No changes are needed!");
            }
            $this->updateUserMask();
            $this->getUserPermissions();
            return true;
        } catch (Exception $ex) {
            $this->log->put("setPermissions", "err " . $ex->getMessage());
            return false;
        }
    }
    public function unsetPermissions($remPermissions = array()) {
        try {
            $userMaskBeforeChanges = $this->userMask;
            if (count($remPermissions) < 1) {
                throw new Exception("No permissions to set!");
            }
            $remBits = array();
            foreach ($remPermissions as $permission) {
                $remBits[] = array_search($permission, $this->bitMap);
            }
            foreach ($remBits as $unsetBit) {
                $this->userMask = ($this->userMask & ~(pow(2, $unsetBit)));
            }
            if ($userMaskBeforeChanges === $this->userMask) {
                throw new Exception("No changes are needed!");
            }
            $this->updateUserMask();
            $this->getUserPermissions();
            return true;
        } catch (Exception $ex) {
            $this->log->put("unsetPermissions", "err " . $ex->getMessage());
            return false;
        }
    }
    
    public function getBitMap() {
        return $this->bitMap;
    }
}