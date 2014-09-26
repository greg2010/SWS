<?php
/**
 * Description of userLogging
 *
 * @author greg2010
 */
class userLogging {
    
    private $db;
    private $sessionInfo;
    
    private $regInfo;
    
    public function __construct() {
        $this->db = db::getInstance();
        $this->getUserInfo();
    }
    
    public function __sleep() {
         unset($this->db);
     }
     
    public function __wakeup() {
        $this->db = db::getInstance();
        $this->getUserInfo();
    }
    
    private function getUserInfo() {
        $this->basicSessionInfo['reqPage'] = $_SERVER[REQUEST_URI];
        $this->basicSessionInfo['IP'] = $_SERVER[REMOTE_ADDR];
        $this->basicSessionInfo['referer'] = $_SERVER[HTTP_REFERER];
        $this->basicSessionInfo['userAgent'] = $_SERVER[HTTP_USER_AGENT];
    }
    
    private function sanitizeArray($array) {
        foreach ($array as $key => $value) {
            $arraySane[$key] = $this->db->sanitizeString($value);
        }
        return $arraySane;
    }
    
    public function setRegistrationInfo($key, $value) {
        $this->regInfo[$key] = $value;
    }
    
    public function setSessionInfo() {
        $this->sessionInfo['hasPermission'] = $_SESSION[userObject]->hasPermission();
        if ($_SESSION[userObject]->isLoggedIn() == 1) {
            $userInfo = $_SESSION[userObject]->getUserInfo();
            $this->sessionInfo['accessMask'] = $userInfo[accessMask];
            
            $pilotInfo = $_SESSION[userObject]->getPilotInfo();
             $this->sessionInfo['characterName'] = $pilotInfo[characterName];
             $this->sessionInfo['characterID'] = $pilotInfo[characterID];
             $this->sessionInfo['corporationID'] = $pilotInfo[corporationID];
             $this->sessionInfo['allianceID'] = $pilotInfo[allianceID];
        }
    }
    
    public function pushToDb($logType) {
        switch ($logType) {
            case 'reg':
                $this->regInfo = $this->sanitizeArray($this->regInfo);
                $query = "INSERT INTO `log.user.registration` SET";
                $query .= " `exceptionCode` = '{$this->regInfo[exceptionCode]}'";
                foreach ($this->regInfo as $key => $value) {
                    if ($key == 'exceptionCode' ) {
                        continue;
                    }
                    $query .= ", `$key` = '$value'";
                }
                break;
            case 'hits':
                $query = "INSERT INTO `log.user.hits` SET";
                $query .=  " `page` = '{$this->basicSessionInfo[reqPage]}'";
                
                foreach ($this->sessionInfo as $key => $value) {
                    $query .= ", `$key` = '$value'";
                }
                break;
        }
        $query .= ", `IP` = '{$this->basicSessionInfo[IP]}'"
                . ", `referer` = '{$this->basicSessionInfo[referer]}'"
                . ", `userAgent` = '{$this->basicSessionInfo[userAgent]}'";
        $this->db->query($query);
    }
}