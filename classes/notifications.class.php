<?php

use Pheal\Pheal;

class notifications {

    protected $keyID;
    protected $vCode;
    protected $characterID;
    protected $corporationID;
    protected $allianceID;
    private $log;
    private $db;
    private $notif = array();

	public function __construct($keyID, $vCode, $characterID, $corporationID, $allianceID){
        $this->keyID = $keyID;
        $this->vCode = $vCode;
        $this->characterID = $characterID;
        $this->corporationID = $corporationID;
        $this->allianceID = $allianceID;
        $this->db = db::getInstance();
        $this->log = new logging();
    }

    private function getNotificationsXML(){       
        $pheal = new Pheal($this->keyID, $this->vCode, "char");
        try{
            $response = $pheal->Notifications(array("characterID" => $this->characterID));
            foreach($response->notifications as $row){
                $rpt = false;
                $rtype = false;
                $typearr = explode(", ", config::notif_types);
                if($this->rptCheck($row[notificationID])){
                    $rpt = true;
                } else{
                    for($j = 0; $j < count($typearr); $j++){
                        if(strval($row[typeID])==$typearr[$j]){
                            $rtype = true;
                            for($h = 0; $h < count($this->notif); $h++){
                                if($this->notif[$h]['notificationID']==$row[notificationID]){
                                    $rpt = true;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                if(!$rpt && $rtype){
                    $this->notif[] = array(
                        'notificationID' => $row[notificationID],
                        'typeID' => $row[typeID],
                        'senderID' => $row[senderID],
                        'senderName' => $row[senderName],
                        'sentDate' => $row[sentDate]
                    );
                    $i++;
                }
            }
            return true;
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getNotificationsXML", "err " . $e->getMessage());
            return false;
        }
    }

    private function rptCheck($notificationID){
        try {
            $query = "SELECT `notificationID` FROM `notifications` WHERE `notificationID` = '$notificationID' LIMIT 1";
            $result = $this->db->query($query);
            return ($this->db->hasRows($result)) ? true : false;
        } catch (Exception $ex) {
            $this->log->put("rptCheck", "err " . $ex->getMessage());
            return false;
        }
    }

    private function getNotificationTextsXML($notificationID){         
        $pheal = new Pheal($this->keyID, $this->vCode, "char");
        try{
            $response = $pheal->NotificationTexts(array("characterID" => $this->characterID, "IDs" => $notificationID));
            foreach($response->notifications as $row){
                foreach($this->notif as $key){
                    if($key['notificationID'] == $row->notificationID){
                        $arrpos = array_search($key, $this->notif);
                        $notiftext = ($this->notif[$arrpos]['typeID']==76) ? (new snotif((string)$row)) : (new snotif((string)$row, $this->corporationID, $this->allianceID));
                        $this->log->merge($notiftext->log->get(true), "getNotificationTextsXML, id: " . $row->notificationID);
                        $this->notif[$arrpos]['NotificationText'] = $notiftext->getText();
                    }
                }
            }
        } catch (\Pheal\Exceptions\PhealException $e){
            $this->log->put("getNotificationTextsXML " . $notificationID, "err " . $e->getMessage());
        }
    }

    private function insertToDB(){
        try {
            foreach ($this->notif as $n){
                $notixtxttosql = addslashes($n['NotificationText']);
                $query = "INSERT INTO `notifications` SET `notificationID` = '{$n['notificationID']}', `typeID` = '{$n['typeID']}', `senderID` = '{$n['senderID']}', `senderName` = '{$n['senderName']}',
                 `sentDate` = '{$n['sentDate']}', `NotificationText` = '$notixtxttosql', `corporationID` = '$this->corporationID', `allianceID` = '$this->allianceID'";
                $result = $this->db->query($query);
            }
        } catch (Exception $ex) {
            $this->log->put("insertToDB", "err " . $ex->getMessage());
        }
    } 

    public function processNotif(){
        if($this->getNotificationsXML() && count($this->notif) > 0){
            for($i=0; $i<count($this->notif); $i++){
                $ids .= ($i == count($this->notif) - 1) ? ($this->notif[$i][notificationID]) : ($this->notif[$i][notificationID] . ",");
            }
            $this->getNotificationTextsXML($ids);
            $this->insertToDB();
        }
        return $this->log->get();
    }
}

?>
