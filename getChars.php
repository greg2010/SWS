<?php

require_once 'init.php';
//$keyID = $_POST[keyID];
//$vCode = $_POST[vCode];

$keyID = "3361996";
$vCode = "njIL4YIM0iwyl8QHWy9rf027vBxL3xZyAZ4Jl7CKLJJhPCorh981QC6mRgOy6wfA";

$api = new APIUserManagement();
$chars = $api->getCharsInfo($keyID, $vCode);
$userManagement = new userManagement();
$permissions = new permissions();

if (!$chars) {
    $error = $api->log->get();
    $sendArray['stats'] = array (
        "status" => $error[getApiPilotInfo_code],
        "message" => ltrim($error[getApiPilotInfo], "err ")
    );
} else {
    $sendArray = array();
    foreach ($chars as $char) {
        $requestArray = array (
            "characterID" => $char[characterID],
            "corporationID" => $char[corporationID],
            "allianceID" => $char[allianceID]
        );
        $keyPermissions = $userManagement->getAllowedListMask($requestArray);
        if (!$keyPermissions) {
            $keyPermissions = 0;
        }
        
        $permissions->setUserMask($keyPermissions);
        if ($permissions->hasPermission("webReg_Valid") == FALSE) {
            $canRegister = 0;
        } else {
            $canRegister = 1;
        }
        $sendArray[] = array(
            "characterName" => $char[characterName],
            "corporationName" => $char[corporationName],
            "allianceName" => $char[allianceName],
            "valid" => $canRegister
        );
    }
    $sendArray['stats'] = array (
        "code" => "0",
        "message" => ""
    );
}

header('Content-Type: application/json');
echo json_encode($sendArray);