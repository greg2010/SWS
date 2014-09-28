<?php
$thisPage = "index";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$pagePermissions = array("webReg_Valid");

//$ts3 = new ts3();
//$tsStats = $ts3->status();
$toTemplate['ts']['status'] = $tsStats[status];
$toTemplate['ts']['online'] = $tsStats[online];

require 'twigRender.php';