<?php

$thisPage = "posmon";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
//$pagePermissions = array("webReg_Valid", "posMon_Valid");
$pagePermissions = array("webReg_Valid");

$posmon = new posmon();
try { 
    
$toTemplate["posList"] = $posmon->getSortedPosList();
} catch (Exception $ex) {
    $toTemplate["errorMsg"] = "Database error. Try again later.";
}
//print_r($toTemplate[posList]);
require 'twigRender.php';