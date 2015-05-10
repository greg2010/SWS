<?php

$thisPage = "wh";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$pagePermissions = array("webReg_Valid");

try{
    $wormholes = new wormholes($_SESSION[userObject]->getID());
    $toTemplate["WHList"] = $wormholes->getWHList();
    //var_dump($toTemplate["WHList"]);
} catch (Exception $ex){
    $toTemplate["errorMsg"] = "Database error. Try again later.";
}
require 'twigRender.php';

?>
