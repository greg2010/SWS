<?php

require_once dirname(__FILE__) . '/../init.php';

$thread_max = config::num_of_threads;
$users_max = config::ops_in_thread;

$pid = -2;
if($pid == -2){
	try{
		$apiOrgManagement = new APIOrgManagement();
		$userList = $apiOrgManagement->getAllianceList();

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

		$tolog = $users_count . " alliances, " . $thread_count . " threads, " . $users_in_thread . " alliances each";
	} catch (\Pheal\Exceptions\PhealException $e){
    	$tolog = "err " . $e->getMessage();
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
			$drake = $apiOrgManagement->updateAllianceList($userList[$i]);
			if($drake != NULL){
				$log->merge($drake, $userList[$i][id]);
				$emt = round(microtime(1)*1000) - $smt;
				$log->put("spent", $emt . " milliseconds", $userList[$i][id]);
			}
		}
		$emta = round(microtime(1)) - $smta;
		if($log->get() != NULL){
			$log->put("get", "thread " . $t . ", " . $tolog);
			$log->put("total spent", $emta . " seconds");
		} else{
			$log->put("ok", $emta . " seconds, " . "thread " . $t . ", " . $tolog);
		}
		$log->record("log.updateOrgList");
		posix_kill(posix_getpid(), SIGTERM);
	}
}

?>
