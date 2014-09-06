<?php

class starbases {

    protected $keyID;
    protected $vCode;
    private $log;
    private $db;

	public function __construct($keyID, $vCode){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyID = $keyID;
        $this->vCode = $vCode;
    }

    public function updateStarbaseDetail(){
        return $this->log->get();
    }

    public function updateStarbaseList(){
        return $this->log->get();
    }
}

?>
