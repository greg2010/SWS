<?php
require_once 'auth.php';
$_SESSION[userObject]->removeCookie();
unset($_SESSION[userObject]);
$_SESSION['userObject'] = new userSession();
$toTemplate['loggedIN'] = 0;
header("Location: /index.php");