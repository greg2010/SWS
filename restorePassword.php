<?php

require_once 'auth.php';
if ($_POST[form] == 'sent') {
    $login = $_POST[login];
    $email = $_POST[email];
    $restorePassword = new restorePassword();
    try {
        $restorePassword->setUserData($login, $email);
        $restorePassword->mail();
    } catch (Exception $ex) {

    }
}
//человек жмет кнопку восстановить пароль
//в бд генерируется случайный хеш кода
//юзеру ставится спец кука с кодом->на почту приходит ссылка с гет запросом к спец скрипту
//юзер переходит по этой спец ссылке с гет запросом, в гет запросе хеш ключа->скрипт берет куку, хеширует код из нее, сверяет с тем что в гет запросе и тем что в бд, если все ок, предлагает ввести новый пароль
//после ввода нового пароля кука уничтожается, а запись из бд стирается