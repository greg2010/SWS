<?php
$thisPage = "login";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
//$pagePermissions = array();

$loginFormSent = $_POST[form];
$method = $_SERVER[REQUEST_METHOD];
if ($method <> 'POST' AND $loginFromSent === 'sent') {
    header("Location: /login.php");
}
if ($loginFormSent == 'sent') {
    $login = $_POST[login];
    $password = $_POST[password];
    $_SESSION[userObject]->logUserByLoginPass($login, $password);
    $toTemplate['loggedIn'] = $_SESSION[userObject]->isLoggedIn();
    if ($toTemplate[loggedIn] == TRUE) {
        $toTemplate['success'] = 1;
        header("Location: /index.php");
    } else {
        $toTemplate['success'] = 0;
    }
    if ($_POST[remember] == 1 && $toTemplate[loggedIn] == TRUE) {
        $_SESSION[userObject]->setCookieForUser();
    }
}
//$_SESSION[userObject]->preparePage($pagePermissions);

//$toTemplate['hasAccess'] = $_SESSION[userObject]->hasPermission();
//$_SESSION[userObject]->test = 0;
//var_dump($_SESSION[userObject]);
require 'twigRender.php';