<?php
$toTemplate = array();
require_once 'init.php';
session_start();
$db = db::getInstance();
if (!($_SESSION[userObject] instanceof userSession)) {
    $_SESSION['userObject'] = new userSession();
}
$toTemplate['loggedIN'] = $_SESSION[userObject]->isLoggedIn();
if ($toTemplate['loggedIN'] == 1) {
    $userInfo = $_SESSION[userObject]->getPilotInfo();
    $toTemplate['characterName'] = $userInfo[characterName];
    $toTemplate['characterID'] = $userInfo[characterID];
}