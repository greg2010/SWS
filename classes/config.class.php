<?php

class config {
    public $hostname;
    public $username;
    public $password;
    public $database;

    /*public $jhost;
    public $jport;
    public $juser;
    public $jpwd;
    public $jdomain;*/
    
    //function __construct($hostname = NULL, $username = NULL, $password = NULL, $database = NULL, $jhost = NULL, $jport = NULL, $juser = NULL, $jpwd = NULL, $jdomain = NULL) {
    function __construct($hostname = NULL, $username = NULL, $password = NULL, $database = NULL) {
        $this->hostname = !empty($hostname) ? $hostname : "";
        $this->username = !empty($username) ? $username : "";
        $this->password = !empty($password) ? $password : "";
        $this->database = !empty($database) ? $database : "";
        
        /*$this->jhost = !empty($jhost) ? $jhost : "";
        $this->jport = !empty($jport) ? $jport : "";
        $this->juser = !empty($juser) ? $juser : "";
        $this->jpwd = !empty($jpwd) ? $jpwd : "";
        $this->jdomain = !empty($jdomain) ? $jdomain : "";*/
    }
    function __destruct() { 
    }
}

?>
