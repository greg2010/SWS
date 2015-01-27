<?php
require_once 'init.php';
session_start();

$posmon = new posmon();
try {
    $posmon->updateSiloOwner($_GET[siloID], $_GET[posID]);
} catch (Exception $ex) {
    //13,30
}
header(); //redirect