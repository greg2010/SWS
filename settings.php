<?php
$thisPage = "settings";
require_once 'auth.php';
include 'header.php';

$pageActive = "class=active";

$templateName = $thisPage;
$page = $_GET[a];

switch ($page) {
    case 'api':
        $toTemplate['curForm'] = 'api';
        $toTemplate['active']['api'] = $pageActive;
        break;
    case 'teamSpeak':
        $toTemplate['curForm'] = 'teamspeak';
        $toTemplate['active']['teamspeak'] = $pageActive;
        break;
    default:
        $toTemplate['curForm'] = '';
        $toTemplate['active']['profile'] = $pageActive;
}

require 'twigRender.php';