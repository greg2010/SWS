<?php

require_once 'auth.php';

switch ($_GET[a]) {
    case "step_1":
        //Ask for the code, if works redirect to s2
        try {
            if ($_POST[form] == 'sent') {
                $verified = $_SESSION[restore]->verifyUser($_POST[hash]);
            } elseif(isset($_GET[hash])) {
                $verified = $_SESSION[restore]->verifyUser($_GET[hash]);
            }
            if ($verified) {
                header("Location: /restorePassword.php?a=step_2");
            }
        } catch (Exception $ex) {
            //24,30
        }
        break;
    case "step_2":
        //Ask for new pwd and pwd-repeat. If fine, set new pwd and redirect to index
        try {
            if (!$_SESSION[restore]->isVerified()) {
                header("Location: /restorePassword.php");
            }
            if ($_POST[form] == 'sent') {
            $_SESSION[restore]->setNewPassword($_POST[password], $_POST[passwordRepeat]);
            }
            ("Location: /login.php");
        } catch (Exception $ex) {
            //11,25,30
        }
        break;
    default:
        //Ask for email and login, if fine redirect to s1
    if ($_POST[form] == 'sent') {
        $login = $_POST[login];
        $email = $_POST[email];
        $_SESSION['restore'] = new restorePassword();
        try {
            $_SESSION[restore]->setUserData($login, $email);
            $_SESSION[restore]->mail();
            header("Location: /restorePassword.php?a=step_1");
        } catch (Exception $ex) {
            //21,22,23,30
        }
    }
        break;
}
//человек жмет кнопку восстановить пароль
//в бд генерируется случайный хеш кода
//юзеру ставится спец кука с кодом->на почту приходит ссылка с гет запросом к спец скрипту
//юзер переходит по этой спец ссылке с гет запросом, в гет запросе хеш ключа->скрипт берет куку, хеширует код из нее, сверяет с тем что в гет запросе и тем что в бд, если все ок, предлагает ввести новый пароль
//после ввода нового пароля кука уничтожается, а запись из бд стирается