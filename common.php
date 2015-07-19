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

$toTemplate['loggedIN'] = $_SESSION[userObject]->isLoggedIn();
if ($toTemplate[loggedIN] == 0) {
    $_SESSION[userObject]->logUserByCookie();
}


if (isset($_SESSION[successMsg])) {
    $toTemplate['successMsg'] = $_SESSION[successMsg];
    unset($_SESSION[successMsg]);
}