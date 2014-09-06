<?php

/**
 * Description of userSession
 *
 * @author greg2010
 */
class userSession {
    
    private static $_instance = null;
    
    static public function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    private $sessionID;
    private $cookiesArray;
    private $id;
    private $userSalt;
    private $permissions;
    private $db;
    
    private $isLoggedIn;
    private $pagePermissions;
    private $hasAccessToCurrentPage;
    
    private function __construct() {
        $this->sessionStart();
        $this->db = db::getInstance();
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
        $cookieNumber = 4;
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
            
        }
        setcookie('SSID', $newCookieValue, time()-config::cookie_lifetime);
        setcookie('SSID', $newCookieValue, time()+config::cookie_lifetime);
    }
    
    public function logUserByLoginPass($login, $password) {
        $passwordHash = hash(config::password_hash_type, $password);
        $this->id = $this->db->getUserLogin($login, $passwordHash);
        if ($this->id === FALSE) {
            $this->isLoggedIn = FALSE;
        } else {
            $this->isLoggedIn = TRUE;
            $this->setCookie();
            $this->initialize();
        }
    }
    
    private function initialize() {
        $this->permissions = new permissions($this->id);
        
        $query = "SELECT `salt` FROM `users` WHERE `id` = '$this->id'";
        $this->userSalt = $this->db->getMysqlResult($this->db->query($query));
    }
    
    public function preparePage($permissions = array()) {
        $this->setPagePermissions($permissions);
        $this->hasAccess();
    }
    
    public function isLoggedIn() {
        return $this->isLoggedIn;
    }
    
    public function hasPermission() {
        return $this->hasAccessToCurrentPage;
    }
}