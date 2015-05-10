<?php
require_once 'init.php';
session_start();

$posmon = new posmon();
try{
	$wormholes = new wormholes($_GET[userID]);
	$wormholes->updateWH($_GET[wh_id], $_GET[ID], $_GET[Type], $_GET[System], $_GET[Leads], $_GET[Life], $_GET[Mass]);
} catch (Exception $ex){
    //13,30
}
header("Location: /wh.php"); //redirect

?>
