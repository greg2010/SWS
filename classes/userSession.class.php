<?php

/**
 * Description of userSession
 *
 * @author greg2010
 */
class userSession {
    
    private $sessionID;
    private $cookiesArray;
    private $id;
    private $salt;
    private $db;
    private $settings;
    
    private $isLoggedIn;
    private $pagePermissions;
    private $hasAccessToCurrentPage;
    
    public $userInfo;
    public $apiPilotList;
    public $corpInfo;
    public $allianceInfo;
    
    public $permissions;
    public $userManagement;
    
    public function __construct() {
        $this->sessionStart();
        $this->db = db::getInstance();
        $this->permissions = new permissions();
        $this->userManagement = new userManagement();
    }
    
    public function __sleep() {
         unset($this->db);
         unset($this->permissions);
         return array('sessionID', 'id', 'isLoggedIn', 'userInfo', 'apiPilotList', 'corpInfo', 'allianceInfo', 'salt');
     }
     
    public function __wakeup() {
        $this->db = db::getInstance();
           $this->permissions = new permissions($this->id);
        if ($this->id) {
           $this->userManagement = new userManagement($this->id);
           $this->updateUserInfo();
        }
    }

    private function sessionStart() {
        $sessionStarted = session_start();
        if ($sessionStarted === False) {
            die("Session hasn't started. Aborting...");
        }
        $this->sessionID = session_id();
    }
    
    private function setPagePermissions($permissions) {
        if (!$permissions) {
            $permissions = array();
        }
        $pm = new permissions();
        $bitMap = $pm->getBitMap();
        $cleanPermissions = array();
        foreach ($permissions as $permission) {
            $isValid = in_array($permission, $bitMap);
            if ($isValid === TRUE) {
                $cleanPermissions[] = $permission;
            }
        }
        $this->pagePermissions = $cleanPermissions;
        unset($bitMap);
        unset($pm);
    }

    private function hasAccess() {
        if (count($this->pagePermissions) === 0) {
            $this->hasAccessToCurrentPage = TRUE;
        } else {
            $neededPermissions = array();
            foreach ($this->pagePermissions as $permission) {
                if ($this->permissions->hasPermission($permission) === FALSE) {
                    $neededPermissions[] = $permission;
                }
            }
            if (count($neededPermissions) > 0) {
                $this->hasAccessToCurrentPage = FALSE;
            } else {
                $this->hasAccessToCurrentPage = TRUE;
            }
        }
    }
    
    private function generateCookieForCurrentUser() {
        $cookie = hash(config::cookie_hash_type, $_SERVER[HTTP_USER_AGENT] . '@' . $_SERVER[REMOTE_ADDR] . $this->salt);
        return $cookie;
    }
    
    private function getCookies() {
        try {
            $cookieNumber = config::cookieNumber;
            for ($i = 0; $i<=$cookieNumber; $i++) {
                $fields .= '`cookie' . $i;
                if ($i<$cookieNumber) {
                    $fields .= '`, ';
                } else {
                    $fields .= '` ';
                }
            }
            $query = "SELECT $fields FROM `userCookies` WHERE `id` = $this->id";
            $result = $this->db->query($query);
            if ($this->db->hasRows($result) == FALSE) {
                $query = "INSERT INTO `userCookies` SET `id` = $this->id";
                $result = $this->db->query($query);
                $query = "SELECT $fields FROM `userCookies` WHERE `id` = $this->id";
                $result = $this->db->query($query);
            }
            $this->cookiesArray = $this->db->fetchAssoc($result);
        } catch (Exception $ex) {
            //handle mysql errors
        }
    }
    
    private function setCookie() {
        setcookie('SSID', '', time()-config::cookie_lifetime);
        try {
            $newCookieValue = $this->generateCookieForCurrentUser();
            if (!$this->cookiesArray) {
                $this->getCookies();
            }
            foreach ($this->cookiesArray as $cookieNumber => $cookie) {
                if ($cookie === $newCookieValue) {
                    throw new Exception("Cookie has already been set. Cookie value: $cookie", 0);
                }
                if (!$cookie) {
                    $cookiePush = $cookieNumber;
                    break 1;
                }
            }
            if (!$cookiePush) {
                $query = "SELECT `pointer` FROM `userCookies` WHERE `id` = '$this->id'";
                $result = $this->db->query($query);
                $pointer = $this->db->getMysqlResult($result);
                if ($pointer === NULL) {
                    $pointer = 0;
                } elseif ($pointer === 4) {
                    $pointer = 0;
                } else {
                    $pointer++;
                }
                $cookiePush = 'cookie' . $pointer;
            } else {
                $pointer = NULL;
            }
            $query = "UPDATE `userCookies` SET `$cookiePush` = '$newCookieValue', `pointer` = '$pointer' WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->getCookies();
        } catch (Exception $ex) {
            //handle mysql errors
            //0 - Cookie already exists, 1 - mysql error
        }
        setcookie('SSID', $newCookieValue, time()+config::cookie_lifetime);
    }
    
    private function initialize() {
        $this->permissions = new permissions($this->id);
        $this->userManagement = new userManagement($this->id);
        $this->updateUserInfo();
    }
    
    public function updateUserInfo() {
        try {
            $query = "SELECT `email`, `lastNotifID`, `salt`, `accessMask`, `settingsMask` FROM `users` WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->userInfo = $this->db->fetchAssoc($result);
            $this->updateSettingsInfo();
            
            $query = "SELECT * FROM `apiPilotList` WHERE `id` = '$this->id' AND `keyStatus` = '1'";
            $result = $this->db->query($query);
            $this->apiPilotList['mainAPI'] = $this->db->fetchAssoc($result);
            
            if (count($this->apiPilotList['mainAPI']) == 0) {
                $this->apiPilotList['mainAPI']['characterID'] = 1;
                $this->apiPilotList['mainAPI']['characterName'] = "NoName";
            }
            
            $query = "SELECT * FROM `apiPilotList` WHERE `id` = '$this->id' AND `keyStatus` = '2'";
            $result = $this->db->query($query);
            $this->apiPilotList['secAPI'] = $this->db->fetchAssoc($result);
            
            $query = "SELECT * FROM `corporationList` WHERE `id` = '{$this->pilotInfo[corporationID]}'";
            $result = $this->db->query($query);
            $this->corpInfo = $this->db->fetchAssoc($result);
            
            $query = "SELECT * FROM `allianceList` WHERE `id` = '{$this->pilotInfo[allianceID]}'";
            $result = $this->db->query($query);
            $this->allianceInfo = $this->db->fetchAssoc($result);
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
    }

    public function logUserByLoginPass($login, $password) {
        try {
            $id = $this->db->getIDByName($login);
            if ($id) {
                $query = "SELECT `salt` FROM `users` WHERE `id` = $id";
                $this->salt = $this->db->getMySQLResult($this->db->query($query));
            }
            $password = $password . $this->salt;
            $passwordHash = hash(config::password_hash_type, $password);
            $this->id = $this->db->getUserByLogin($login, $passwordHash);
            if ($this->id === FALSE) {
                $this->isLoggedIn = FALSE;
                throw new Exception("Login Failed!", 11);
            } else {
                $this->isLoggedIn = TRUE;
                $this->initialize();
            }
            return TRUE;
        } catch (Exception $ex) {
            if ($ex->getCode() == 11) {
                throw new Exception($ex->getMessage(), $ex->getCode());
            }
            unset($this->id);
            unset($this->isLoggedIn);
            throw new Exception("MySQL error! " . $ex->getMessage(), 30);
        }
    }
    
    public function logUserByCookie() {
        if ($_COOKIE[SSID]) {
            $_SESSION[logObject]->setLoginInfo('loginMethod', 'cookie');
            $cookie = $_COOKIE[SSID];
            try {
                $this->id = $this->db->getUserByCookie($cookie);
                if ($this->id === FALSE) {
                    //hack attempt
                } else {
                    $this->isLoggedIn = TRUE;
                    $this->initialize();
                    $query = "SELECT `salt` FROM `users` WHERE `id` = $this->id";
                    $this->salt = $this->db->getMySQLResult($this->db->query($query));
                    $_SESSION[logObject]->setLoginInfo('exceptionCode', 0);
                    $_SESSION[logObject]->setLoginInfo('exceptionText', NULL);
                    $_SESSION[logObject]->setSessionInfo();
                    $_SESSION[logObject]->pushToDb('login');
                }
            } catch (Exception $ex) {
                $this->isLoggedIn = FALSE;
                $_SESSION[logObject]->setLoginInfo('exceptionCode', $ex->getCode());
                $_SESSION[logObject]->setLoginInfo('exceptionText', $ex->getMessage());
                $_SESSION[logObject]->setSessionInfo();
                $_SESSION[logObject]->pushToDb('login');
                return FALSE;
            }
        }
    }
    
    public function verifyCurrentPassword($password) {
        if (!$this->isLoggedIn) {
            throw new Exception("Not logged in.", 30);
        }
        $passwordHash = hash(config::password_hash_type, $password . $this->salt);
         if (!($this->id === $this->db->getUserByLogin($this->apiPilotList[mainAPI][characterName], $passwordHash))) {
             throw new Exception("Wrong password!", 13);
         }
    }
    
    public function setCookieForUser() {
        if ($this->isLoggedIn) {
            $this->setCookie();
            return TRUE;
        } else {
            $this->removeCookie();
            return FALSE;
        }
    }

    public function preparePage($permissions = array()) {
        $this->setPagePermissions($permissions);
        $this->hasAccess();
    }
    
    public function isLoggedIn() {
        return strval($this->isLoggedIn);
    }
    
    public function hasPermission() {
//        if (!isset($this->pagePermissions)) {
//            throw new Exception("You have to call setPermissions() firstly!", 30);
//        }
        return $this->hasAccessToCurrentPage;
    }
    
    public function hasTSRegistration() {
        $query = "SELECT * FROM `teamspeak` WHERE `id` = '$this->id' LIMIT 1";
        $hasReg = $this->db->countRows($this->db->query($query));
        if ($hasReg == 1) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public function removeCookie() {
        $cookieValue = $this->generateCookieForCurrentUser();
        setcookie('SSID', $cookieValue, time()-config::cookie_lifetime);
    }
    
    
    public function getUserInfo() {
        return $this->userInfo;
    }
    
    public function getApiPilotInfo() {
        return $this->apiPilotList;
    }
    
    public function getCorpInfo() {
        return $this->corpInfo;
    }
    
    public function getAllianceInfo() {
        return $this->allianceInfo;
    }
    public function getID() {
        return $this->id;
    }
    
    public function getTSInfo() {
        $query = "SELECT `uniqueID` FROM `teamspeak` WHERE `id` = $this->id LIMIT 1";
        $TSInfo['uniqueID'] = $this->db->getMySQLResult($this->db->query($query));
        $ts3 = new ts3();
        $TSInfo['nickName'] = $ts3->nickname($this->id);
        return $TSInfo;
    }
    
    private function updateSettingsInfo() {
        unset($this->settings);
        $mask = $this->userInfo[settingsMask];
        $maskLength = floor(log($mask)/log(2)) + 1;
        for ($i = 0; $i <= $maskLength; $i++) {
            $isSet = (($mask >> $i)&1);
            if ($isSet) {
                if ($i == 0) {
                    $this->settings[emailNotif] = 1;
                }
                if ($i == 1) {
                    $this->settings[jabberNotif] = 1;
                }
            }
        }
    }
    
    public function getUserSettings() {
        $this->updateUserInfo();
        return $this->settings;
    }
    
    public function setUserSettings($settingsArr) {
        $mask = 0;
        if ($settingsArr[email] == 1) {
            $mask = $mask | 1;
        }
        if ($settingsArr[jabber] == 1) {
            $mask = $mask | 2;
        }
        $query = "UPDATE `users` SET `settingsMask` = '$mask' WHERE `id` = '$this->id'";
        $this->db->query($query);
    }
    
    public function getJabberLogin() {
        $jabbName = str_replace("'", '.', str_replace(' ', '_',$this->apiPilotList['mainAPI']['characterName']));
        return $jabbName;
    }
    
    public function getUserAffiliations() {
        $apiInfo = $this->apiPilotList;
        
        $affiliations = array();
        $affiliations['corporation'][] = $apiInfo[mainAPI][corporationID];
        $affiliations['alliance'][] = $apiInfo[mainAPI][allianceID];
        if (is_array($apiInfo[secAPI])) {
            if (is_array($apiInfo[secAPI][0])) {
                foreach ($apiInfo[secAPI] as $secApis) {
                    $affiliations['corporation'][] = $secApis[corporationID];
                    $affiliations['alliance'][] = $secApis[allianceID];
                }
            } else {
                $affiliations['corporation'][] = $apiInfo[secAPI][corporationID];
                $affiliations['alliance'][] = $apiInfo[secAPI][allianceID];
            }
        }
        
        $affiliations['corporation'] = array_unique($affiliations[corporation]);
        $affiliations['alliance'] = array_unique($affiliations[alliance]);
        return $affiliations;
    }
}