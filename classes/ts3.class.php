<?php

class ts3{

protected $id;
public $log;
private $db;
private $permissions;
private $tsAdmin;

    public function __construct(){
    $tsAdmin=$this->connectTs();
    $tsAdmin->selectServer('10000', 'port');
    $this->tsAdmin=$tsAdmin;
    }

    public function __destruct(){
    $tsAdmin=$this->tsAdmin;
    $tsAdmin->logout();
    }    
    
    public function connectTs(){

    $ts3_ip = config::ts3_ip;
    $ts3_queryport = config::ts3_queryport;
    $ts3_user = config::ts3_user;
    $ts3_pass = config::ts3_pass;
    $date_now = date("Y-m-d H:i:s");
    
    
    $tsAdmin = new ts3admin($ts3_ip, $ts3_queryport);

    if($tsAdmin->getElement('success', $tsAdmin->connect())) {
	    $tsAdmin->login($ts3_user, $ts3_pass);
    }else{
    
	file_put_contents ("error_connect.txt", "error date: $date_now $ts3_ip:$ts3_queryport  \n", FILE_APPEND);
	sleep(1);
	for ($gt=0;$gt<3;$gt++){

	$tsAdmin = new ts3admin($ts3_ip, $ts3_queryport);

	if($tsAdmin->getElement('success', $tsAdmin->connect())) {
		    $tsAdmin->login($ts3_user, $ts3_pass);
		    break;
		    }else{
			    file_put_contents ("error_connect.txt", "error date2: $date_now $ts3_ip:$ts3_queryport  \n", FILE_APPEND);
			    sleep(1);
			    
    			}
    	    }
    	}


return $tsAdmin;
    }
    
    
    private function hostinfo(){
    $tsAdmin=$this->tsAdmin;
    $info=$tsAdmin->clientDbList(0);
    $cl_list=$info[data];
    return $cl_list;
    }

    public function nickname($id){
    $this->db=db::getInstance();
    $query = "SELECT apiPilotList.characterName, allianceList.ticker, corporationList.ticker 
    FROM apiPilotList INNER JOIN allianceList ON apiPilotList.allianceID = allianceList.id INNER JOIN corporationList ON apiPilotList.corporationID = corporationList.id 
    WHERE apiPilotList.id = $id AND apiPilotList.keyStatus = 1";
    $result = $this->db->query($query);
    $nick_raw=$this->db->fetchRow($result);
    return $nickname="{$nick_raw[1]} | {$nick_raw[2]} | {$nick_raw[0]}";
    }

    public function status(){
    $tsAdmin=$this->tsAdmin;
    $info=$tsAdmin->serverInfo();
    $status=array("status"=>$info['data']['virtualserver_status'], "online"=>($info['data']['virtualserver_clientsonline']-1));
    return $status;
    }

    public function nick_verify(){
    $tsAdmin=$this->tsAdmin;
    $this->db=db::getInstance();
    $info=$tsAdmin->clientList('-uid');
	    foreach ($info['data'] as $key=>$val){
	        if ($info['data'][$key]['client_type']=='1'){
	        unset($info['data'][$key]);
	        }else{
	    	    $uniID = $info['data'][$key]['client_unique_identifier'];
	        
	            $query = "SELECT `id` FROM `teamspeak` WHERE `uniqueID`= '$uniID'";
		    $result = $this->db->query($query);
		    $id_raw=$this->db->fetchRow($result);
		    $id=$id_raw[0];
		    if($id==NULL){
		    $clid=$info['data'][$key]['clid'];
	    	    $tsAdmin->clientKick($clid,'server','register now! fucking evilrax');
	    	    $kickusers[$key]=$uniID;
		    }else{
	    		$need_nickname=$this->nickname($id);
	    	    
	    		if($need_nickname != $info['data'][$key]['client_nickname']){
	    		$clid=$info['data'][$key]['clid'];
	    		$tsAdmin->clientKick($clid,'server','check nickname');
	    		$kickusers[$key]=$uniID;
	    		}
	    	    }
	        
	        }
	    }
    return $kickusers;
    }

    public function fullvalidate(){
    
    $tsAdmin=$this->tsAdmin;
    $this->db=db::getInstance();
    
    $query = "SELECT `id` FROM `teamspeak`";
    $result = $this->db->query($query);
    $id_raw=$this->db->fetchRow($result);
	foreach ($id_raw as $id){
	$iv++;
	$this->validate($id);
	
	}
    
    
    
    $this->syncDbTs();
    return $iv;
    }




    public function syncDbTs(){
    $tsAdmin=$this->tsAdmin;
    $this->db=db::getInstance();
    $info=$tsAdmin->clientDbList('0');
	    foreach ($info['data'] as $key=>$val){
	    	    $uniID = $info['data'][$key]['client_unique_identifier'];
	        
	            $query = "SELECT `id` FROM `teamspeak` WHERE `uniqueID`= '$uniID'";
		    $result = $this->db->query($query);
		    $id_raw=$this->db->fetchRow($result);
		    $id=$id_raw[0];
		    if($id==NULL){
		    
		    $chOnline=$tsAdmin->clientGetIds($uniID);
		    $cl_id=$info['data'][$key]['cldbid'];
		    
		    	if($chOnline['success']=false){
			$delete=$tsAdmin->clientDbDelete("$cl_id");
#			}elseif($info['success']=true){
			}elseif($chOnline['success']=true){
					    $cid=$chOnline['data'][0]['clid'];
					    $kick=$tsAdmin->clientKick($cid,'server','Need register now');
					    $delete=$tsAdmin->clientDbDelete("$cl_id");
					    }
				}else{
				
#				$delete="allOk";
				
				}
	        
	        }
#    return $delete;
    }


    public function validate($id){
    
    $ar1a=$this->grAdditDbTs($id);
    if ($ar1a==NULL){
    $ar1a=array();
    }
    $ar1m=array($this->grMaDbTs($id));
    $ar1=array_merge($ar1a,$ar1m);
    $ar2t=$this->perm_user($this->getTsUid($id));
    if($ar2t==false){
    return "user not find";
    exit;
    }
    
    foreach($ar2t as $ar2_v){
    $i2v++;
    $ar2[$i2v]=$ar2_v[sgid];
    }
    $validDb_Ts=array_diff($ar1, $ar2);
    $validTs_Db=array_diff($ar2, $ar1);
#var_dump($ar1);
#var_dump($ar2);
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
            $query = "SELECT `allianceID` FROM `apiPilotList` WHERE `id`=$id AND `keyStatus` = '1'";
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
#    var_dump($lable_ar);
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
#var_dump($r_tmp);
    return $row=array_values($row);
	}else{
    
    return $row=array('not_validate');
	}
    }


    public function gr_convert($namegr){
    $tsAdmin=$this->tsAdmin;
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
     $tsAdmin=$this->tsAdmin;
    $info=$tsAdmin->clientDbFind("$nick",'-uid');
    $cl_id=$info['data'][0]['cldbid'];
    $s_gr_clid=$tsAdmin->serverGroupsByClientID("$cl_id");
    $perm=$s_gr_clid['data'];
    return $perm;
    }


    public function getUid($nick){
     $tsAdmin=$this->tsAdmin;
    $info=$tsAdmin->clientDbFind("$nick");
    $cl_id=$info['data'][0]['cldbid'];
    $cDbInf_raw=$tsAdmin->clientDbInfo("$cl_id");
    $Uid=$cDbInf_raw['data']['client_unique_identifier'];

    return $Uid;
    }

    public function deleteTsUser($id){
     $tsAdmin=$this->tsAdmin;
    $Uid=$this->getTsUid($id);
#    file_put_contents ("deleteTS.txt", "detele Unique ID TS for $id \n", FILE_APPEND);
    $info=$tsAdmin->clientGetIds($Uid);
    $info2=$tsAdmin->clientDbFind("$Uid",'-uid');
    $cl_id=$info2['data'][0]['cldbid'];
    if ($cl_id==NULL){
    return "user not found in TS";
    exit;
    }
	if($info['success']=false){
	    $delete=$tsAdmin->clientDbDelete("$cl_id");
	    }elseif($info['success']=true){
					    $cid=$info['data'][0]['clid'];
					    $kick=$tsAdmin->clientKick($cid,'server','User delete request');
					    $delete=$tsAdmin->clientDbDelete("$cl_id");
					}else{
					
					$delete=false;
					}
    return $delete;
    }


    
    private function setGrUser($sgid,$nick){
    $tsAdmin=$this->tsAdmin;
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
    $tsAdmin=$this->tsAdmin;
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



/*
######example validate user
$ts3 = new ts3;


$wow=$ts3->validate('1');
#$wow=$ts3->perm_user('ttc6PcBp8ufxl6zo3JGU/P5jejE=');
var_dump($wow);

*/



?>