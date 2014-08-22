<?php


/**
 * Description of APIUserManagement
 *
 * @author greg2010
 */


interface IAPIUserManagement {
    
    function getUserKeyMask();
    function changeUserApiKey($keyID, $vCode);
    
}

class APIUserManagement extends permissions implements IAPIUserManagement {

    public function __construct($id) {
        parent::__construct($id);
        
    }
    public function changeUserApiKey($keyID, $vCode) {
        
    }
    public function getUserKeyMask() {
        
    }
    private function verifyApiInfo() {
        
    }
}