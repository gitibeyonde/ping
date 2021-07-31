<?php

class RegistryPort
{
    public $port=null;
    public $serverip = null;
    public $uuid=null;
    public $updated=null;
    
    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();

    public function __construct()
    {
    }

    public function init(){
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $stmt = $this->db_connection->prepare('/*qc=on*/'.'SELECT count(*) FROM registry_port');
            $stmt->execute();
            $count = $stmt->fetch()[0];
            if ($count == 0){
                // initialize ports
                //error_log("Initialize ports--");
                for ($i=NetUtils::PORT_START; $i < NetUtils::PORT_END; $i++){
                    $sql="insert into registry_port values ( $i, NULL, now());";
                    //error_log($sql);
                    $this->db_connection->exec($sql);
                }
                //error_log("Initialize ports complete--");
            }
        }
        else {
            throw new Exception(" Database connection error");
        }
    }
    
    private function databaseConnection()
    {
        // connection already opened
        if ($this->db_connection != null) {
            return true;
        } else {
            // create a database connection, using the constants from config/config.php
            try {
                $this->db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                return true;
            } catch (PDOException $e) {
                $this->errors[] = MESSAGE_DATABASE_ERROR;
                return false;
            }
        }
    }
    

    public function getIpAndPort($uuid)
    {
        if (!isset($uuid)){
            throw new Exception(" Invalid uuid ".$uuid);
        }
        if ($this->databaseConnection()) {
            //error_log("Find port mapping for ".$uuid);
            $stmt = $this->db_connection->prepare('/*qc=on*/'.'SELECT  * FROM registry_port where uuid = :uuid limit 2');
            $stmt->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $stmt->setFetchMode(PDO::FETCH_CLASS, 'RegistryPort');
            $stmt->execute();
            if ($stmt->rowCount() == 1){
                $port_entry = $stmt->fetch();
                //error_log("Found port mapping ".$port_entry->port);
                    return array($port_entry->serverip, $port_entry->port);
            }
            else if ($stmt->rowCount() > 1){
                //error_log(" Fatal more than one row found for device ".$uuid);
                throw new Exception(" Fatal more than one row found for device ".$uuid);
            }
            else {
                $fp = fopen("/tmp/registryport", 'w+');
                if(flock($fp, LOCK_EX)) {
                    $stmt2 = $this->db_connection->prepare('/*qc=on*/'.'SELECT  * FROM registry_port where uuid is :uuid limit 1 for update');
                    $stmt2->bindValue(':uuid', null, PDO::PARAM_INT);
                    $stmt2->setFetchMode(PDO::FETCH_CLASS, 'RegistryPort');
                    $stmt2->execute();
                    $port_entry = $stmt2->fetch();
                    //error_log("Error=".implode(",", $stmt2->errorInfo()). " row count=".$stmt2->rowCount());
                    //error_log("Empty port found ".$port_entry->port);
                    
                    $stmt3 = $this->db_connection->prepare('update registry_port set uuid = :uuid where port = :port and serverip = :serverip');
                    $stmt3->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
                    $stmt3->bindValue(':port', $port_entry->port, PDO::PARAM_STR);
                    $stmt3->bindValue(':serverip', $port_entry->serverip, PDO::PARAM_STR);
                    $stmt3->execute();
                    //error_log("Error=".implode(",", $stmt3->errorInfo()));
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    return array($port_entry->serverip, $port_entry->port);
                } 
            }
            
        }
    }
    
    

}
