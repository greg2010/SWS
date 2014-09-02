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

#protected $id;
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
    
    public function hostinfo(){
    global $tsAdmin;
    $info=$tsAdmin->clientDbList(0);
    $cl_list=$info[data];
    return $cl_list;
    }


    public function grAdditDbTs($id){
    $permissions = new permissions($id);
    $test=$permissions->getTSPermissions();
#    var_dump($test);
    return $test;
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

    public function perm_user($nick){
    global $tsAdmin;
#    $info=$tsAdmin->clientDbFind("$nick",'-uid');
    $info=$tsAdmin->clientDbFind("$nick");
    $cl_id=$info['data'][0]['cldbid'];
    $s_gr_clid=$tsAdmin->serverGroupsByClientID("$cl_id");
    $perm=$s_gr_clid['data'];
    return $perm;
    }
    
    public function setGrUser($sgid,$nick){
    global $tsAdmin;
    $cl_id_tmp=$tsAdmin->clientDbFind("$nick");
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
    

    public function delGrUser($sgid,$nick){
    global $tsAdmin;
    $cl_id_tmp=$tsAdmin->clientDbFind("$nick");
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


$ts3 = new ts3;

#$clid=$ts3->perm_user('ttc6PcBp8ufxl6zo3JGU/P5jejE=');
$clid=$ts3->perm_user('RED | AND. | Dark Angel66');
foreach($clid as $val){
echo ("$val[name] -> $val[sgid] \n");
}

$cl_p_d=$ts3->grAdditDbTs('1');
foreach ($cl_p_d as $v){
echo("\n $v \n");

}
#$sgid=array(44,41);
#$sgid=array('tmp','Allies');

#$sgid='41';
#$sgid='Allies';
#$set=$ts3->setGruser($sgid,'RED | AND. | Dark Angel66');
#$del=$ts3->delGruser($sgid,'RED | AND. | Dark Angel66');
#echo ("\n $set[success] \n");
#echo ("\n $del[success] \n");
#var_dump($set);
#var_dump($sgid);


#echo $ts3->gr_convert('Server Admin');



?>