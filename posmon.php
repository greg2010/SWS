<?php

$thisPage = "posmon";
require_once 'auth.php';
include 'header.php';

$templateName = $thisPage;
$pagePermissions = array("webReg_Valid", "posMon_Valid");

$affiliation = $_SESSION[userObject]->getUserAffiliations();

if ($_SESSION[userObject]->permissions->hasPermission('XMPP_Overmind')) {
//    $accessLevel = 1; //Alliances of the user
//    $query = "SELECT * FROM `posList` WHERE ";
//    if (count($affiliation[alliance])>1) {
//        foreach ($affiliation[alliance] as $key => $value) {
//            $query .= "`allianceID` = '$value'";
//            if (count($affiliation[alliance]) - $key > 0) {
//                $query .= " OR ";
//            }
//        }
//    }
} else {
    $accessLevel = 0; //Corps of the user
    $corps = $affiliation[corporation];
    foreach ($corps as $corporationID) {
        $corpName = $db->getMysqlResult($db->query("SELECT `name` FROM `corporationList` WHERE `id` = '$corporationID'"));
        $query = "SELECT * FROM `posList` WHERE `corporationID` = '$corporationID'";
        $data = $db->fetchAssoc($db->query($query));
        if (count($data)>0) {
            $return["$corpName"] = $data;
            if (is_array($return[$corpName])) {
                foreach ($return[$corpName] as $number => $pos) {
                    $return[$corpName][$number][time] = $_SESSION[userObject]->hoursToDays($return[$corpName][$number][time]);
                }
            } else {
                $return[$corpName][time] = $_SESSION[userObject]->hoursToDays($return[$corpName][$number][time]);
            }
            $return["$corpName"]['corporationName'] = $corpName;
        }
    }
print_r($return);
    
//    $query = "SELECT * FROM `posList` WHERE ";
//    if (count($affiliation[corporation])>1) {
//        foreach ($affiliation[corporation] as $key => $value) {
//            $query .= "`corporationID` = '$value'";
//            if (count($affiliation[corporation]) - $key > 0) {
//                $query .= " OR ";
//            }
//        }
//    }
}

//print_r($db->fetchAssoc($db->query($query)));

//cornercase 1 pos!



//require 'twigRender.php';