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
    private $keys = array();
    //private $posop;

	public function __construct($user){
        $this->db = db::getInstance();
        $this->id = $user[id];
        $this->email = $user[email];
        $this->login = $user[login];
        $this->lastNotifID = $user[lastNotifID];
        $this->permission = $this->genPermission($user[accessMask]);
        $this->send_email = (($user[settingsMask] & 1) > 0) ? true : false;
        $this->send_jabber = (($user[settingsMask] & 2) > 0) ? true : false;
        $this->keys = $this->getKeys();
        if(($this->send_email || $this->send_jabber) && ($this->permission > 0)){
            $txt = $this->getNotifications();
            if($txt != NULL){
                if($this->send_email){
                    $c_email = new email;
                    if(!$c_email->sendmail($this->email, "New EvE Online notification update", date(DATE_RFC822) . " New notifications arrived.\n" . $txt[starbase] . $txt[sovwarfare] . $txt[other])) 
                        throw new Exception("Mail sending failed", -1);
                }
                if($this->send_jabber){
                    $c_xmpp = new xmpp;
                    if($txt[starbase] != NULL){
                        if(!$c_xmpp->sendfrom("starbase alert", $this->login, $txt[starbase]))
                            throw new Exception("Jabber sending failed", -2);
                    }
                    if($txt[sovwarfare] != NULL){
                        if(!$c_xmpp->sendfrom("sovereignty alert", $this->login, $txt[sovwarfare]))
                            throw new Exception("Jabber sending failed", -2);
                    }
                    if($txt[other] != NULL){
                        if(!$c_xmpp->sendfrom("other alert", $this->login, $txt[other]))
                            throw new Exception("Jabber sending failed", -2);
                    }
                }
                $query = "UPDATE `users` SET `lastNotifID` = '$this->lastNotifID' WHERE `id`='$this->id'";
                $result = $this->db->query($query);
            }
        }
    }

    private function getKeys(){
        $query = "SELECT `corporationID`, `allianceID` FROM `apiPilotList` WHERE ((`keyStatus` > 0) AND (`id` = '$this->id'))";
        $result = $this->db->query($query);
        $tmparr = $this->db->fetchAssoc($result);
        if($this->db->countRows($result) == 1){
            $ids[corporationID][0] = $tmparr[corporationID];
            $ids[allianceID][0] = $tmparr[allianceID];
        } else{
            foreach($tmparr as $key){
                if(count($ids[corporationID]) == 0){
                    $ids[corporationID][] = $key[corporationID];
                } else{
                    if(!in_array($key[corporationID], $ids[corporationID])) $ids[corporationID][] = $key[corporationID];
                }
                if(count($ids[allianceID]) == 0){
                    $ids[allianceID][] = $key[allianceID];
                } else{
                    if(!in_array($key[allianceID], $ids[allianceID])) $ids[allianceID][] = $key[allianceID];
                }
            }
        }
        return $ids;
    }

    private function genPermission($mask){
        $permissions = new permissions($this->id);
        //$this->posop = ($permissions->hasPermission("posMon_Valid")) ? (" OR (`typeID` = 76 AND `allianceID` = '" . $this->allianceID . "')") : (" OR (`typeID` = 76 AND `corporationID` = '" . $this->corporationID . "')");
        if($permissions->hasPermission("XMPP_Valid")){
            if($permissions->hasPermission("XMPP_Overmind") || $permissions->hasPermission("XMPP_FleetCom")){
                return 3;
            } else{
                if($permissions->hasPermission("XMPP_RoamingFC")){
                    return 2;
                } else return 1;
            }
        } else return 0;
    }

    private function getNotifications(){
        $mailtext = NULL;
        /*if($this->permission == 1) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND ((`typeID` <> 76 AND `corporationID` = '$this->corporationID')" . $this->posop . ")";
        elseif($this->permission == 2) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND ((`typeID` <> 76 AND `allianceID` = '$this->allianceID')" . $this->posop . ")";
        elseif($this->permission == 3) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND (`typeID` <> 76" . $this->posop . ")";*/

        if(count($this->keys[corporationID]) == 1){
            $cid_text = "`corporationID` = '{$this->keys[corporationID][0]}'";
        } else{
            for($i = 0; $i < count($this->keys[corporationID]); $i++){
                $cid_text .= ($i == 0) ? "(" : " OR ";
                $cid_text .= "(`corporationID` = '{$this->keys[corporationID][$i]}')";
                if($i == count($this->keys[corporationID])-1) $cid_text .= ")";
            }
        }
        if(count($this->keys[allianceID]) == 1){
            $aid_text = "`allianceID` = '{$this->keys[allianceID][0]}'";
        } else{
            for($i = 0; $i < count($this->keys[allianceID]); $i++){
                $aid_text .= ($i == 0) ? "(" : " OR ";
                $aid_text .= "(`allianceID` = '{$this->keys[allianceID][$i]}')";
                if($i == count($this->keys[allianceID])-1) $aid_text .= ")";
            }
        }
        if($this->permission == 1) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND ((`typeID` <> 76 AND " . $aid_text . ") OR (`typeID` = 76 AND " . $cid_text . "))";
        elseif($this->permission == 2) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND ((`typeID` <> 76 AND " . $aid_text . ") OR (`typeID` = 76 AND " . $cid_text . "))";
        elseif($this->permission == 3) $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` "
         . "WHERE `notificationID` > '$this->lastNotifID' AND (`typeID` <> 76 OR (`typeID` = 76 AND " . $cid_text . "))";
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
                            $returntext = $this->GenerateMailText($notifArr[$j][typeID], $notifArr[$j][NotificationText]);
                        if($tmparr[wants][$h][quantity] >= $fuelph*3 && $tmparr[wants][$h][quantity] < $fuelph*4)
                            $returntext = $this->GenerateMailText($notifArr[$j][typeID], $notifArr[$j][NotificationText]);
                    }
                }
            } else $returntext = $this->GenerateMailText($notifArr[$j][typeID], $notifArr[$j][NotificationText]);
            if($returntext != NULL){
                $t = $notifArr[$j][typeID];
                if($t==75 || $t==76) $mailtext[starbase] .= "\n" . $notifArr[$j][sentDate] . " " . $returntext;
                elseif($t==80 || $t==86 || $t==87 || $t==88 || $t==43 || $t==44 || $t==41 || $t==42 || $t==46 || $t==47 || $t==48 || $t==37 || $t==38 || $t==79) $mailtext[sovwarfare] .= "\n" . $notifArr[$j][sentDate] . " " . $returntext;
                else $mailtext[other] .= "\n" . $notifArr[$j][sentDate] . " " . $returntext;
            }
        }
        return $mailtext;
    }

    private function getFuelPH($typeID){
        $query = "SELECT `fuelph` FROM `posList` WHERE `typeID` = '$typeID' LIMIT 1";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result, 0);
    }

    private function GenerateMailText($type, $str){
        // https://neweden-dev.com/Char/Notifications
        $mailtext = NULL;
        $strarr = yaml_parse($str);
        if($type == 76){
            $mailtext .= $strarr[typeName] . " low on resources on " . $strarr[moonName] . "\n";
            $mailtext .= "Owner: " . $strarr[OwnerCorpName] . " [" . $strarr[OwnerCorpTicker] . "] (" . $strarr[OwnerAllyName] . " [" . $strarr[OwnerAllyTicker] . "])" . "\n";
            for($i=0; $i < count($strarr[wants]); $i++){
                $mailtext .= "Remaining " . $strarr[wants][$i][quantity] . " " . $strarr[wants][$i][typeName] . "\n";
            }
        } elseif(($type == 75 || $type == 80 || $type == 86 || $type == 87 || $type == 88)  && $this->permission > 1){
            $locname = ($type == 75) ? $strarr[moonName] : $strarr[solarSystemName];
            if($type == 86) $strarr[typeName] = "Territorial Claim Unit";
            if($type == 87) $strarr[typeName] = "Sovereignty Blockade Unit";
            if($type == 88) $strarr[typeName] = "Infrastructure Hub";
            $mailtext .= $strarr[typeName] . " on " . $locname . " is under attack\n";
            $mailtext .= "Owner: " . $strarr[OwnerCorpName] . " [" . $strarr[OwnerCorpTicker] . "] (" . $strarr[OwnerAllyName] . " [" . $strarr[OwnerAllyTicker] . "])" . "\n";
            $mailtext .= ($strarr[aggressorID] != NULL) ? ("Aggressor: " . $strarr[aggressorName]) : ("Aggressor: Unknown");
            $mailtext .= ($strarr[aggressorCorpID] != NULL) ? (" from " . $strarr[corpName] . " [" . $strarr[corpTicker] . "]") : (" from Unknown Corporation");
            if($strarr[aggressorAllianceID] != NULL) $mailtext .= " (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])";
            $mailtext .= "\nShield: " . round($strarr[shieldValue]*100) . "% Armor: " . round($strarr[armorValue]*100) . "% Hull: " . round($strarr[hullValue]*100) . "%\n";
        } elseif($type == 93 && $this->permission > 1){ // Customs office has been attacked
            $mailtext .= $strarr[typeName] . " on " . $strarr[planetName] . " is under attack\n";
            $mailtext .= "Owner: " . $strarr[OwnerCorpName] . " [" . $strarr[OwnerCorpTicker] . "] (" . $strarr[OwnerAllyName] . " [" . $strarr[OwnerAllyTicker] . "])" . "\n";
            $mailtext .= ($strarr[aggressorID] != NULL) ? ("Aggressor: " . $strarr[aggressorName]) : ("Aggressor: Unknown");
            $mailtext .= ($strarr[aggressorCorpID] != NULL) ? (" from " . $strarr[corpName] . " [" . $strarr[corpTicker] . "]") : (" from Unknown Corporation");
            if($strarr[aggressorAllianceID] != NULL) $mailtext .= " (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])";
            $mailtext .= "\nShield: " . round($strarr[shieldLevel]*100) . "%\n";
        } elseif($type == 77 && $this->permission > 1){ // Station service aggression message
            $mailtext .= $strarr[typeName] . " is under attack at " . $strarr[stationName] . " in " . $strarr[solarSystemName] . "\n";
            $mailtext .= "Owner: " . $strarr[OwnerCorpName] . " [" . $strarr[OwnerCorpTicker] . "] (" . $strarr[OwnerAllyName] . " [" . $strarr[OwnerAllyTicker] . "])" . "\n";
            $mailtext .= ($strarr[aggressorID] != NULL) ? ("Aggressor: " . $strarr[aggressorName]) : ("Aggressor: Unknown");
            $mailtext .= ($strarr[aggressorCorpID] != NULL) ? (" from " . $strarr[corpName] . " [" . $strarr[corpTicker] . "]") : (" from Unknown Corporation");
            if($strarr[aggressorAllianceID] != NULL) $mailtext .= " (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])";
            $mailtext .= "\nShield: " . round($strarr[shieldValue]*100) . "%\n";
        } elseif($type == 45 && $this->permission == 3){
            $mailtext .= "New " . $strarr[typeName] . " anchored on " . $strarr[moonName] . " by " . $strarr[corpName] . " [" . $strarr[corpTicker] . "]";
            $mailtext .= ($strarr[allyName] != NULL) ? (" (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])\n") : ("\n");
            $mailtext .= "Old towers in system:\n";
            for($i=0; $i < count($strarr[corpsPresent]); $i++){
                for($j=0; $j < count($strarr[corpsPresent][$i][towers]); $j++){
                    $mailtext .= $strarr[corpsPresent][$i][towers][$j][typeName] . " on " . $strarr[corpsPresent][$i][towers][$j][moonName] . ", ";
                }
                $mailtext .= " anchored by " . $strarr[corpsPresent][$i][corpName] . " [" . $strarr[corpsPresent][$i][corpTicker] . "]";
                $mailtext .= ($strarr[corpsPresent][$i][allyName] != NULL) ? (" (" . $strarr[corpsPresent][$i][allyName] . " [" . $strarr[corpsPresent][$i][allyTicker] . "])\n") : ("\n");
            }
        } elseif($type == 43 || $type == 44 || $type == 41 || $type == 42 || $type == 46 || $type == 47 || $type == 48 || $type == 37 || $type == 38 || $type == 79){ // Sovereignty claim
            if($type == 37 || $type == 38) $mailtext .= "Sovereignty claim fails";
            if($type == 41 || $type == 42) $mailtext .= "Sovereignty claim lost";
            if($type == 43 || $type == 44) $mailtext .= "Sovereignty claim acquired";
            if($type == 46 && $this->permission > 1) $mailtext .= "Alliance structure turns vulnerable";
            if($type == 47 && $this->permission > 1) $mailtext .= "Alliance structure turns invulnerable";
            if($type == 48 && $this->permission > 1) $mailtext .= "Sovereignty disruptor anchored";
            if($type == 79) $mailtext .= "Station conquered";
            $mailtext .= " in " . $strarr[solarSystemName] . "\nOwner: ";
            $mailtext .= ($strarr[corpID] != NULL) ? ($strarr[corpName] . " [" . $strarr[corpTicker] . "]") : "Unknown";
            $mailtext .= ($strarr[allianceID] != NULL) ? (" (" . $strarr[allyName] . " [" . $strarr[allyTicker] . "])\n") : "\n";
        } else{
            if(($type == 39 || $type == 40) && $this->permission == 3) $mailtext .= "Sovereignty bill late in " . $strarr[solarSystemName] . "\n";
            if($type == 78 && $this->permission > 1) $mailtext .= "Station state change in " . $strarr[solarSystemName] . "\n";
        }
        return $mailtext;
    }
}

?>
