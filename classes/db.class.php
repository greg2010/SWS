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
        try {
            if(mysqli_num_rows($result)>0) {
                return true;
            } else {
                return false;
            }
        } catch (Exception $e) {
            return $e;
        }
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function countRows($result) {
        try {
            return mysqli_num_rows($result);
        } catch (Exception $e) {
            return $e;
        }            
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function affectedRows($result) {
        try {
            return mysqli_affected_rows($result);
        } catch (Exception $e) {
            return $e;
        }            
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function fetchAssoc($result) {
        try {
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
        } catch (Exception $e) {
            return $e;
        }
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function fetchArray($result) {
        try {
            $arrays = array();
            while ($array = mysqli_fetch_array($result)) {
                $arrays[] = $array;
            }
            return $arrays;
        } catch (Exception $e) {
            return $e;
        }
    }

    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function fetchRow($result) {
        try {
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
        } catch (Exception $e) {
            return $e;
        }
    }
    
    /**
     * 
     * @param object $result
     * @param int $i
     * @return \Exception
     */
    
    public function getMysqlResult($result, $i = NULL) {
        try {
            $row = $this->fetchRow($result);
            if ($i) {
                return $row[$i];
            } else {
                return $row[0];
            }
        } catch (Exception $e) {
            return $e;
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
        try {
            if(!mysqli_ping($this->connection)) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
            return $e;
        }
    }
    
    /**
     * 
     * @param object $result
     * @return \Exception
     */
    
    public function toArray($result) {
        $results = array();
        try {
            while(($row = $result->fetch_assoc()) != false) {
                $results[] = $row;
            }
            return $results;
        } catch (Exception $e) {
            return $e;
        }
    }
    
    private function predefinedMySQLLogin($login, $passwordHash) {
        $stmt = mysqli_prepare($this->connection, "SELECT `id` FROM `users` WHERE `login`=? AND `passwordHash`=?");
        mysqli_stmt_bind_param($stmt, "ss", $login, $passwordHash);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $id);
        $success = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($success === True) {
            return $id;
        } else {
            $id = False;
            return $id;
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
        mysqli_stmt_bind_result($stmt, $id);
        $success = mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        if ($success === True) {
            return $id;
        } else {
            $id = False;
            return $id;
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
}