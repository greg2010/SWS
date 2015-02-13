<?php

$thisPage = "posmon";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
//$pagePermissions = array("webReg_Valid", "posMon_Valid");
$pagePermissions = array("webReg_Valid");

$posmon = new posmon();
try {
    $pilotInfo = $_SESSION[userObject]->getApiPilotInfo();
    $posmon->checkIfHasApiKey($pilotInfo[mainAPI][corporationID]);
    $toTemplate["posList"] = $posmon->getSortedPosList();
} catch (Exception $ex) {
    if ($ex->getCode() == 26) {
        $toTemplate["errorMsg"] = "No API key for your main corp. Please contact your CEO/director to provide one.";
    } elseif ($ex->getCode() == 27) {
        $toTemplate["errorMsg"] = "No valid API key for your main corp. Please contact your CEO/director to provide one.";
    } else {
        $toTemplate["errorMsg"] = "Database error. Try again later.";
    }
}
require 'twigRender.php';