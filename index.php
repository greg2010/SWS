<?php
$thisPage = "index";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$pagePermissions = array("webReg_Valid");

require 'twigRender.php';