<?php

require_once dirname(__FILE__) . '/../init.php';

$thread_max = config::num_of_threads;
$users_max = config::ops_in_thread;

$pid = -2;
if($pid == -2){
	try{
		$connection = mysqli_connect(config::hostname, config::username, config::password);
        $selectdb = mysqli_select_db($connection, config::database);
        $query = "SELECT * FROM `apiPilotList` WHERE `keyStatus` > 0";
		$result = mysqli_query($connection, $query);
		while($array = mysqli_fetch_assoc($result)){
			if(($array[accessMask] & 49152) > 0) $userList[] = $array;
		}
        mysqli_close($connection);

        $users_count = count($userList);
        if($users_count > $users_max){
        	$thread_count = ($users_count < $users_max) ? 1 : round($users_count / $users_max);
        	if($thread_count > $thread_max){
        		$thread_count = $thread_max;
        		$users_in_thread = round($users_count / $thread_count);
        	} else $users_in_thread = $users_max;
        } else{
        	$thread_count = 1;
        	$users_in_thread = $users_count;
        }

		$tolog = $users_count . " api keys, " . $thread_count . " threads, " . $users_in_thread . " keys each";
	} catch (Exception $ex){
    	$tolog = "err " . $ex->getMessage();
	}
}
for($t=0; $t<$thread_count; $t++){
	$pid = pcntl_fork();
	if($pid==-1) die("Error: impossible to pcntl_fork()\n");
	else if($pid) $pid_arr[$t] = $pid;
	else if(!$pid){
		$smta = round(microtime(1));
		$log = new logging();
		$users_first = $t*$users_in_thread;
		$users_last = ($t==($thread_count-1)) ? ($users_count) : (($t+1)*$users_in_thread);
		for($i=$users_first; $i<$users_last; $i++){
			$smt = round(microtime(1)*1000);
			$notification = new notif_get($userList[$i]);
			$drake = $notification->processNotif();
			if($drake != NULL){
				$log->merge($drake, $userList[$i][characterID]);
				$log->put("name", $userList[$i][characterName], $userList[$i][characterID]);
				$emt = round(microtime(1)*1000) - $smt;
				$log->put("spent", $emt . " milliseconds", $userList[$i][characterID]);
			}
		}
		$emta = round(microtime(1)) - $smta;
		if($log->get() != NULL){
			$log->put("select keys", $tolog);
			$log->put("total spent", $emta . " seconds");
		} else{
			$log->put("ok", $emta . " seconds, " . $tolog);
		}
		$log->record("log.notifications");
		posix_kill(posix_getpid(), SIGTERM);
	}
}

foreach ($pid_arr as $pid) pcntl_waitpid($pid, $status);

$smta = round(microtime(1));
$log = new logging();

$connection = mysqli_connect(config::hostname, config::username, config::password);
$selectdb = mysqli_select_db($connection, config::database);
$query = "SELECT `id`, `accessMask`, `settingsMask`, `email`, `lastNotifID`, `login` FROM `users` WHERE ((`settingsMask` & 1) OR (`settingsMask` & 2)) AND (`banID`=0) AND (`accessMask` & 1024)";
$result = mysqli_query($connection, $query);
while($array = mysqli_fetch_assoc($result)) $users[] = $array;
mysqli_close($connection);

foreach ($users as $user) {
	try{
		$notification = new notif_send($user);
	} catch (Exception $ex){	
		$log->put("id", $user[id], $user[id]);
		$log->put("exception", $ex->getMessage(), $user[id]);
	}
}

if($log->get() != NULL){
	$emta = round(microtime(1)) - $smta;
	$log->put("total spent", $emta . " seconds");
	$log->record("log.notifications");
}
exit();

?>
