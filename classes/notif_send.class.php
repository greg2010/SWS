<?php

class notif_send {

    private $db;
    private $id;
    private $corporationID;
    private $allianceID;
    private $email;
    private $lastNotifID;
    private $send_email = false;
    private $send_jabber = false;

	public function __construct($id, $corporationID, $allianceID){
        $this->db = db::getInstance();
        $this->id = $id;
        $this->corporationID = $corporationID;
        $this->allianceID = $allianceID;
        $this->getMoreInfoFromDB();
        if($send_email || $send_jabber){
            $txt = $this->getNotifications();
            if($txt != NULL){
                if($send_email) new email->sendmail($this->email, "New EvE Online notification update", date(DATE_RFC822) . " New notifications arrived.\n" . $txt);
                // if($send_jabber) метод отправки в жабер 
                $query = "UPDATE `users` SET `lastNotifID` = '$this->lastNotifID' WHERE `id`='$this->id'";
                $result = $this->db->query($query);
            }
        }
    }

    private function getMoreInfoFromDB(){
        $query = "SELECT `accessMask`, `email`, `lastNotifID` FROM `users` WHERE `id` = '$this->id'";
        $result = $this->db->query($query);
        $arr = $this->db->fetchAssoc($result);
        $this->email = $arr[email];
        $this->lastNotifID = $arr[lastNotifID];
        // send_email and send_jabber
    }

    private function getNotifications(){
        $mailtext = NULL;
        // условия для выборки скул запроса
        $query = "SELECT `notificationID`, `typeID`, `sentDate`, `NotificationText` FROM `notifications` WHERE `notificationID` > '$this->lastNotifID'"; // corporationID allianceID
        $result = $this->db->query($query);
        $notifArr = $this->db->fetchArray($result);
        for ($j = 0; $j < $this->db->countRows($result); $j++){
            if($notifArr[$j][typeID]==76){
                $tmparr = yaml_parse($notifArr[$j][NotificationText]);
                for($h=0; $h < count($tmparr[wants]); $h++){
                    if($tmparr[wants][$h][typeID] = 4246 || $tmparr[wants][$h][typeID] = 4247 || $tmparr[wants][$h][typeID] = 4051 || $tmparr[wants][$h][typeID] = 4312){ // Fuel Block ids
                        $fuelph = $yhis->getFuelPH($tmparr[typeID]);
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
        $query = "SELECT `fuelph` FROM `poslist` WHERE `typeID` = '$typeID' LIMIT 1";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result, 0);
    }

    private function GenerateMailText($type, $sentDate, $str){
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
        } else{
            if($type == 37 || $type == 38) $mailtext .= "Sovereignty claim fails in " . $strarr[solarSystemName] . "\n";
            if($type == 39 || $type == 40) $mailtext .= "Sovereignty bill late in " . $strarr[solarSystemName] . "\n";
            if($type == 41 || $type == 42) $mailtext .= "Sovereignty claim lost in " . $strarr[solarSystemName] . "\n";
            if($type == 43 || $type == 44) $mailtext .= "Sovereignty claim acquired in " . $strarr[solarSystemName] . "\n";
            if($type == 45) $mailtext .= "Control tower anchored in " . $strarr[solarSystemName] . "\n";
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
