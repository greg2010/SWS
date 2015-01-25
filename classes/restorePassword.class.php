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
    
    private function __sleep() {
        return array('verified', 'changeUserID');
    }


    public function __construct() {
        $this->db = db::getInstance();
        $this->generateCode();
    }
    
    private function generateCode() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randstring = '';
        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $this->code = $randomString;
        $this->hash = hash(config::cookie_hash_type, $randomString . $_SERVER[REMOTE_ADDR]);
    }
    
    private function makeDBRecord() {
        $query = "INSERT INTO `passwordRestore` SET `id` = '$this->id', `keyHash` = '$this->hash', `creationTime` = 'NOW()'";
        $this->db->query($query);
    }

    private function setCookie() {
        setcookie('restore', $this->code, time()+config::cookie_lifetime);
    }

    private function sendmail($subj, $text){
        $subject  = '=?UTF-8?B?' . base64_encode($subj) . '?=';
        $headers  = "MIME-Version: 1.0\r\n"; 
        $headers .= "Content-type: text/html; charset=utf-8\r\n";
        $headers .= "From: RADRF Web Services <mailer@coalition.redalliance.pw>\r\n";
        $headers .= "Reply-to: No-Reply <noreply@coalition.redalliance.pw>\r\n";
        mail($this->email, $subject, $text, $headers);
    }
    
    public function verifyUser($inputHash) {
        $string = $_COOKIE[restore] . $_SERVER[REMOTE_ADDR];
        $hash = hash($string);
        
        if ($hash <> $inputHash) {
            throw new Exception ("Your code is wrong. Please try again.", 24);
        }
        $query = "SELECT `id` FROM `passwordRestore` WHERE `keyHash` = '$hash' LIMIT 1";
        $result = $this->db->query($query);
        if ($this->db->countRows($result) <> 1) {
            throw new Exception("Something went terribly wrong. Please contact administrator to prevent the end of the world!", 30);
        }
        $this->changeUserID = $this->db->fetchMySQLResult($result);
        $this->verified = TRUE;
    }


    public function setNewPassword($password, $passwordRepeat) {
        if ($this->verified <> TRUE) {
            throw new Exception("Go away!", 25);
        }
        $userManagement = new userManagement($this->changeUserID);
        $userManagement->setNewPassword($password, $passwordRepeat);
    }

    public function setUserData($login, $email) {
        $this->login = $login;
        $this->email = $email;
        $this->id = $this->db->getIDByName($this->login);
        if ($this->id == FALSE) {
            throw new Exception("No such user!", 21);
        }
        $query = "SELECT `email` FROM `users` WHERE `id` = '$id'";
        $dbEmail = $this->db->getMySQLResult($this->db->query($query));
        if ($dbEmail == NULL) {
            throw new Exception("No email for this user. Please contact your CEO / director to your change password.", 22);
        }
        if ($dbEmail <> $this->email) {
            throw new Exception("Wrong email!", 23);
        }
    }
    
    public function mail() {
        $this->makeDBRecord();
        $this->setCookie();
        $subj = "RADRF Web Services Password Restoration";
        $text = "Hello, $login!\n\n"
                . "According to our records, you requested to reset your password password .\n"
                . "Please click on <a href ='https://coalition.redalliance.pw/restore.php?hash=$this->hash'>this</a> link to change your password.\n\n"
                . "If you did not request this, please click <a href ='https://coalition.redalliance.pw/restore.php?hash=$this->hash&action=remove'>this</a> link.\n\n"
                . "Thank you,\n"
                . "RADRF Web Services.";
        $this->sendmail($subj, $text);
    }
}
