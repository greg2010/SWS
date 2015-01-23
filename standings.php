<?php
$thisPage = "standings";
require_once 'auth.php';
include 'header.php';

//$pagePermissions = array("webReg_Valid");
$templateName = $thisPage;

$APIUserManagement = new APIUserManagement();
$toTemplate["standings"] = $APIUserManagement->getAllianceStandings();


require 'twigRender.php';