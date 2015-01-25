<?php

require_once 'auth.php';

switch ($_GET[a]) {
    case "s1":
        //Ask for the code, if works redirect to s2
        break;
    case "s2":
        //Ask for new pwd and pwd-repeat. If fine, set new pwd and redirect to index
        break;
    default:
        //Ask for email and login, if fine redirect to s1
    if ($_POST[form] == 'sent') {
        $login = $_POST[login];
        $email = $_POST[email];
        $_SESSION['restore'] = new restorePassword();
        try {
            $restorePassword->setUserData($login, $email);
            $restorePassword->mail();
        } catch (Exception $ex) {

        }
    }
        break;
}
//человек жмет кнопку восстановить пароль
//в бд генерируется случайный хеш кода
//юзеру ставится спец кука с кодом->на почту приходит ссылка с гет запросом к спец скрипту
//юзер переходит по этой спец ссылке с гет запросом, в гет запросе хеш ключа->скрипт берет куку, хеширует код из нее, сверяет с тем что в гет запросе и тем что в бд, если все ок, предлагает ввести новый пароль
//после ввода нового пароля кука уничтожается, а запись из бд стирается