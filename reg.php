<?php
$thisPage = "reg";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;

if ($_POST[form] == 'sent') {
    if (!($_SESSION[regObject] instanceof registerNewUser)) {
        throw new Exception("Getting api info step was skipped. Aborting...");
    }
    $login = $_POST[login];
    $password = $_POST[password];
    if ($_POST[email]) {
        $email = $_POST[email];
    }
    $_SESSION[regObject]->setUserData($login, $password, $email);
    
    $success = $_SESSION[regObject]->register();
    if ($success) {
        unset($_SESSION[regObject]);
        $_SESSION[userObject]->logUserByLoginPass($login, $password);
        $toTemplate['loggedIn'] = $_SESSION[userObject]->isLoggedIn();
        if (!$toTemplate['loggedIn']) {
            //Something went wrong...
        } else {
            $_SESSION[userObject]->setCookieForUser();
        }
    }
}
require 'twigRender.php';