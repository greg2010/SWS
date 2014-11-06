<?php

class logging {
    private $log = array();

    public function put($key, $value, $sub=NULL){
    	if(isset($key)){
            if($sub){
                if($this->log[$sub] == NULL) $this->log[$sub] = array();
                $this->log[$sub][$key] = $value;
    	   } else{
    	       $this->log[$key] = $value;
    	   }
        }
	}

    public function merge($arr, $sub=NULL){
        if($arr != NULL){
    	   if(isset($sub)){
    	       if($this->log[$sub] == NULL) $this->log[$sub] = array();
    	       $this->log[$sub] = array_merge($arr, $this->log[$sub]);
    	   } else{
    	       $this->log = array_merge($arr, $this->log);
    	   }
        }
    }

    public function get($rm=false){
    	$lg = $this->log;
    	if($rm) $this->rm();
    	return $lg;
    }

    public function rm($key=NULL){
        if (isset($key)) unset($this->log[$key]); else unset($this->log);
    }

    public function record($table){
    	try{
            $db = db::getInstance();
    		$date = date("Y-m-d H:i:s");
    		$text = ($this->log[ok]) ? ("ok: " . $this->log[ok]) : addslashes(yaml_emit($this->log));
    		$query = "INSERT INTO `$table` (`date`, `text`) VALUES ('$date','$text')";
			if(($this->log[ok] && config::ok_message_in_log) || (!$this->log[ok])) $result = $db->query($query);
		} catch (Exception $ex){
    		echo $ex->getMessage();
		}
    }
}

?>
