<?php
// include the config
require_once (__ROOT__ . '/config/config.php');
require_once(__ROOT__.'/classes/core/Log.php');
require_once(__ROOT__.'/classes/core/Utils.php');

class Mysql {
    
    protected $db_connection = null;
    protected $log=null;
    
    public function __construct()
    {
        $this->log  = isset($_SESSION['log']) ? $_SESSION['log'] : $GLOBALS['log'];
    }
    
    
    public static function guidv4()
    {
        $data = random_bytes(10);
        return substr(bin2hex($data), 0, 10);
    }
    
    protected function databaseConnection()
    {
        // if connection already exists
        if ($this->db_connection != null) {
            return true;
        } else {
            try {
                $this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                return true;
            } catch (PDOException $e) {
                $_SESSION['message']  = MESSAGE_DATABASE_ERROR . $e->getMessage();
            }
        }
        return false;
    }
    
    public function selectOne($sql){
        if ($this->databaseConnection()) {
            $sth = $this->db_connection->prepare($sql);
            $sth->execute();
            $this->log->trace("ERR=".Utils::flatten($sth->errorInfo())." SQL=".$sql);
            if ( $sth->errorInfo()[0] != "0000"){
                $this->log->fatal("ERR=".Utils::flatten($sth->errorInfo())." SQL=".$sql);
                $_SESSION['message'] = Utils::flatten($sth->errorInfo());
            }
            return $sth->fetch()[0];
        }
    }
    
    public function selectRow($sql){
        if ($this->databaseConnection()) {
            $sth = $this->db_connection->prepare($sql);
            $sth->execute();
            $this->log->trace("ERR=".Utils::flatten($sth->errorInfo())." SQL=".$sql);
            if ( $sth->errorInfo()[0] != "0000"){
                $this->log->fatal("ERR=".print_r($sth->errorInfo(), true)." SQL=".$sql);
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
            return $sth->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    public function selectRows($sql){
        if ($this->databaseConnection()) {
            $res=array();
            $sth = $this->db_connection->prepare($sql);
            $sth->execute();
            $this->log->trace("ERR=".Utils::flatten($sth->errorInfo())." SQL=".$sql);
            if ( $sth->errorInfo()[0] != "0000"){
                $this->log->fatal("ERR=".Utils::flatten($sth->errorInfo())." SQL=".$sql);
                $_SESSION['message'] = Utils::flatten($sth->errorInfo());
            }
            while($obj =  $sth->fetch(PDO::FETCH_ASSOC)){
                $res[]=$obj;
            }
            return $res;
        }
    }
    
    public function changeRow($sql){
        
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare($sql);
            $sth->execute();
            $this->log->trace("ERR=".Utils::flatten($sth->errorInfo())." SQL=".$sql);
            if ( $sth->errorInfo()[0] != "0000"){
                $this->log->fatal("ERR=".Utils::flatten($sth->errorInfo())." SQL=".$sql);
                $_SESSION['message'] = Utils::flatten($sth->errorInfo());
                return false;
            }
        }
        return true;
    }
    
    
    public function quote ($s) {
        if ($this->databaseConnection()) {
            if ($s != null){
                return $this->db_connection->quote($s);
            }
            else {
                return $this->db_connection->quote(" ");
            }
        }
    }
    
    
}

?>