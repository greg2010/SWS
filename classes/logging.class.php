<?php

/*interface Ilogging {
    function put($txt);
    function get();
}*/

class logging {//implements Ilogging {
    private $log = array();

	/*public function __construct(){
        
    }*/

    public function put($key, $value, $sub=NULL){
    	if($sub){
    		if($this->log[$sub] == NULL) $this->log[$sub] = array();
    		$this->log[$sub][$key] = $value;
    	} else{
    		$this->log[$key] = $value;
    	}
	}

    public function merge($arr, $sub=NULL){
    	if($sub){
    		if($this->log[$sub] == NULL) $this->log[$sub] = array();
    		$this->log[$sub] = array_merge($arr, $this->log[$sub]);
    	} else{
    		$this->log = array_merge($arr, $this->log);
    	}
    }

    public function get(){
    	return $this->log;
    }

    public function record($table){
    	try{
    		$db = db::getInstance();
    		$date = date("Y-m-d H:i:s");
    		$text = addslashes(yaml_emit($this->log));
    		$query = "INSERT INTO `$table` (`date`, `text`) VALUES ('$date','$text')";
			$result = $db->query($query);
		} catch (Exception $ex){
    		echo $ex->getMessage();
		}
    }
}

?>
