<?php
$thisPage = "reg";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$toTemplate['saveForm']['keyID'] = $_POST[keyID];
$toTemplate['saveForm']['vCode'] = $_POST[vCode];
$toTemplate['saveForm']['email'] = $_POST[email];
if ($_POST[form] == 'sent') {
    if (!($_SESSION[regObject] instanceof registerNewUser)) {
        throw new Exception("Getting api info step was skipped. Aborting...");
    }
    $login = $_POST[login];
    $password = $_POST[password];
    if ($_POST[email]) {
        $email = $_POST[email];
    }
    try {
        
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
            header("Location: /index.php");
        }
    }
    } catch (Exception $ex) {
        switch ($ex->getCode()) {
            case 10:
                $toTemplate["errorMsg"] = "Please fill in all fields!";
                break;
            case 15:
                $toTemplate["errorMsg"] = "There is a problem with CCP servers. Please try again later.";
                break;
            case 20:
                $toTemplate["errorMsg"] = "Please choose eligible charater.";
                break;
            case 30:
                $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue!<br> Please convey this information to server administator:" . $ex->getMessage();
                break;
        }
    }
}
require 'twigRender.php';