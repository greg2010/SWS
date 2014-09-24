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
    try {
        $_SESSION[userObject]->logUserByLoginPass($login, $password);
        if ($_POST[remember] == 1) {
            $_SESSION[userObject]->setCookieForUser();
        }
        header("Location: /index.php");
    } catch (Exception $ex) {
        $toTemplate['saveform']['login'] = $login;
        switch ($ex->getCode()) {
            case 11:
                $toTemplate['errorMsg'] = "Login failed. Please check your login or password.";
                break;
            case 30:
                $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                break;
        }
    }
    $toTemplate['loggedIn'] = $_SESSION[userObject]->isLoggedIn();
}
//$_SESSION[userObject]->preparePage($pagePermissions);

//$toTemplate['hasAccess'] = $_SESSION[userObject]->hasPermission();
require 'twigRender.php';