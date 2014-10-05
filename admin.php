<?php
$thisPage = "admin";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$pagePermissions = array("webReg_Valid", "webReg_AdminPanel");

require 'twigRender.php';