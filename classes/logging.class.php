<?php

/*interface Ilogging {
    function put($txt);
    function get();
}*/

class logging {//implements Ilogging {
    private $log;

	public function __construct(){
        
    }

    public function put($txt, $newline=true){
    	$this->log .= ($newline) ? ($txt . "\n") : ($txt);
    }

    public function get(){
    	return $this->log;
    }
}

?>
