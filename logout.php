<?php
require_once 'init.php';
$_SESSION[user]->removeCookie();
unset($_SESSION[user]);
header("Location: /index.php");