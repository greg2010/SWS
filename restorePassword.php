<?php

$thisPage = "restorePassword";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
if ($_GET[hash]) {
    //Ask for new pwd and pwd-repeat. If fine, set new pwd and redirect to index
    $_SESSION['restore'] = new restorePassword();
    try {
        if ($_GET[action] == 'remove') {
            throw new exception('removeRequest', 31);
        }
        $_SESSION['restore']->verifyUser($_GET[hash]);
        if ($_POST[form] == 'sent') {
            try {
                $_SESSION[restore]->setNewPassword($_POST[password], $_POST[passwordRepeat]);
                header("Location: /login.php");
            } catch (Exception $ex) {
                switch ($ex->getCode()) {
                    case 11:
                        $toTemplate["errorMsg"] = "There is a problem: " . $ex->getMessage();
                        break;
                    case 30:
                        $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                        break;
                }
            }
        } else {
            $toTemplate['verified'] = TRUE;
            $toTemplate['hash'] = $_GET[hash];
        }
    } catch (Exception $ex) {
        switch ($ex->getCode()) {
            case 24:
                $toTemplate['errorMsg'] = "Verification failed. Please click here to try again. Please insure you request password reset and do actual reset on the same device.";
                $toTemplate['isDisabled'] = "Disabled";
                $_SESSION[restore]->removeRequest($_GET[hash]);
                break;
            case 30:
                $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                break;
            case 31:
                $_SESSION[restore]->removeRequest($_GET[hash]);
                header("Location: /restorePassword.php");
                break;
        }
    }
} else {
    //Ask for email and login, if fine redirect to s1
    if ($_POST[form] == 'sent') {
        $login = $_POST[login];
        $email = $_POST[email];
        $_SESSION['restore'] = new restorePassword(init);
        try {
            $_SESSION[restore]->setUserData($login, $email);
            $_SESSION[restore]->mail();
            $toTemplate['success'] = TRUE;
        } catch (Exception $ex) {
            //21,22,23,24,30
            switch ($ex->getCode()) {
                case 21:
                case 22:
                case 23:
                case 24:
                case 25:
                    $toTemplate['errorMsg'] = $ex->getMessage();
                    break;
                case 30:
                    $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                    break;
            }
        }
    }
}
require 'twigRender.php';
//человек жмет кнопку восстановить пароль
//в бд генерируется случайный хеш кода
//юзеру ставится спец кука с кодом->на почту приходит ссылка с гет запросом к спец скрипту
//юзер переходит по этой спец ссылке с гет запросом, в гет запросе хеш ключа->скрипт берет куку, хеширует код из нее, сверяет с тем что в гет запросе и тем что в бд, если все ок, предлагает ввести новый пароль
//после ввода нового пароля кука уничтожается, а запись из бд стирается