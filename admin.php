<?php
$thisPage = "admin";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$pagePermissions = array("webReg_Valid", "webReg_AdminPanel");

$admin = new admin($_SESSION[userObject]->getID());
$toTemplate['allowedList'] = $admin->getAllowList();

require 'twigRender.php';