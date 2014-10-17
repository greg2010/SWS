<?php

use Pheal\Pheal;
use Pheal\Core\Config as PhealConfig;

class APIOrgManagement {
    
    private $orgManagement;

    public function __construct() {
        PhealConfig::getInstance()->cache = new \Pheal\Cache\FileStorage(dirname(__FILE__) . '/../phealcache/');
        $this->orgManagement = new orgManagement();
    }

    public function getCorporationTicker($id){
        $Ticker = NULL;
        try{
            $Ticker = $this->orgManagement->getCorporationTicker($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getCorporationTicker " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Ticker != NULL){
            return $Ticker;
        } else{
            $pheal = new Pheal(NULL, NULL, "corp");
            $response = $pheal->CorporationSheet(array("corporationID" => $id));
            try{
                $this->orgManagement->recordCorporationInfo($id, $response->corporationName, $response->ticker);
            } catch(Exception $ex){
                throw new \Pheal\Exceptions\PhealException("recordCorporationInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
            }
            return $response->ticker;
        }
    }

    public function getCorporationName($id){
        $Name = NULL;
        try{
            $Name = $this->orgManagement->getCorporationName($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getCorporationName " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Name != NULL){
            return $Name;
        } else{
            $pheal = new Pheal(NULL, NULL, "corp");
            $response = $pheal->CorporationSheet(array("corporationID" => $id));
            try{
                $this->orgManagement->recordCorporationInfo($id, $response->corporationName, $response->ticker);
            } catch(Exception $ex){
                throw new \Pheal\Exceptions\PhealException("recordCorporationInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
            }
            return $response->corporationName;
        }
    }

    public function getCorporationID($name){
        $ID = NULL;
        try{
            $ID = $this->orgManagement->getCorporationID($name);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getCorporationID " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($ID != NULL){
            return $ID;
        } else{
            $pheal = new Pheal(NULL, NULL, "eve");
            $response = $pheal->CharacterID(array("names" => $name));
            $ID = $response->characters[0]->characterID;
            $pheal = new Pheal(NULL, NULL, "corp");
            $response = $pheal->CorporationSheet(array("corporationID" => $ID));
            try{
                $this->orgManagement->recordCorporationInfo($ID, $name, $response->ticker);
            } catch(Exception $ex){
                throw new \Pheal\Exceptions\PhealException("recordCorporationInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
            }
            return $ID;
        }
        return 0;
    }

    public function getAllianceTicker($id){
        $Ticker = NULL;
        try{
            $Ticker = $this->orgManagement->getAllianceTicker($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getAllianceTicker " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Ticker != NULL){
            return $Ticker;
        } else{
            $pheal = new Pheal(NULL, NULL, "eve");
            $response = $pheal->AllianceList();
            foreach($response->alliances as $row){
                if($row->allianceID == $id){
                    try{
                        $this->orgManagement->recordAllianceInfo($id, $row->name, $row->shortName);
                    } catch(Exception $ex){
                        throw new \Pheal\Exceptions\PhealException("recordAllianceInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
                    }
                    return $row->shortName;
                }
            }
        }
    }

    public function getAllianceName($id){
        $Name = NULL;
        try{
            $Name = $this->orgManagement->getAllianceName($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getAllianceName " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Name != NULL){
            return $Name;
        } else{
            $pheal = new Pheal(NULL, NULL, "eve");
            $response = $pheal->AllianceList();
            foreach($response->alliances as $row){
                if($row->allianceID == $id){
                    try{
                        $this->orgManagement->recordAllianceInfo($id, $row->name, $row->shortName);
                    } catch(Exception $ex){
                        throw new \Pheal\Exceptions\PhealException("recordAllianceInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
                    }
                    return $row->name;
                }
            }
        }
    }

    public function getAllianceID($name){
        $ID = NULL;
        try{
            $ID = $this->orgManagement->getAllianceID($name);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getAllianceID " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($ID != NULL){
            return $ID;
        } else{
            $pheal = new Pheal(NULL, NULL, "eve");
            $response = $pheal->AllianceList();
            foreach($response->alliances as $row){
                if($row->name == $name){
                    try{
                        $this->orgManagement->recordAllianceInfo($row->allianceID, $name, $row->shortName);
                    } catch(Exception $ex){
                        throw new \Pheal\Exceptions\PhealException("recordAllianceInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
                    }
                    return $row->allianceID;
                }
            }
        }
        return 0;
    }

    public function getAllianceByCorporation($id){
        $pheal = new Pheal(NULL, NULL, "corp");
        $response = $pheal->CorporationSheet(array("corporationID" => $id));
        return $response->allianceID;
    }

    public function getAllianceList(){
        $pheal = new Pheal(NULL, NULL, "eve");
        $response = $pheal->AllianceList();
        foreach($response->alliances as $alliance){
            unset($corplist);
            foreach($alliance->memberCorporations as $corporation){
                $corplist[] = $corporation->corporationID;
            }
            $list[] = array(
                "id" => $alliance->allianceID,
                "name" => $alliance->name,
                "ticker" => $alliance->shortName,
                "corporations" => $corplist
            );
        }
        return $list;
    }

    public function updateAllianceList($alliance = array()){
        $log = new logging();
        try{
            $Name = $this->orgManagement->getAllianceName($alliance[id]);
        } catch(Exception $ex){
            $log->put("getAllianceName", "err " . $ex->getMessage());
        }
        if($Name == NULL){
            try{
                $this->orgManagement->recordAllianceInfo($alliance[id], $alliance[name], $alliance[ticker]);
                $log->put("recordAllianceInfo", "ok " . $alliance[name] . " [" . $alliance[ticker] . "]");
            } catch(Exception $ex){
                $log->put("recordAllianceInfo", "err " . $ex->getMessage());
            }
        }
        foreach($alliance[corporations] as $corporation){
            try{
                $corp = $this->UpdateCorporationList($corporation);
                if($corp != NULL){
                    $log->put($corporation, "ok " . $corp[name] . " [" . $corp[ticker] . "]");
                }
            } catch (\Pheal\Exceptions\PhealException $e){
                $log->put("UpdateCorporationList", "err " . $e->getMessage(), $corporation);
            }
        }
        return $log->get();
    }

    public function UpdateCorporationList($id){
        $Name = NULL;
        try{
            $Name = $this->orgManagement->getCorporationName($id);
        } catch(Exception $ex){
            throw new \Pheal\Exceptions\PhealException("getCorporationName " . $ex->getMessage(), ($ex->getCode())*-1000);
        }
        if($Name == NULL){
            $pheal = new Pheal(NULL, NULL, "corp");
            $response = $pheal->CorporationSheet(array("corporationID" => $id));
            try{
                $this->orgManagement->recordCorporationInfo($id, $response->corporationName, $response->ticker);
            } catch(Exception $ex){
                throw new \Pheal\Exceptions\PhealException("recordCorporationInfo " . $ex->getMessage(), ($ex->getCode())*-1000);
            }
            return $corporation = array("name" => $response->corporationName, "ticker" => $response->ticker);
        } else return NULL;
    }
}

?>
