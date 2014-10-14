<?php

class permissions {
    protected $id;
    protected $db;
    protected $bitMap;
    protected $userPermissions = array();
    private $userMask;
    private $maskLength;
    
    function __construct($id = NULL) {
        $this->db = db::getInstance();
        $this->getBitMapFromDb();
        if (!isset($id)) {
            $this->id = -1;
        } else {
            $this->id = $id;
            $this->getUserMask();
            $this->getUserPermissions();
        }
    }
    
    private function getBitMapFromDb() {
        $query = "SELECT * FROM `bitMap`";
        $result = $this->db->query($query);
        $bitMapRaw = $this->db->fetchArray($result);
        $bitNames = array();
        foreach ($bitMapRaw as $rows) {
            $bitNames[$rows[bitPosition]] = $rows[name];
        }
        $this->bitMap = $bitNames;
    }

    private function getUserMask($mask = NULL) {
        if (strlen($mask) > 0) {
            $this->userMask = $mask;
        } else {
            $query = "SELECT `accessMask` FROM `users` WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->userMask = $this->db->getMysqlResult($result);
        }
        //$this->userMask = 15731715; //Temp full mask for debug
        $this->maskLength = floor(log($this->userMask)/log(2)) + 1;
    }
    
    protected function returnUserMask() {
        return $this->userMask;
    }

    private function getUserPermissions() {
        for ($i = 0; $i <= $this->maskLength; $i++) {
            $isSet = (($this->userMask >> $i)&1);
            if ($isSet) {
                $this->userPermissions[$i] = $this->bitMap[$i];
            }
        }
    }
    
    private function getPermissionsInRange($firstBit, $lastBit) {
        if ($firstBit > 62 || $lastBit > 63 || $firstBit > $lastBit) {
            throw new Exception("Invalid bit range!", -101);
        }
        $rightsRequested = array();
        for ($i = $firstBit; $i <= $lastBit; $i++) {
            if ($this->userPermissions[$i]) {
                $rightsRequested[] = $this->userPermissions[$i];
            }
        }
        return $rightsRequested;
    }
    
    private function updateUserMask() {
        $query = "UPDATE `users` SET `accessMask` = '$this->userMask' WHERE `id` = '$this->id'";
        $result = $this->db->query($query);
    }
    
    public function hasPermission($permission) {
        $permissionBit = array_search($permission, $this->bitMap);
        if ($permissionBit === False) {
            throw new Exception("Invalid permission!", -102);
        }
        $hasPermission = $this->getPermissionsInRange($permissionBit, $permissionBit);
        if (count($hasPermission) === 1) {
            return True;
        } else {
            return False;
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
        $userMaskBeforeChanges = $this->userMask;
        if (count($newPermissions) < 1) {
            throw new Exception("No permissions to set!", -103);
        }
        $newBits = array();
        foreach ($newPermissions as $permission) {
            $newBits[] = array_search($permission, $this->bitMap);
        }
        foreach ($newBits as $setBit) {
            $this->userMask = ($this->userMask |  pow(2, $setBit));
        }
        if ($userMaskBeforeChanges === $this->userMask) {
            throw new Exception("No changes are needed!", -104);
        }
        $this->updateUserMask();
        $this->getUserPermissions();
    }

    public function convertPermissions($Permissions = array()) {
        if (count($Permissions) < 1) {
            return 0;
        }
        $Bits = array();
        foreach ($Permissions as $permission) {
            $result = array_search($permission, $this->bitMap);
            if($result == false) throw new Exception("Incorrect permission!", -108);
            $Bits[] = $result;
        }
        foreach ($Bits as $Bit) {
            $Mask = $Mask | (1 << $Bit);
        }
        return $Mask;
    }

    public function unsetPermissions($remPermissions = array()) {
        $userMaskBeforeChanges = $this->userMask;
        if (count($remPermissions) < 1) {
            throw new Exception("No permissions to set!", -105);
        }
        $remBits = array();
        foreach ($remPermissions as $permission) {
            $remBits[] = array_search($permission, $this->bitMap);
        }
        foreach ($remBits as $unsetBit) {
            $this->userMask = ($this->userMask & ~(pow(2, $unsetBit)));
        }
        if ($userMaskBeforeChanges === $this->userMask) {
            throw new Exception("No changes are needed!", -106);
        }
        $this->updateUserMask();
        $this->getUserPermissions();
    }
    
    public function setUserMask($mask) {
        if ($this->id <> -1) {
            throw new Exception("Method is only available in fake user mode!", -107);
        }
        unset($this->userMask);
        unset($this->userPermissions);
        unset($this->maskLength);
        $this->getUserMask($mask);
        $this->getUserPermissions();
    }

    public function getBitMap() {
        return $this->bitMap;
    }
}

?>
