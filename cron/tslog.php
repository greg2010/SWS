<?php
$path ="/home/ts_reserve";
$link = mysql_connect('127.0.0.1', 'root', 'root');
$db = mysql_select_db('ts3-ra', $link);
$date_now = date("Y-m-d H:i:s");
                        
######################################################-TS RA LOG

$file = file_get_contents("$path/ts.log" , FILE_USE_INCLUDE_PATH);
$f = explode("\n", $file);

$u='0';
preg_match_all("/(\d{1,4}\-\d{1,2}\-\d{1,2})\s(\d{1,2}\:\d{1,2}\:\d{1,2}).*\'(.*)\'(\(.*\))\sfrom\s(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $file, $out);
foreach ($out[0] as $value){


$data=$out[1][$u];
$time=$out[2][$u];
$nick=$out[3][$u];
$ip=$out[5][$u];


$query = "SELECT `nick`  from `clients_logs` WHERE `nick` = '$nick' AND `date` = '$data' AND `time` = '$time'";
$res = mysql_query($query, $link);
if (!$res){$date = date(DATE_RFC822);    file_put_contents ("$path/error.txt", "error in Mysql select logs ts $date_now   \n", FILE_APPEND);}

$row = mysql_fetch_row($res);
    if (!$row[0]){
#########insert to BD
	$query = "INSERT INTO `clients_logs` VALUES ('', '$data', '$time', '$nick', '$ip')";
	$res = mysql_query($query, $link);

	if (!$res){$date = date(DATE_RFC822);    file_put_contents ("$path/error.txt", "error in Mysql insert clients_logs in cycle $u $date  $res \n", FILE_APPEND);}
	
	}
#################################





#echo $out[1][$u]." " .$out[2][$u]." "  .$out[3][$u]. " "  .$out[5][$u]. "\n";
#echo $u. " ".$data." " .$time." "  .$nick. " "  .$ip. "\n";
$u++;
}
########################################################


mysql_close();
?>
