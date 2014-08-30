<?php

/*interface Ilogging {
    function put($txt);
    function get();
}*/

class logging {//implements Ilogging {
    private $log;

	/*public function __construct(){
        
    }*/

    public function put($txt, $newline=true){
    	$this->log .= ($newline) ? ($txt . "\n") : ($txt);
    }

    public function addN(){
    	$this->log .= "\n";
    }

    public function rm(){
    	$this->log = "";
    }

    public function get(){
    	return $this->log;
    }

    public function init(){
    	$this->log = "[" . str_repeat("=",35) . date("Y-m-d H:i:s") . str_repeat("=",35) . "]\n";
    }
}

?>
