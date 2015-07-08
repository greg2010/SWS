<?php
require_once 'init.php';
session_start();


try{
	$wormholes = new wormholes($_SESSION[userObject]->getID());
	$wormholes->updateWH($_GET[wh_id], $_GET[ID], $_GET[Type], $_GET[System], $_GET[Leads], $_GET[Life], $_GET[Mass]);
} catch (Exception $ex){
	if ($ex->getCode() == 31){
        $toTemplate["errorMsg"] = "Invalid signature id!";
    } elseif ($ex->getCode() == 32){
        $toTemplate["errorMsg"] = "Invalid wormhole type!";
    } elseif ($ex->getCode() == 33){
        $toTemplate["errorMsg"] = "Invalid system name!";
    } elseif ($ex->getCode() == 34){
        $toTemplate["errorMsg"] = "Invalid target system name!";
    } else{
        $toTemplate["errorMsg"] = "Database error. Try again later.";
    }
}
header("Location: /wh.php"); //redirect

?>
