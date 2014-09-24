<?php
$thisPage = "reg";
require_once 'auth.php';
include 'header.php';
$templateName = $thisPage;
$toTemplate['saveForm']['keyID'] = $_POST[keyID];
$toTemplate['saveForm']['vCode'] = $_POST[vCode];
$toTemplate['saveForm']['email'] = $_POST[email];
if ($_POST[form] == 'sent') {
    $login = $_POST[login];
    $password = $_POST[password];
    $passwordRepeat = $_POST[passwordRepeat];
    if ($_POST[email]) {
        $email = $_POST[email];
    }
    try {
        if (!($_SESSION[regObject] instanceof registerNewUser)) {
            throw new Exception("Getting api info step was skipped. Aborting...", 31);
        }
        $_SESSION[regObject]->setUserData($login, $password, $passwordRepeat, $email);
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
            case 11:
                $toTemplate["errorMsg"] = "There is a problem with your password: " . $ex->getMessage();
                break;
            case 12:
                $toTemplate["errorMsg"] = "Your passwords don't match!";
                break;
            case 15:
                $toTemplate["errorMsg"] = "There is a problem with CCP servers. Please try again later.";
                break;
            case 20:
                $toTemplate["errorMsg"] = "Please choose eligible charater.";
                break;
            case 21:
                $toTemplate["errorMsg"] = "This character is already registered!";
                break;
            case 22:
                $toTemplate["errorMsg"] = "This api is already used!";
                break;
            case 30:
                $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                break;
            case 31:
                $toTemplate["errorMsg"] = "Please choose your main character firstly!";
                break;
        }
    }
}
require 'twigRender.php';