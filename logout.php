<?php
require_once 'auth.php';
$_SESSION[userObject]->removeCookie();
unset($_SESSION[userObject]);
$_SESSION['userObject'] = new userSession();

header("Location: /index.php");