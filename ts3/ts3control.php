<?php
$path=dirname(__FILE__);
#$pid=getmypid();
$date_now = date("Y-m-d H:i:s");
require_once 'config.php';
require_once("../classes/ts3admin.class.php");
require_once("../classes/permissions.class.php");
require_once("../classes/db.class.php");
require_once("../classes/config.class.php");
$tsAdmin = new ts3admin($ts3_ip, $ts3_queryport);
if($tsAdmin->getElement('success', $tsAdmin->connect())) {
$tsAdmin->login($ts3_user, $ts3_pass);
}else{
file_put_contents ("$path/error_connect.txt", "error date: $date_now $ts3_ip:$ts3_queryport  \n", FILE_APPEND);
sleep(1);

	$tsAdmin = new ts3admin($ts3_ip, $ts3_queryport);
	if($tsAdmin->getElement('success', $tsAdmin->connect())) {
	$tsAdmin->login($ts3_user, $ts3_pass);
	}else{
	file_put_contents ("$path/error_connect.txt", "error date2: $date_now $ts3_ip:$ts3_queryport  \n", FILE_APPEND);
	sleep(1);
        }

    
    }



class ts3{

protected $id;
public $log;
private $db;
private $permissions;



    public function __construct(){
    global $tsAdmin;    
    $tsAdmin->selectServer('10000', 'port');
    }

    public function __destruct(){
    global $tsAdmin;
    $tsAdmin->logout();
    }    
    
    private function hostinfo(){
    global $tsAdmin;
    $info=$tsAdmin->clientDbList(0);
    $cl_list=$info[data];
    return $cl_list;
    }

    public function validate($id){
    
    $ar1a=$this->grAdditDbTs($id);
    $ar1m=array($this->grMaDbTs($id));
    $ar1=array_merge($ar1a,$ar1m);
    $ar2t=$this->perm_user($this->getTsUid($id));
    foreach($ar2t as $ar2_v){
    $i2v++;
    $ar2[$i2v]=$ar2_v[sgid];
    }
    $validDb_Ts=array_diff($ar1, $ar2);
    $validTs_Db=array_diff($ar2, $ar1);

    if (!in_array('not_validate', $ar1)){

    if (count($validDb_Ts)!='0'){

	foreach($validDb_Ts as $sgids){
#	echo ("set:$sgids\n");
	
        $set=$this->setGruser($sgids,$this->getTsUid($id));
	}
    }

    if (count($validTs_Db)!='0'){
    
    	foreach($validTs_Db as $sgidd){
#	echo ("del:$sgidd\n");
        $del=$this->delGruser($sgidd,$this->getTsUid($id));
	}
    }

    }else{

    foreach($ar2 as $sgiddnv){
#	echo ("del:$sgiddnv\n");
        $del=$this->delGruser($sgiddnv,$this->getTsUid($id));
				}

        }

    return true;
    
    }




    private function getGrMainDbTs($allianceID){
	    $this->db=db::getInstance();
            $query = "SELECT `TSGroupID` FROM `mainTSGroupID` WHERE `allianceID` = '$allianceID'";
            $result = $this->db->query($query);
            return $this->db->getMysqlResult($result);
    }

    private function alliUser($id){
	    $this->db=db::getInstance();
            $query = "SELECT `allianceID` FROM `pilotInfo` WHERE `id`=$id";
            $result = $this->db->query($query);
            return $this->db->getMysqlResult($result);
    }

    private function getTsUid($id){
	    $this->db=db::getInstance();
            $query = "SELECT `uniqueID` FROM `teamspeak` WHERE `id`=$id";
            $result = $this->db->query($query);
            return $this->db->getMysqlResult($result);
    }


    private function grMaDbTs($id){
#	    $this->db=db::getInstance();
            return $this->getGrMainDbTs($this->alliUser($id));
    }


    private function grAdditDbTs($id){
    $permissions = new permissions($id);
    $this->db=db::getInstance();
    $lable_ar=$permissions->getTSPermissions();
    if(in_array('TS_Valid', $lable_ar)){
    foreach ($lable_ar as $lable_str){
    $i++;
	    $query = "SELECT `TSGroupID` FROM `additionalTSGroupID` WHERE `bitName` = '$lable_str'";
#	    echo ("\n $query \n");
	    $result = $this->db->query($query);
	    $r_tmp=$this->db->fetchRow($result);
		if($r_tmp[0]!=''){
		$row[$i]=$r_tmp[0];
		}
	    }

    return $row=array_values($row);
	}else{
    
    return $row=array('not_validate');
	}
    }


    public function gr_convert($namegr){
    global $tsAdmin;
    $s_gr_l=$tsAdmin->serverGroupList();
    foreach ($s_gr_l[data] as $val){
	if($val[name] == $namegr){
	$sgid_t=$val[sgid];
				}else{
				$sgid_tf='Not group found';
				}
	
	}
	if($sgid_t!=NULL&&$sgid_t!=''&&$sgid_t!='1'&&$sgid_t!='2'&&$sgid_t!='3'&&$sgid_t!='4'&&$sgid_t!='5'){
    return $sgid_t;
	}else{
	return $sgid_tf;
	}
    }

    private function perm_user($nick){
    global $tsAdmin;
    $info=$tsAdmin->clientDbFind("$nick",'-uid');
#    $info=$tsAdmin->clientDbFind("$nick");
    $cl_id=$info['data'][0]['cldbid'];
    $s_gr_clid=$tsAdmin->serverGroupsByClientID("$cl_id");
    $perm=$s_gr_clid['data'];
    return $perm;
    }
    
    private function setGrUser($sgid,$nick){
    global $tsAdmin;
    $cl_id_tmp=$tsAdmin->clientDbFind("$nick", '-uid');
    $cl_id=$cl_id_tmp['data'][0]['cldbid'];
    if(is_array($sgid)){
        foreach($sgid as $val){
    $type_sg= preg_match("/^(\d+)$/",$val);
    if($type_sg=='1'){
    	$info=$tsAdmin->serverGroupAddClient("$val","$cl_id");
    	}else{
    		$val=$this->gr_convert("$val");
        	$info=$tsAdmin->serverGroupAddClient("$val","$cl_id");
		}
	}

    
    }else{
    
        $type_sg= preg_match("/^(\d+)$/",$sgid);
    if($type_sg=='1'){
    	$info=$tsAdmin->serverGroupAddClient("$sgid","$cl_id");
    	}else{
    		$sgid=$this->gr_convert("$sgid");
        	$info=$tsAdmin->serverGroupAddClient("$sgid","$cl_id");
		}
    
        }
    return $info;
    }
    

    private function delGrUser($sgid,$nick){
    global $tsAdmin;
    $cl_id_tmp=$tsAdmin->clientDbFind("$nick", '-uid');
    $cl_id=$cl_id_tmp['data'][0]['cldbid'];
    if(is_array($sgid)){
        foreach($sgid as $val){
    $type_sg= preg_match("/^(\d+)$/",$val);
    if($type_sg=='1'){
    	$info=$tsAdmin->serverGroupDeleteClient("$val","$cl_id");
    	}else{
    		$val=$this->gr_convert("$val");
        	$info=$tsAdmin->serverGroupDeleteClient("$val","$cl_id");
		}
	}

    
    }else{
    
        $type_sg= preg_match("/^(\d+)$/",$sgid);
    if($type_sg=='1'){
    	$info=$tsAdmin->serverGroupDeleteClient("$sgid","$cl_id");
    	}else{
    		$sgid=$this->gr_convert("$sgid");
        	$info=$tsAdmin->serverGroupDeleteClient("$sgid","$cl_id");
		}
    
        }
    return $info;
    }
    


}




######example validate user
$ts3 = new ts3;


$wow=$ts3->validate('1');
#$wow=$ts3->perm_user('ttc6PcBp8ufxl6zo3JGU/P5jejE=');
var_dump($wow);





?>