<?php

require_once 'init.php';
$keyID = $_POST[keyID];
$vCode = $_POST[vCode];

//$keyID = "3361996";
//$vCode = "njIL4YIM0iwyl8QHWy9rf027vBxL3xZyAZ4Jl7CKLJJhPCorh981QC6mRgOy6wfA";

unset($_SESSION[regObject]);
$_SESSION["regObject"] = new registerNewUser();

$_SESSION[regObject]->setUserApi($keyID, $vCode);

header('Content-Type: application/json');
echo $_SESSION[regObject]->AjaxAnswer();