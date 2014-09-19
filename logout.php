<?php
require_once 'auth.php';
$_SESSION[userObject]->removeCookie();
unset($_SESSION[userObject]);
header("Location: /index.php");