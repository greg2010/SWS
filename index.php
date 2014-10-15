<?php
$thisPage = "index";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$pagePermissions = array("webReg_Valid");

if ($_SESSION[successLogin]) {
    $toTemplate['successLogin'] = $_SESSION[successLogin];
    unset($_SESSION[successLogin]);
}
//$ts3 = new ts3();
//$tsStats = $ts3->status();
$toTemplate['ts']['status'] = $tsStats[status];
$toTemplate['ts']['online'] = $tsStats[online];

$api = new APIUserManagement();
$serverStatus = $api->getServerStatus();
$toTemplate['eve']['status'] = $serverStatus[status];
$toTemplate['eve']['online'] = $serverStatus[online];

require 'twigRender.php';