<?php

/**
 * Description of timerboard
 *
 * @author greg2008200
 */

interface Itimerboard {
    public function getAllTimers();
    public function setNewTimer($timer, $system, $planet, $moon, $rfType, $friendly);
    public function deleteTimer($tID);
}

class timerboard implements Itimerboard {
    
    private $db;
    private $permissions;

    public function __construct() {
        $this->db = db::getInstance();
        $id = $_SESSION[userObject]->getID();
        if (!$id) {
            throw new Exception("User isn't logged in!");
        }
        $this->permissions = new permissions($id);
    }
    
    private function checkRights($action) {
        switch ($action) {
            case 'add':
                $requiredPermissions = array();
                break;
            case 'delete':
                $requiredPermissions = array();
                break;
        }
        foreach ($requiredPermissions as $permission) {
            if (!$this->permissions->hasPermission($permission)) {
                throw new Exception("Not enough permissions!", 11);
            }
        }
    }
    
    public function getAllTimers() {
        $query = "SELECT * FROM `timerboard`";
        $assocArray = $this->db->fetchAssoc($this->db->query($query));
        return $assocArray;
    }
    
    public function setNewTimer($timer, $system, $planet, $moon, $rfType, $friendly) {
        $this->checkRights('add');
        $this->db->setNewTimer($timer, $system, $planet, $moon, $rfType, $friendly);
    }
    
    public function deleteTimer($tID) {
        $this->checkRights('delete');
        $this->db->deleteTimer($tID);
    }
}
