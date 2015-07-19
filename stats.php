<?php

$thisPage = "stats";
require_once 'common.php';
include 'header.php';
$templateName = $thisPage;

$pagePermissions = array("webReg_Valid");

$stats = new statistics();
try {
    $toTemplate['regStats'] = $stats->getRegistrationStats();
} catch (Exception $ex) {
    $toTemplate["errorMsg"] = "Database error. Try again later.";
}

require 'twigRender.php';