<?php
require_once 'auth.php';
include 'header.php';

$toTemplate = array();
$thisPage = "index";
$templateName = $thisPage;
var_dump($_SESSION[userObject]);
require 'twigRender.php';