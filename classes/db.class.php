<?php

class db {
    private $connection;
    private $selectdb;
    private $lastQuery;
    private $config = array();
    private static $_instance = null;
    
    static public function getInstance() {
        if(is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $this->config = array (
                'hostname' => config::hostname,
                'username' => config::username,
                'password' => config::password,
                'database' => config::database
        );
    }
    
    /**
     * 
     * @param string $var
     * @return string
     */
    public function sanitizeString($var) {
        if(empty($this->connection)) {
            $this->openConnection();
            $var = mysqli_real_escape_string($this->connection, $var);
            return $var;
        } else {
            $var = mysqli_real_escape_string($this->connection, $var);
            return $var;
        }
    }
    
    /**
     *
     * @return object
     * @throws mysqli_sql_exception
     */
    
    public function openConnection() {
        $this->connection = mysqli_connect($this->config[hostname], $this->config[username], $this->config[password]);
        $this->selectdb = mysqli_select_db($this->connection, $this->config[database]);
        if (mysqli_connect_error()) {
            throw new Exception(mysqli_connect_error(), mysqli_connect_errno());
        }
    }

    /**
     *
     * @return \Exception
     */
    
    public function closeConnection() {
        try {
            mysqli_close($this->connection);
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     *
     * @param string $query
     * @return string|\Exception
     */
    
    public function query($query) {
        if(empty($this->connection)) {
            $this->openConnection();
            $this->lastQuery = mysqli_query($this->connection, $query);
            if (mysqli_error($this->connection)) {
                throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
            } else {
                return $this->lastQuery;
            }
            $this->closeConnection();
        } else {
            $this->lastQuery = mysqli_query($this->connection, $query);
            if (mysqli_error($this->connection)) {
                throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
            } else {
                return $this->lastQuery;
            }
        }
    }
    

    /**
     * 
     * @return \Exception|boolean
     */
    
    public function hasRows($result) {
        if (gettype($result)<> "object") {
            throw new Exception("hasRows: Wrong input type. Object expected, " . gettype($result) . " given.");
        }
        if(mysqli_num_rows($result)>0) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function countRows($result) {
        if (gettype($result)<> "object") {
            throw new Exception("countRows: Wrong input type. Object expected, " . gettype($result) . " given.");
        }
        return mysqli_num_rows($result);         
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function affectedRows($result) {
        if (gettype($result)<> "object" OR $result <> TRUE) {
            throw new Exception("affectedRows: Wrong input type. Object or boolean true expected, " . var_dump($result) . " given.");
        }
        return mysqli_affected_rows($result);       
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function fetchAssoc($result) {
        if (gettype($result)<> "object") {
            throw new Exception("fetchAssoc: Wrong input type. Object expected, " . gettype($result) . " given.");
        }
        $assoc = array();
        $numRows =$this->countRows($result);
        if ($numRows === 1) {
            $assoc = mysqli_fetch_assoc($result);
        } else {
            while ($array = mysqli_fetch_assoc($result)) {
                $assoc[] = $array;
            }
        }
        return $assoc;
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function fetchArray($result) {
        if (gettype($result)<> "object") {
            throw new Exception("fetchArray: Wrong input type. Object expected, " . gettype($result) . " given.");
        }
        $arrays = array();
        while ($array = mysqli_fetch_array($result)) {
            $arrays[] = $array;
        }
        return $arrays;
    }

    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function fetchRow($result) {
        if (gettype($result)<> "object") {
            throw new Exception("fetchRow: Wrong input type. Object expected, " . gettype($result) . " given.");
        }
        $rows = array();
        $numRows =$this->countRows($result);
        if ($numRows === 1) {
            $rows = mysqli_fetch_row($result);
        }  else {
            while ($array = mysqli_fetch_row($result)) {
                $rows[] = $array;
            }
        }
        return $rows;
    }
    
    /**
     * 
     * @param object $result
     * @param int $i
     * @return \Exception
     */
    
    public function getMysqlResult($result, $i = NULL) {
        if (gettype($result)<> "object") {
            throw new Exception("getMysqlResult: Wrong input type. Object expected, " . gettype($result) . " given.");
        }
        $row = $this->fetchRow($result);
        foreach ($row as $value) {
            if(gettype($value) == "array") throw new Exception("getMysqlResult: Wrong input type. Array deeper than expected.");
        }
        /*if (count($row) > 1) {
            throw new Exception("getMysqlResult: Wrong input type. Expected 1 row, got " . count($row) . " row(s).");
        }*/
        if ($i) {
            return $row[$i];
        } else {
            return $row[0];
        }
    }
    
    /**
     * 
     * @return object
     */
    
    public function lastQuery() {
        return $this->lastQuery;
    }
    
    /**
     * 
     * @return \Exception|boolean
     */
    
    public function pingServer() {
        if(!mysqli_ping($this->connection)) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function toArray($result) {
        $results = array();
        while(($row = $result->fetch_assoc()) != false) {
            $results[] = $row;
        }
        return $results;
    }
    
    private function predefinedMySQLLogin($login, $passwordHash) {
        $stmt = mysqli_prepare($this->connection, "SELECT `id` FROM `users` WHERE `login`=? AND `passwordHash`=?");
        mysqli_stmt_bind_param($stmt, "ss", $login, $passwordHash);
        mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_bind_result($stmt, $id);
        $success = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($id) {
            return $id;
        } else {
            return False;
        }
    }
    
    private function predefinedMySQLCheckIfUserRegistered($login) {
        $stmt = mysqli_prepare($this->connection, "SELECT `id` FROM `users` WHERE `login`=?");
        mysqli_stmt_bind_param($stmt, "s", $login);
        mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_bind_result($stmt, $id);
        $success = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($id) {
            return $id;
        } else {
            return False;
        }
    }
    
    private function predefinedMySQLCheckIfApiIsInDB($characterID) {
        $stmt = mysqli_prepare($this->connection, "SELECT `id` FROM `apiPilotList` WHERE `characterID`=?");
        mysqli_stmt_bind_param($stmt, "s", $characterID);
        mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_bind_result($stmt, $id);
        $success = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($id) {
            return $id;
        } else {
            return False;
        }
    }
    
    private function predefinedMySQLCookie($cookie) {
        $query = "SELECT `id` FROM `userCookies` WHERE ";
        $cookieNumber = config::cookieNumber;
        for ($i = 0; $i<=$cookieNumber; $i++) {
            $fields .= '`cookie' . $i . "`=?";
            if ($i<$cookieNumber) {
                $fields .= ' OR ';
            } else {
                $fields .= ' ';
            }
        }
        $query .= $fields;
        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $cookie, $cookie, $cookie, $cookie, $cookie); //Repeat $cookie as many times as there cookies fields
        mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_bind_result($stmt, $id);
        $success = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($id) {
            return $id;
        } else {
            return False;
        }
    }
    
    private function predefinedMySQLFindIDByEmail($email) {
        $stmt = mysqli_prepare($this->connection, "SELECT `id` FROM `users` WHERE `email`=?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception(mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_bind_result($stmt, $id);
        $success = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($id) {
            return $id;
        } else {
            return False;
        }
    }
    
    private function predefinedMySQLChangeEmail($id, $email) {
        $stmt = mysqli_prepare($this->connection, "UPDATE `users` SET `email`=? WHERE `id`=?");
        mysqli_stmt_bind_param($stmt, "ss", $email, $id);
        $success = mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception("predefinedMySQLChangeEmail: " . mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_close($stmt);
    }


    private function predefinedPopulateUsers($login, $passwordHash, $accessMask, $salt, $email = NULL) {
        $this->openConnection();
        $stmt = mysqli_prepare($this->connection, "INSERT INTO `users` SET `login`=?, `passwordHash`=?, `accessMask`=?, `email`=?, `salt`=?");
        mysqli_stmt_bind_param($stmt, "sssss", $login, $passwordHash, $accessMask, $email, $salt);
        $success = mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception("predefinedPopulateUsers: " . mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_close($stmt);
        $this->closeConnection();
        return $success;
    }
    
    private function predefinedGetMainApiKey($id) {
        $stmt = mysqli_prepare($this->connection, "SELECT `characterID` FROM `apiPilotList` WHERE `id`=? AND `keyStatus` = '1'");
        mysqli_stmt_bind_param($stmt, "s", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $characterID);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($characterID) {
            return $characterID;
        } else {
            return FALSE;
        }
        
    }
    
    private function predefinedAddApiKey($id, $keyID, $vCode, $characterID, $keyType) {
        $stmt = mysqli_prepare($this->connection, "SELECT `id`, `keyStatus` FROM `apiPilotList` WHERE `characterID`=?");
        mysqli_stmt_bind_param($stmt, "s", $characterID);
        mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception("predefinedAddApiKeySelect: " . mysqli_error($this->connection), mysqli_errno($this->connection));
        }
        mysqli_stmt_bind_result($stmt, $ownerID, $keyStatus);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        var_dump($ownerID);
        if ($ownerID) {
            switch ($keyStatus) {
                case 0:
                    $stmt = mysqli_prepare($this->connection, "UPDATE `apiPilotList` SET `id`=?, `keyID`=?, `vCode`=?, `keyStatus`=? WHERE `characterID`=?");
                    mysqli_stmt_bind_param($stmt, "sssss", $id, $keyID, $vCode, $keyType, $characterID);
                    mysqli_stmt_execute($stmt);
                    if (mysqli_error($this->connection)) {
                        throw new Exception("predefinedAddApiKeyUpdate: " . mysqli_error($this->connection), mysqli_errno($this->connection));
                    }
                    $validation = new validation();
                    $validation->updatePilotInfo($characterID, $keyID, $vCode);
                    break;
                case 1:
                case 2:
                    throw new Exception("Key is already used!", 22);
                default :
                    throw new Exception("Unkown keyStatus!", 30);
            }
        } else {
            $stmt = mysqli_prepare($this->connection, "INSERT INTO `apiPilotList` SET `id`=?, `keyID`=?, `vCode`=?, `characterID`=?, `keyStatus`=?");
            mysqli_stmt_bind_param($stmt, "sssss", $id, $keyID, $vCode, $characterID, $keyType);
            mysqli_stmt_execute($stmt);
            if (mysqli_error($this->connection)) {
                throw new Exception("predefinedAddApiKeyInsert: " . mysqli_error($this->connection), mysqli_errno($this->connection));
            }
            $validation = new validation();
            $validation->updatePilotInfo($characterID, $keyID, $vCode);
            mysqli_stmt_close($stmt);
        }
    }
    
    private function predefinedDeleteApiKey($characterID) {
        $stmt = mysqli_prepare($this->connection, "UPDATE `apiPilotList` SET `keyStatus`='0' WHERE `characterID`=?");
        mysqli_stmt_bind_param($stmt, "s", $characterID);
        mysqli_stmt_execute($stmt);
        if (mysqli_error($this->connection)) {
            throw new Exception("predefinedAddApiKey: " . mysqli_error($this->connection), mysqli_errno($this->connection));
        }
    }

    public function getUserByLogin($login, $passwordHash) {
        $this->openConnection();
        $id = $this->predefinedMySQLLogin($login, $passwordHash);
        return $id;
    }
    
    public function getUserByCookie($cookie) {
        $this->openConnection();
        $id = $this->predefinedMySQLCookie($cookie);
        return $id;
    }
    
    public function updateEmail($id, $email) {
        $this->openConnection();
        $this->predefinedMySQLChangeEmail($id, $email);
    }
    
    public function getIDByEmail($email) {
        $this->openConnection();
        return $this->predefinedMySQLFindIDByEmail($email);
    }
    
    public function getIDByName($login) {
        $this->openConnection();
        return $this->predefinedMySQLCheckIfUserRegistered($login);
    }
    
    public function checkIfCharRegistered($characterID) {
        $this->openConnection();
        return $this->predefinedMySQLCheckIfApiIsInDB($characterID);
    }
    
    public function addSecApi ($id, $keyID, $vCode, $characterID) {
        $this->openConnection();
        $keyType = 2;
        $this->predefinedAddApiKey($id, $keyID, $vCode, $characterID, $keyType);
    }
    
    public function changeMainAPI($id, $keyID, $vCode, $characterID) {
        $this->openConnection();
        $oldCharacterID = $this->predefinedGetMainApiKey($id);
        $this->predefinedDeleteApiKey($oldCharacterID);
        
        $keyType = 1;
        $this->predefinedAddApiKey($id, $keyID, $vCode, $characterID, $keyType);
    }

    public function deleteAPI($ownerID, $characterID) {
        $this->openConnection();
        $id = $this->predefinedMySQLCheckIfApiIsInDB($characterID);
        if ($id <> $ownerID) {
            throw new Exception('Wrong characterID!', 13);
        }
        $this->predefinedDeleteApiKey($characterID);
    }
    public function registerNewUser($keyID, $vCode, $characterID, $characterName, $passwordHash, $permissions, $salt, $email = NULL) {
        try {
            $this->predefinedPopulateUsers($characterName, $passwordHash, $permissions, $salt, $email);
            $id = $this->getIDByName($characterName);
            $keyType = 1;
            $this->predefinedAddApiKey($id, $keyID, $vCode, $characterID, $keyType);
        } catch (Exception $ex) {
            $firstException = $ex->getMessage();
            //Rolling Back...
            try {
                $this->predefinedDeleteApiKey($characterID);
                $query = "DELETE FROM `users` WHERE `login` = '$characterName'";
                $this->query($query);
            } catch (Exception $ex) {
                throw new Exception("Something went terribly wrong. Message: " . $firstException . " Attempt to roll back failed, exception: " . $ex->getMessage(), 30);
            }
            switch ($ex->getCode()) {
                case -1:
                case 30:
                    throw new Exception($ex->getMessage(), $ex->getCode());
                default :
                    throw new Exception("MySQL error: " . $firstException . " Rolled back successfully.", 30);
            }
        }
    }
}