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
    	($sub) ? ($this->log[$sub][$key] = $value) : ($this->log[$key] = $value);
	}

    public function merge($arr, $sub=NULL){
    	($sub) ? ($this->log[$sub] = array_merge($arr, $this->log[$sub])) : ($this->log = array_merge($arr, $this->log));
    }

    public function initSub($key){
    	$this->log[$key] = array();
    }

    public function rm(){
    	$this->log = NULL;
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
