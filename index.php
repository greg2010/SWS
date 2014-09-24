<?php
$thisPage = "index";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;

require 'twigRender.php';