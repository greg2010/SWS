<?php
$thisPage = "standings";
require_once 'common.php';
include 'header.php';

//$pagePermissions = array("webReg_Valid");
$templateName = $thisPage;

$APIUserManagement = new APIUserManagement();
try {
$toTemplate["standings"] = $APIUserManagement->getAllianceStandings();
} catch (Exception $ex) {
    echo $ex->getMessage();
    $toTemplate["errorMsg"] = "Database error. Try again later.";
}

require 'twigRender.php';