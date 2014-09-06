<?php

require_once dirname(__FILE__) . '/../init.php';

$thread_max = config::num_of_threads;
$users_max = config::ops_in_thread;

$pid = -2;
if($pid == -2){
	try{
		$connection = mysqli_connect(config::hostname, config::username, config::password);
        $selectdb = mysqli_select_db($connection, config::database);
        $query = "SELECT `characterID`, `keyID`, `vCode` FROM `apiList` WHERE `keyStatus` > 0";
		$result = mysqli_query($connection, $query);
		while($array = mysqli_fetch_assoc($result)) $userList[] = $array;
		$query = "SELECT `characterID`, `characterName`, `corporationID`, `allianceID` FROM `pilotInfo`";
		$result = mysqli_query($connection, $query);
		while($array = mysqli_fetch_assoc($result)) $tmpArr[] = $array;
        mysqli_close($connection);

        for($i=0; $i<count($userList); $i++){
        	foreach($tmpArr as $row){
        		if($row[characterID] == $userList[$i][characterID]){
        			$userList[$i][characterName] = $row[characterName];
        			$userList[$i][corporationID] = $row[corporationID];
        			$userList[$i][allianceID] = $row[allianceID];
        			break;
        		}
        	}
        }

        $users_count = count($userList);
        if($users_count > $max_users){
        	$thread_count = ($users_count < $users_max) ? 1 : round($users_count / $users_max);
        	if($thread_count > $thread_max){
        		$thread_count = $thread_max;
        		$users_in_thread = round($users_count / $thread_count);
        	} else $users_in_thread = $users_max;
        } else $thread_count = 1;

		$tolog = "select " . $users_count . " api keys, run " . $thread_count . " threads " . $users_in_thread . " keys each";
	} catch (Exception $ex){
    	$tolog = "select fail: " . $ex->getMessage();
	}
}
for($t=0; $t<$thread_count; $t++){
	$pid = pcntl_fork();
	if(!$pid){
		$smta = round(microtime(1));
		$log = new logging();
		$log->put("api keys", $tolog);
		$users_first = $t*$users_in_thread;
		$users_last = ($t==($thread_count-1)) ? ($users_count) : (($t+1)*$users_in_thread);
		for($i=$users_first; $i<$users_last; $i++){
			$smt = round(microtime(1)*1000);
			$log->put("key", "user: " . $userList[$i][characterName] . " (keyID: " . $userList[$i][keyID] . ")", $userList[$i][keyID]);
			$notification = new notifications($userList[$i][keyID], $userList[$i][vCode], $userList[$i][characterID], $userList[$i][corporationID], $userList[$i][allianceID]);
			$log->merge($notification->processNotif(), $userList[$i][keyID]);
			$emt = round(microtime(1)*1000) - $smt;
			$log->put("spent", $emt . " microseconds", $userList[$i][keyID]);
		}
		$emta = round(microtime(1)) - $smta;
		$log->put("total spent", $emta . " seconds");
		$log->record("log.notifications");
		posix_kill(posix_getpid(), SIGTERM);
	}
}

?>
