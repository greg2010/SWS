<?php
require_once 'init.php';
$db = db::getInstance();
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = userSession::getInstance();
}