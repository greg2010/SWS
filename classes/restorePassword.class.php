<?php

/**
 * Description of restorePassword
 *
 * @author greg2010
 */
class restorePassword {
    
    private $login;
    private $id;
    private $email;
    
    private $code;
    private $hash;
    private $db;
    
    private $verified;
    private $changeUserID;
    
    public function __sleep() {
        return array('verified', 'changeUserID', 'id', 'login', 'email');
    }

    public function __wakeup() {
        $this->db = db::getInstance();
    }

    public function __construct($action = NULL) {
        $this->db = db::getInstance();
        if ($action == 'init') {
            $this->generateCode();
        }
    }
    
    private function generateCode() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $this->code = $randomString;
        $this->hash = hash(config::cookie_hash_type, $randomString . $_SERVER[REMOTE_ADDR]);
    }
    
    private function makeDBRecord() {
        try {
            $query = "INSERT INTO `passwordRestore` SET `id` = '$this->id', `keyHash` = '$this->hash', `creationTime` = NOW()";
            $this->db->query($query);
        } catch (Exception $ex) {
            throw new Exception($ex, 30);
        }
    }

    private function setCookie() {
        setcookie('restore', $this->code, time()+config::cookie_lifetime);
    }
    
    public function verifyUser($inputHash) {
        $string = $_COOKIE[restore] . $_SERVER[REMOTE_ADDR];
        $hash = hash(config::cookie_hash_type, $string);
        
        if ($hash <> $inputHash) {
            throw new Exception ("Your code is wrong.", 24);
        }
        $query = "SELECT `id` FROM `passwordRestore` WHERE `keyHash` = '$hash' LIMIT 1";
        try {
            $result = $this->db->query($query);
        } catch (Exception $ex) {
            throw new Exception("Database error.", 30);
        }
        if ($this->db->countRows($result) <> 1) {
            throw new Exception("Something went terribly wrong. Please contact administrator to prevent the end of the world!", 30);
        }
        $this->changeUserID = $this->db->getMysqlResult($result);
        $this->verified = TRUE;
        return TRUE;
    }


    public function setNewPassword($password, $passwordRepeat) {
        if ($this->verified <> TRUE) {
            throw new Exception("Go away!", 25);
        }
        $userManagement = new userManagement($this->changeUserID);
        $userManagement->setNewPassword($password, $passwordRepeat);
    }

    public function setUserData($login, $email) {
        if ($login == NULL || $email == NULL) {
            throw new Exception("Please enter your login and email!", 25);
        }
        $this->login = $login;
        $this->email = $email;
        $this->id = $this->db->getIDByName($this->login);
        if ($this->id == FALSE) {
            throw new Exception("No such user!", 21);
        }
        $query = "SELECT `email` FROM `users` WHERE `id` = '$this->id'";
        try {
            $dbEmail = $this->db->getMySQLResult($this->db->query($query));
        } catch (Exception $ex) {
            throw new Exception("Database error.", 30);
        }
        if ($dbEmail == NULL) {
            throw new Exception("No email for this user. Please contact your CEO / director to your change password.", 22);
        }
        if ($dbEmail <> $this->email) {
            throw new Exception("Wrong email!", 23);
        }
        $query = "SELECT * FROM `passwordRestore` WHERE `id` = '$this->id'";

        if ($this->db->countRows($this->db->query($query)) > 0) {
            throw new Exception("You've already requested password reset!", 24);
        }
    }

    public function prepEmail() {
        $this->makeDBRecord();
        $this->setCookie();
        $this->mail();
    }

    public function mail() {
        $subj = "Restore your password at Red Menace Web Services";
        $text = "Hi, $this->login!<br><br>"
                . "Recently you requested to reset your password.<br>.<br>"
                . "Please click <a href ='http://" . $_SERVER['SERVER_NAME'] ."/restorePassword.php?&hash=$this->hash'>here</a> to change your password.<br>"
                . "If you did not request this, please click <a href ='http://" . $_SERVER['SERVER_NAME'] ."/restorePassword.php?hash=$this->hash&action=remove'>here</a>.<br><br>"
                . "Thanks!<br>"
                . "Red Menace Web Services";

        $email = new email();
        $email->sendmailHtml($this->email, $subj, $text);
    }
    
    public function isVerified() {
        return $this->verified;
    }

    public function removeRequest($hash) {
        $this->db->DeleteRestoreHash($hash);
        setcookie('restore', 'NULL', time()-config::cookie_lifetime);
    }
}
