<?php

$thisPage = "posmon";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
//$pagePermissions = array("webReg_Valid", "posMon_Valid");
$pagePermissions = array("webReg_Valid");

$posmon = new posmon();

$toTemplate["posList"] = $posmon->getSortedPosList();
//print_r($toTemplate[posList]);
require 'twigRender.php';