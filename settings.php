<?php
$thisPage = "settings";
require_once 'auth.php';
include 'header.php';

$pageActive = "class=active";

//$pagePermissions = array("webReg_Valid");

$templateName = $thisPage;
$page = $_GET[a];

switch ($page) {
    case 'api':
        $toTemplate['curForm'] = 'api';
        $toTemplate['active']['api'] = $pageActive;
        
        $API = $_SESSION[userObject]->getApiPilotInfo();
        $toTemplate['saveForm']['currKeyID'] = $API[mainAPI][keyID];
        $toTemplate['saveForm']['currVCode'] = $API[mainAPI][vCode];
        
        if ($_POST[form] == 'sent') {
                switch (($_POST[action])) {
                    case 'changeMain':
                        try {
                            $_SESSION[userObject]->userManagement->changeMainAPI($_POST[keyID], $_POST[vCode], $_POST[login]);
                            $toTemplate['saveForm']['currKeyID'] = $_POST[keyID];
                            $toTemplate['saveForm']['currVCode'] = $_POST[vCode];
                            $toTemplate['success'] = "All changes are applied.";
                        } catch (Exception $ex) {
                            switch ($ex->getCode()) {
                                case 11:
                                    $toTemplate["errorMsg"] = "There is a problem: " . $ex->getMessage();
                                    break;
                                case 15:
                                    $toTemplate["errorMsg"] = "There is a problem with CCP servers. Please try again later.";
                                    break;
                                case 20:
                                    $toTemplate["errorMsg"] = "Please choose eligible charater.";
                                    break;
                                case 22:
                                    $toTemplate["errorMsg"] = "This character is already registered!";
                                    break;
                                case 30:
                                    $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                                    break;
                                case 31:
                                    $toTemplate["errorMsg"] = "Please choose your main character firstly!";
                                    break;
                            }
                        }
                        break;
                    case 'ban':
                        try {
                        $_SESSION[userObject]->userManagement->ban();
                        $_SESSION[userObject]->updateUserInfo();
                        $toTemplate['success'] = "All changes are applied.";
                        } catch (Exception $ex) {
                             $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                        }
                        break;
                    case 'addSec':
                        try {
                            $_SESSION[userObject]->userManagement->addSecAPI($_POST[keyID], $_POST[vCode], $_POST[login]);
                            $toTemplate['saveForm']['currKeyID'] = $_POST[keyID];
                            $toTemplate['saveForm']['currVCode'] = $_POST[vCode];
                            $_SESSION[userObject]->updateUserInfo();
                            $API = $_SESSION[userObject]->getApiPilotInfo();
                            $toTemplate['secSuccess'] = "All changes are applied.";
                        } catch (Exception $ex) {
                            switch ($ex->getCode()) {
                                case 11:
                                    $toTemplate["errorSecMsg"] = "There is a problem: " . $ex->getMessage();
                                    break;
                                case 15:
                                    $toTemplate["errorSecMsg"] = "There is a problem with CCP servers. Please try again later.";
                                    break;
                                case 20:
                                    $toTemplate["errorSecMsg"] = "Please choose eligible charater.";
                                    break;
                                case 22:
                                    $toTemplate["errorSecMsg"] = "This character is already registered!";
                                    break;
                                case 30:
                                    $toTemplate["errorSecMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                                    break;
                                case 31:
                                    $toTemplate["errorSecMsg"] = "Please choose your main character firstly!";
                                    break;
                            }
                        }
                        break;
                    case 'deleteSec':
                        $_SESSION[userObject]->userManagement->deleteSecAPI($_POST[characterID]);
                        $_SESSION[userObject]->updateUserInfo();
                        $API = $_SESSION[userObject]->getApiPilotInfo();
                        $toTemplate['secSuccess'] = "All changes are applied.";
                        break;
                }
        }
        
        if (count($API[secAPI])>0) {
            if (!is_array($API[secAPI][0])) {
                $toTemplate['apiList'][0]['keyID'] = $API[secAPI][keyID];
                $toTemplate['apiList'][0]['vCode'] = $API[secAPI][vCode];
                $toTemplate['apiList'][0]['characterName'] = $API[secAPI][characterName];
                $toTemplate['apiList'][0]['characterID'] = $API[secAPI][characterID];
            } elseif (count($API[secAPI]) > 1) {
                $i = 0;
                foreach ($API[secAPI] as $apilist) {
                    $toTemplate['apiList'][$i]['keyID'] = $apilist[keyID];
                    $toTemplate['apiList'][$i]['vCode'] = $apilist[vCode];
                    $toTemplate['apiList'][$i]['characterName'] = $apilist[characterName];
                    $toTemplate['apiList'][$i]['characterID'] = $apilist[characterID];
                    $i++;
                }
                unset($i);
            }
        }
        break;
    case 'teamspeak':
        $toTemplate['curForm'] = 'teamspeak';
        $toTemplate['active']['teamspeak'] = $pageActive;
        $toTemplate['hasReg'] = $_SESSION[userObject]->hasTSRegistration();
        
        if ($_SESSION[regSuccess] == 1) {
            $toTemplate['regSuccess'] = 1;
            unset($_SESSION[regSuccess]);
        }
        
        if ($toTemplate[hasReg]) {
            $TSInfo = $_SESSION[userObject]->getTSInfo();
            $toTemplate['hasTSReg'] = $TSInfo;
            if ($_POST[form] == 'sent') {
                try {
                    if ($_POST[action] == 'delete') {
                        $_SESSION[userObject]->userManagement->deleteFromTeamspeak();
                    }
                } catch (Exception $ex) {
                    $toTemplate["errorMsgTS"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                }
            }
        } else {
        $ts3 = new ts3();
        try {
            $toTemplate['TSNickname'] = $ts3->nickname($_SESSION[userObject]->getID());
        } catch (Exception $ex) {
            $toTemplate["errorMsgTS"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
        }
        
        if ($_POST[form] == 'sent') {
            $toTemplate['saveForm']['uniqueID'] = $_POST[uniqueID];
            try {
                switch (($_POST[action])) {
                    case 'TS':
                        $_SESSION[userObject]->userManagement->registerInTeamspeak($_POST[UniqueID]);
                        break;
                }
            } catch (Exception $ex) {
                switch ($ex->getCode()) {
                    case 11:
                        if($_POST[action] == 'TS') {
                            $toTemplate["errorMsgTS"] = "Please hit /'Open Teamspeak/' button firstly!";
                        }
                        break;
                    case 30:
                        $toTemplate["errorMsgTS"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                        break;
                }
            }
        }
        
        //id-uniqueID
        //$ts3->validate($id);
        
        //$ts3->nickname($id);
        //$ts3->getUid($nick);
        //$ts3->validate($id);
        }
        break;
    default:
        $toTemplate['curForm'] = '';
        $toTemplate['active']['profile'] = $pageActive;
        
        $toTemplate['saveForm']['email'] = $_SESSION[userObject]->userInfo[email];
        if ($_POST[form] == 'sent') {
            try {
                $currPassword = $_POST[currentPassword];
                $_SESSION[userObject]->verifyCurrentPassword($currPassword);
                if ($_POST[email]) {
                    try {
                        $email = $_POST[email];
                        if ($toTemplate['saveForm']['email'] === $email) {
                            throw new Exception('', 0);
                        }
                        $toTemplate['saveForm']['email'] = $email;
                        $_SESSION[userObject]->userManagement->setNewEmail($email);
                        $emailSuccess = 1;
                    } catch (Exception $ex) {
                        $emailSuccess = 0;
                        switch ($ex->getCode()) {
                            case 11:
                                $toTemplate["errorMsgEmail"] = "There is a problem: " . $ex->getMessage();
                                break;
                            case 30:
                                $toTemplate["errorMsgEmail"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                                break;
                        }
                    }
                }
                if ($_POST[password]) {
                    try {
                        $password = $_POST[password];
                        $passwordRepeat = $_POST[passwordRepeat];
                        $_SESSION[userObject]->userManagement->setNewPassword($password, $passwordRepeat);
                        $passwordSuccess = 1;
                    } catch (Exception $ex) {
                        $passwordSuccess = 0;
                        switch ($ex->getCode()) {
                            case 11:
                                $toTemplate["errorMsgPassword"] = "There is a problem: " . $ex->getMessage();
                                break;
                            case 30:
                                $toTemplate["errorMsgPassword"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                                break;
                        }
                    }
                }
                if (isset($emailSuccess) && $emailSuccess == 1 || isset($passwordSuccess) && $passwordSuccess == 1) {
                    $toTemplate['success'] = "All changes are applied.";
                }
            } catch (Exception $ex) {
                switch ($ex->getCode()) {
                    case 13:
                        $toTemplate["errorMsg"] = "Wrong password!";
                        break;
                    case 30:
                        $toTemplate["errorMsg"] = "Internal server error. Please contact server administrators ASAP to resolve this issue! Please convey this information to server administator:" . $ex->getMessage();
                        break;
                }
            }
        }
}

require 'twigRender.php';