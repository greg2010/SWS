<?php

require_once dirname(__FILE__) . '/../init.php';

$smta = round(microtime(1)*1000);

$db = db::getInstance();

$log = new logging();
$log->init();

try{
	$query = "SELECT * FROM `users`";
	$result = $db->query($query);
	$userList = $db->fetchAssoc($result);
	$log->put("Select " . count($userList) . " users");
} catch (Exception $ex){
    $log->put("Select fail: " . $ex->getMessage());
}
for($i=0; $i<count($userList); $i++){
	$smt = round(microtime(1)*1000);
	$log->addN();
	$log->put("User: " . $userList[$i][login] . " (id: " . $userList[$i][id] . ")");
	$user = new validation($userList[$i][id], $userList[$i][accessMask]);
	$log->put($user->verifyApiInfo(), false);
	$emt = round(microtime(1)*1000) - $smt;
	$log->put("Spent " . $emt . " microseconds.");
}
$emta = round(microtime(1)*1000) - $smta;
$log->addN();
$log->put("Total spent " . $emta . " microseconds.");
echo $log->get();

?>
