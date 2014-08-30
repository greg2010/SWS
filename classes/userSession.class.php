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
    private $id;
    
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
    
    private function initialize() {
        $this->permissions = new permissions($this->id);
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
    
    public function logUserByLoginPass($login, $password) {
        $passwordHash = hash(sha512, $password);
        $this->id = $this->db->getUserLogin($login, $passwordHash);
        if ($this->id === FALSE) {
            $this->isLoggedIn = FALSE;
        } else {
            $this->isLoggedIn = TRUE;
            $this->initialize();
        }
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
