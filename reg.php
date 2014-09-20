<?php
$thisPage = "login";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;

if ($_POST[form] == 'sent') {
    $key = $_POST[key];
    $vCode = $_POST[vCode];
    $login = $_POST[characterName];
    $password = $_POST[password];
    if ($_POST[email]) {
        $email = $_POST[email];
    }
    
}

require 'twigRender.php';