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
		while($array = mysqli_fetch_assoc($result)) $userList[] = $array;
		$query = "SELECT * FROM `apiCorpList` WHERE `keyStatus` > 0";
		$result = mysqli_query($connection, $query);
		while($array = mysqli_fetch_assoc($result)) $apiCorpList[] = $array;
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

        $corp_count = count($apiCorpList);
        $corp_in_thread = round($corp_count / $thread_count);

		$tolog = $users_count . " users, " . $corp_count . " corps, " . $thread_count . " threads, " . ($users_in_thread + $corp_in_thread) . " keys each";
	} catch (Exception $ex){
    	$tolog = "err " . $ex->getMessage();
	}
}

for($t=0; $t<$thread_count; $t++){
	$pid = pcntl_fork();
	if(!$pid){
		$smta = round(microtime(1));
		$log = new logging();

		$users_first = $t*$users_in_thread;
		$users_last = ($t==($thread_count-1)) ? ($users_count) : (($t+1)*$users_in_thread);
		for($i=$users_first; $i<$users_last; $i++){
			$smt = round(microtime(1)*1000);
			$user = new validation();
			$drake = $user->verifyPilotApiInfo($userList[$i]);
			if($drake != NULL){
				$log->merge($drake, $userList[$i][characterID]);
				$log->put("name", $userList[$i][characterName], $userList[$i][characterID]);
				$emt = round(microtime(1)*1000) - $smt;
				$log->put("spent", $emt . " milliseconds", $userList[$i][characterID]);
			}
		}

		$corp_first = $t*$corp_in_thread;
		$corp_last = ($t==($thread_count-1)) ? ($corp_count) : (($t+1)*$corp_in_thread);
		for($i=$corp_first; $i<$corp_last; $i++){
			$smt = round(microtime(1)*1000);
			$corp = new validation();
			$drake = $corp->verifyCorpApiInfo($apiCorpList[$i]);
			if($drake != NULL){
				$log->merge($drake, $apiCorpList[$i][keyID]);
				$log->put("id", $apiCorpList[$i][corporationID], $apiCorpList[$i][keyID]);
				$emt = round(microtime(1)*1000) - $smt;
				$log->put("spent", $emt . " milliseconds", $apiCorpList[$i][keyID]);
			}
		}

		$emta = round(microtime(1)) - $smta;
		if($log->get() != NULL){
			$log->put("select keys", $tolog);
			$log->put("total spent", $emta . " seconds");
		} else{
			$log->put("ok", $emta . " seconds, " . $tolog);
		}
		$log->record("log.validator");
		posix_kill(posix_getpid(), SIGTERM);
	}
}

?>
