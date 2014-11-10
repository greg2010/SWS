<?php

class notif_send {

    private $db;
    private $id;
    private $corporationID;
    private $allianceID;
    private $email;
    private $login;
    private $lastNotifID = 0;
    private $send_email = false;
    private $send_jabber = false;
    private $permission = 0;
    private $posop;

	public function __construct($id, $corporationID, $allianceID){
        $this->db = db::getInstance();
        $this->id = $id;
        $this->corporationID = $corporationID;
        $this->allianceID = $allianceID;
        $this->getMoreInfoFromDB();
        if(($this->send_email || $this->send_jabber) && ($this->permission > 0)){
            $txt = $this->getNotifications();
            if($txt != NULL){
                if($this->send_email){
                    $c_email = new email;
                    if(!$c_email->sendmail($this->email, "New EvE Online notification update", date(DATE_RFC822) . " New notifications arrived.\n" . $txt)) 
                        throw new Exception("Mail sending failed", -1);
                }
                if($this->send_jabber){
                    $c_xmpp = new xmpp;
                    if(!$c_xmpp->sendjabber($this->login, $txt))
                        throw new Exception("Mail sending failed", -1);
                }
                $query = "UPDATE `users` SET `lastNotifID` = '$this->lastNotifID' WHERE `id`='$this->id'";
                $result = $this->db->query($query);
            }
        }
    }

    private function getMoreInfoFromDB(){
        $query = "SELECT `accessMask`, `settingsMask`, `email`, `login`, `lastNotifID` FROM `users` WHERE `id` = '$this->id'";
        $result = $this->db->query($query);
        $arr = $this->db->fetchAssoc($result);
        $this->email = $arr[email];
        $this->login = $arr[login];
        $this->lastNotifID = $arr[lastNotifID];
        $this->permission = $this->genPermission($arr[accessMask]);
        $this->send_email = (($arr[settngsMask] & 1) > 0) ? true : false;
        $this->send_jabber = (($arr[settngsMask] & 2) > 0) ? true : false;
    }

    private function genPermission($mask){
        $permissions = new permissions($this->id);
        $this->posop = ($permissions->hasPermission("posMon_Valid")) ? (" OR (`typeID` = 76 AND `allianceID` = '" . $this->allianceID . "')") : (" OR (`typeID` = 76 AND `corporationID` = '" . $this->corporationID . "')");
        return ($permissions->hasPermission("XMPP_Valid")) ? (($permissions->hasPermission("XMPP_RoamingFC")) ? (($permissions->hasPermission("XMPP_Overmind") || $permissions->hasPermission("XMPP_FleetCom")) ? 3 : 2) : 1) : 0;
    }

    private function getNotifications(){
        $mailtext = NULL;
        if($this->permission == 1) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND ((`typeID` <> 76 AND `corporationID` = '$this->corporationID')" . $this->posop . ")";
        elseif($this->permission == 2) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND ((`typeID` <> 76 AND `allianceID` = '$this->allianceID')" . $this->posop . ")";
        elseif($this->permission == 3) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND (`typeID` <> 76" . $this->posop . ")";
        $result = $this->db->query($query);
        $notifArr = $this->db->fetchArray($result);
        for ($j = 0; $j < $this->db->countRows($result); $j++){
            if($notifArr[$j][notificationID] > $this->lastNotifID) $this->lastNotifID = $notifArr[$j][notificationID];
            if($notifArr[$j][typeID]==76){
                $tmparr = yaml_parse($notifArr[$j][NotificationText]);
                for($h=0; $h < count($tmparr[wants]); $h++){
                    if($tmparr[wants][$h][typeID] = 4246 || $tmparr[wants][$h][typeID] = 4247 || $tmparr[wants][$h][typeID] = 4051 || $tmparr[wants][$h][typeID] = 4312){ // Fuel Block ids
                        $fuelph = $this->getFuelPH($tmparr[typeID]);
                        if($tmparr[wants][$h][quantity] >= $fuelph*23 && $tmparr[wants][$h][quantity] < $fuelph*24)
                            $mailtext .= $this->GenerateMailText($notifArr[$j][typeID], $notifArr[$j][sentDate], $notifArr[$j][NotificationText]);
                        if($tmparr[wants][$h][quantity] >= $fuelph*3 && $tmparr[wants][$h][quantity] < $fuelph*4)
                            $mailtext .= $this->GenerateMailText($notifArr[$j][typeID], $notifArr[$j][sentDate], $notifArr[$j][NotificationText]);
                    }
                }
            } else $mailtext .= $this->GenerateMailText($notifArr[$j][typeID], $notifArr[$j][sentDate], $notifArr[$j][NotificationText]);
        }
        return $mailtext;
    }

    private function getFuelPH($typeID){
        $query = "SELECT `fuelph` FROM `posList` WHERE `typeID` = '$typeID' LIMIT 1";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result, 0);
    }

    private function GenerateMailText($type, $sentDate, $str){
        // https://neweden-dev.com/Char/Notifications
        $mailtext = "\n" . $sentDate . " ";
        $strarr = yaml_parse($str);
        if($type == 76){
            $mailtext .= $strarr[typeName] . " low on resources on " . $strarr[moonName] . "\n";
            $mailtext .= "Owner: " . $strarr[corpName] . " [" . $strarr[corpTicker] . "] (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])" . "\n";
            for($i=0; $i < count($strarr[wants]); $i++){
                $mailtext .= "Remaining " . $strarr[wants][$i][quantity] . " " . $strarr[wants][$i][typeName] . "\n";
            }
        } elseif($type == 75 || $type == 80 || $type == 86 || $type == 87 || $type == 88){
            $locname = ($type == 75) ? $strarr[moonName] : $strarr[solarSystemName];
            if($type == 86) $strarr[typeName] = "Territorial Claim Unit";
            if($type == 87) $strarr[typeName] = "Sovereignty Blockade Unit";
            if($type == 88) $strarr[typeName] = "Infrastructure Hub";
            $mailtext .= $strarr[typeName] . " on " . $locname . " is under attack\n";
            $mailtext .= "Owner: " . $strarr[OwnerCorpName] . " [" . $strarr[OwnerCorpTicker] . "] (" . $strarr[OwnerAllyName] . " [" . $strarr[OwnerAllyTicker] . "])" . "\n";
            $mailtext .=  "Aggressor: " . $strarr[aggressorName] . " from " . $strarr[corpName] . " [" . $strarr[corpTicker] . "] (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])" . "\n";
            $mailtext .= "Shield: " . round($strarr[shieldValue]*100) . "% Armor: " . round($strarr[armorValue]*100) . "% Hull: " . round($strarr[hullValue]*100) . "%\n";
        } elseif($type == 93){
            $mailtext .= $strarr[typeName] . " on " . $strarr[planetName] . " is under attack\n";
            $mailtext .= "Owner: " . $strarr[OwnerCorpName] . " [" . $strarr[OwnerCorpTicker] . "] (" . $strarr[OwnerAllyName] . " [" . $strarr[OwnerAllyTicker] . "])" . "\n";
            $mailtext .=  "Aggressor: " . $strarr[aggressorName] . " from " . $strarr[corpName] . " [" . $strarr[corpTicker] . "] (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])" . "\n";
            $mailtext .= "Shield: " . round($strarr[shieldLevel]*100) . "%\n";
        } elseif($type == 77){
            $mailtext .= $strarr[typeName] . " in " . $strarr[solarSystemName] . " is under attack\n";
            $mailtext .= "Owner: " . $strarr[OwnerCorpName] . " [" . $strarr[OwnerCorpTicker] . "] (" . $strarr[OwnerAllyName] . " [" . $strarr[OwnerAllyTicker] . "])" . "\n";
            $mailtext .= "Shield: " . round($strarr[shieldLevel]*100) . "%\n";
        } elseif($type == 45 && $this->permission == 3){
            $mailtext .= "New " . $strarr[typeName] . " anchored on " . $strarr[moonName] . " by " . $strarr[corpName] . " [" . $strarr[corpTicker] . "] (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])" . "\n";
            $mailtext .= "Old towers in system:\n";
            for($i=0; $i < count($strarr[corpsPresent]); $i++){
                for($j=0; $j < count($strarr[corpsPresent][$i][towers]); $j++){
                    $mailtext .= $strarr[corpsPresent][$i][towers][$j][typeName] . " on " . $strarr[corpsPresent][$i][towers][$j][moonName] . ", ";
                }
                $mailtext .= " anchored by " . $strarr[corpsPresent][$i][corpName] . " [" . $strarr[corpsPresent][$i][corpTicker] . "] (" . $strarr[corpsPresent][$i][allyName] . " [" . $strarr[corpsPresent][$i][allyTicker] . "])" . "\n";
            }
        } else{
            if($type == 37 || $type == 38) $mailtext .= "Sovereignty claim fails in " . $strarr[solarSystemName] . "\n";
            if($type == 39 || $type == 40) $mailtext .= "Sovereignty bill late in " . $strarr[solarSystemName] . "\n";
            if($type == 41 || $type == 42) $mailtext .= "Sovereignty claim lost in " . $strarr[solarSystemName] . "\n";
            if($type == 43 || $type == 44) $mailtext .= "Sovereignty claim acquired in " . $strarr[solarSystemName] . "\n";
            if($type == 46) $mailtext .= "Alliance structure turns vulnerable in " . $strarr[solarSystemName] . "\n";
            if($type == 47) $mailtext .= "Alliance structure turns invulnerable in " . $strarr[solarSystemName] . "\n";
            if($type == 48) $mailtext .= "Sovereignty disruptor anchored in " . $strarr[solarSystemName] . "\n";
            if($type == 78) $mailtext .= "Station state change in " . $strarr[solarSystemName] . "\n";
            if($type == 79) $mailtext .= "Station conquered in " . $strarr[solarSystemName] . "\n";
        }
        return $mailtext;
    }
}

?>
