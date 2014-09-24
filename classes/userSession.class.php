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
    
    private $isLoggedIn;
    private $pagePermissions;
    private $hasAccessToCurrentPage;
    
    public $userInfo;
    
    public $permissions;
    public $userManagement;
    
    public function __construct() {
        $this->sessionStart();
        $this->db = db::getInstance();
        $this->logUserByCookie();
    }
    
    public function __sleep() {
         unset($this->db);
         unset($this->permissions);
         return array('sessionID', 'id', 'isLoggedIn', 'userInfo');
     }
     
     public function __wakeup() {
         $this->db = db::getInstance();
         if ($this->id) {
            $this->permissions = new permissions($this->id);
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
            if ($this->isLoggedIn === TRUE) {
                $this->hasAccessToCurrentPage = FALSE;
            } else {
                $this->hasAccessToCurrentPage = TRUE;
            }
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
        $cookie = hash(config::cookie_hash_type, $_SERVER[HTTP_USER_AGENT] . '@' . $_SERVER[REMOTE_ADDR] . "$this->userSalt");
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
            if ($this->db->hasRows($result) === FALSE) {
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
            $query = "UPDATE `userCookies` SET `$cookiePush` = '$newCookieValue', `pointer` = '$pointer'";
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
        try {
            $query = "SELECT * FROM `pilotInfo` WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->userInfo = $this->db->fetchAssoc($result);
            
            $query = "SELECT * FROM `corporationList` WHERE `id` = '{$this->userInfo[corporationID]}'";
            $result = $this->db->query($query);
            $this->corpInfo = $this->db->fetchAssoc($result);
            
            $query = "SELECT * FROM `allianceList` WHERE `id` = '{$this->userInfo[allianceID]}'";
            $result = $this->db->query($query);
            $this->allianceInfo = $this->db->fetchAssoc($result);
        } catch (Exception $ex) {
            echo $ex->getMessage();
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
        $cookie = $_COOKIE[SSID];
        try {
            $this->id = $this->db->getUserByCookie($cookie);
            if ($this->id === FALSE) {
                $this->isLoggedIn = FALSE;
            } else {
                $this->isLoggedIn = TRUE;
                $this->initialize();
            }
        } catch (Exception $ex) {
            unset($this->id);
            unset($this->isLoggedIn);
            return FALSE;
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
        return $this->hasAccessToCurrentPage;
    }
    
    public function removeCookie() {
        $cookieValue = $this->generateCookieForCurrentUser();
        setcookie('SSID', $cookieValue, time()-config::cookie_lifetime);
    }
    public function getPilotInfo() {
        return $this->userInfo;
    }
}