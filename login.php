<?php
$thisPage = "login";
include 'header.php';

$templateName = "login";
require_once 'auth.php';
$pagePermissions = array();

$loginFormSent = $_POST[loginFormSent];
$method = $_SERVER[REQUEST_METHOD];
if ($loginFromSent === 'True' AND $method <> 'POST' OR $loginFromSent <> 'True' AND $method === 'POST') {
    header("Location: /login.php");
} else {
    $login = $_POST[login];
    $password = $_POST[password];
    $_SESSION[user]->logUserByLoginPass($login, $password);
}
$_SESSION[user]->preparePage($pagePermissions);

$toTemplate['hasAccess'] = $_SESSION[user]->hasPermission();

require 'twigRender.php';