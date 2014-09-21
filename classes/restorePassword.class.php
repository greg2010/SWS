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
    $this->hash = hash(config::cookie_hash_type, $this->code);
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
        $headers .= "From: StainWagon Web Services <mailer@stainwagon.com>\r\n";
        $headers .= "Reply-to: No-Reply <mailer@stainwagon.com>\r\n";
        mail($this->email, $subject, $text, $headers);
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
            throw new Exception("No email for this user. Please contact your CEO / director to change password.", 22);
        }
        if ($dbEmail <> $this->email) {
            throw new Exception("Wrong email!", 23);
        }
    }
    
    public function mail() {
        $this->makeDBRecord();
        $this->setCookie();
        $subj = "SW Web Services Password Restoration";
        $text = "Hello, $login!\n\n"
                . "According to our records, you forgot your password from StainWagon WebServices.\n"
                . "Please click on <a href ='https://stainwagon.com/restore.php?hash=$this->hash'>this</a> link to change your password.\n\n"
                . "If you did not request this, please click <a href ='https://stainwagon.com/restore.php?hash=$this->hash&action=remove'>this</a> link.\n\n"
                . "Thank you,\n"
                . "StainWagon Web Services.";
        $this->sendmail($subj, $text);
    }
}
