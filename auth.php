<?php
$toTemplate = array();
require_once 'init.php';
session_start();
$db = db::getInstance();
if (!($_SESSION[userObject] instanceof userSession)) {
    $_SESSION['userObject'] = new userSession();
}

if (!($_SESSION[logObject] instanceof userLogging)) {
    $_SESSION['logObject'] = new userLogging();
}

//var_dump($_SESSION);
$toTemplate['loggedIN'] = $_SESSION[userObject]->isLoggedIn();
//$_SESSION[userObject]->initialize();
if ($toTemplate['loggedIN'] == 1) {
    $pilotInfo = $_SESSION[userObject]->getApiPilotInfo();
    $toTemplate['characterName'] = $pilotInfo[mainAPI][characterName];
    $toTemplate['characterID'] = $pilotInfo[mainAPI][characterID];
} else {
    $_SESSION[userObject]->logUserByCookie();
}