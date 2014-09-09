<?php

use Pheal\Pheal;
use Pheal\Core\Config;

class assetList {

    protected $keyInfo = array();
    private $log;
    private $db;
    private $poslist = array();

	public function __construct($keyInfo = array()){
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->keyInfo = $keyInfo;
        Config::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        Config::getInstance()->access = new \Pheal\Access\StaticCheck();
    }

    public function updateSiloList(){
        return $this->log->get();
    }

}

?>
