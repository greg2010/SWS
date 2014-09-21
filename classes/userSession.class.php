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
    private $userSalt;
    private $db;
    
    private $isLoggedIn;
    private $pagePermissions;
    private $hasAccessToCurrentPage;
    
    public $permissions;
    
    public function __construct() {
        $this->sessionStart();
        $this->db = db::getInstance();
        $this->logUserByCookie();
    }
    
    public function __sleep() {
         unset($this->db);
         unset($this->permissions);
         return array('sessionID', 'id', 'isLoggedIn');
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
        try {
            $query = "SELECT `salt` FROM `users` WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->userSalt = $this->db->getMysqlResult($result);
        } catch (Exception $ex) {
            //handle mysql errors
        }
    }
    
    public function logUserByLoginPass($login, $password) {
        $passwordHash = hash(config::password_hash_type, $password);
        try {
            $this->id = $this->db->getUserByLogin($login, $passwordHash);
            if ($this->id === FALSE) {
                $this->isLoggedIn = FALSE;
            } else {
                $this->isLoggedIn = TRUE;
                $this->initialize();
            }
            return TRUE;
        } catch (Exception $ex) {
            unset($this->id);
            unset($this->isLoggedIn);
            return FALSE;
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
}