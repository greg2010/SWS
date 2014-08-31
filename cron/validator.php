<?php

require_once dirname(__FILE__) . '/../init.php';

$smta = round(microtime(1)*1000);

$db = db::getInstance();

$log = new logging();
try{
	$query = "SELECT * FROM `users`";
	$result = $db->query($query);
	$userList = $db->fetchAssoc($result);
	$log->put("users", "select " . count($userList) . " users");
} catch (Exception $ex){
    $log->put("users", "select fail: " . $ex->getMessage());
}
for($i=0; $i<count($userList); $i++){
	$smt = round(microtime(1)*1000);
	$log->initSub($userList[$i][id]);
	$log->put("user", $userList[$i][login] . " (id: " . $userList[$i][id] . ")", $userList[$i][id]);
	$user = new validation($userList[$i][id], $userList[$i][accessMask]);
	$log->merge($user->verifyApiInfo(), $userList[$i][id]);
	$emt = round(microtime(1)*1000) - $smt;
	$log->put("spent", $emt . " microseconds", $userList[$i][id]);
}
$emta = round(microtime(1)*1000) - $smta;
$log->put("total spent", $emta . " microseconds");
//var_dump($log->get());
$log->record("log.validator");
?>
