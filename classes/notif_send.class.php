<?php

class notif_send {

    public $log;
    private $db;

	public function __construct(){
        $this->db = db::getInstance();
        $this->log = new logging();
    }
}

?>
