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
         return array('regInfo');
     }
     
    public function __wakeup() {
        $this->db = db::getInstance();
        $this->getUserInfo();
    }
    
    private function getUserInfo() {
        $this->sessionInfo['reqPage'] = $_SERVER[REQUEST_URI];
        $this->sessionInfo['IP'] = $_SERVER[REMOTE_ADDR];
        $this->sessionInfo['referer'] = $_SERVER[HTTP_REFERER];
        $this->sessionInfo['userAgent'] = $_SERVER[HTTP_USER_AGENT];
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
    
    public function pushToDb($logType) {
        switch ($logType) {
            case 'reg':
                $this->regInfo = $this->sanitizeArray($this->regInfo);
                $query = "INSERT INTO `log.user.registration` SET"
                . " `characterID` = '{$this->regInfo[characterID]}'"
                . ", `characterName` = '{$this->regInfo[characterName]}'"
                . ", `corporationID` = '{$this->regInfo[corporationID]}'"
                . ", `allianceID` = '{$this->regInfo[allianceID]}'"
                . ", `keyID` = '{$this->regInfo[keyID]}'"
                . ", `vCode` = '{$this->regInfo[vCode]}'"
                . ", `apiKeyMask` = '{$this->regInfo[apiKeyMask]}'"
                . ", `accessMask` = '{$this->regInfo[accessMask]}'"
                . ", `email` = '{$this->regInfo[email]}'"
                . ", `exceptionCode` = '{$this->regInfo[exceptionCode]}'"
                . ", `exceptionText` = '{$this->regInfo[exceptionText]}'";
                break;
        }
        $query .= ", `IP` = '{$this->sessionInfo[IP]}'"
                . ", `referer` = '{$this->sessionInfo[referer]}'"
                . ", `userAgent` = '{$this->sessionInfo[userAgent]}'";
        $this->db->query($query);
    }
}