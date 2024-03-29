<?php
$thisPage = "reg";
require_once 'common.php';
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
                throw new Exception("Can't login after registration!", 30);
            } else {
                $_SESSION[logObject]->setRegistrationInfo('exceptionCode', 0);
                $_SESSION[logObject]->setRegistrationInfo('exceptionText', NULL);
                $_SESSION[logObject]->pushToDb('reg');
                $_SESSION[userObject]->setCookieForUser();
                
                header("Location: /settings.php?a=teamspeak");
                $_SESSION['successMsg'] = "Registration is successful. Congratulations! There is only one step left to become full coalition member.";
            }
        }
    } catch (Exception $ex) {
        switch ($ex->getCode()) {
            case 10:
                $toTemplate["errorMsg"] = "Please fill in all fields!";
                break;
            case 11:
                $toTemplate["errorMsg"] = "There is a problem: " . $ex->getMessage();
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
        $_SESSION[logObject]->setRegistrationInfo('exceptionCode', $ex->getCode());
        $_SESSION[logObject]->setRegistrationInfo('exceptionText', $ex->getMessage());
        $_SESSION[logObject]->pushToDb('reg');
    }
}
require 'twigRender.php';