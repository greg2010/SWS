<?php
$toTemplate = array();
require_once 'init.php';
session_start();
$db = db::getInstance();
if (!($_SESSION[userObject] instanceof userSession)) {
    $_SESSION['userObject'] = new userSession();
}
//var_dump($_SESSION);
$toTemplate['loggedIN'] = $_SESSION[userObject]->isLoggedIn();
//$_SESSION[userObject]->initialize();
if ($toTemplate['loggedIN'] == 1) {
    $pilotInfo = $_SESSION[userObject]->getPilotInfo();
    $toTemplate['characterName'] = $pilotInfo[characterName];
    $toTemplate['characterID'] = $pilotInfo[characterID];
}