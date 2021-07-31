<?php

define("DEVICE_SIP", "device");
define("WEB_APP_SIP", "webapp");
define("MOBILE_SIP", "mobile");
define("OTHER_SIP", "app");

class Sip
{
    public $uuid = null;
    public $sip = null;
    public $context = null;
    public $type = null;
    public $dev_uuid = null;
    public $valid = null;
    public $created = null;
    public $updated = null;

    private $db_connection            = null;
    

    public function __construct()
    {
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
    
    public function enableVoiceForDevice($device_uuid){
        if ($this->databaseConnection()) {
            if (count($this->loadAllSipForDevice($device_uuid)) == 0 ){
                try {
                    $this->db_connection->beginTransaction();
                    $group=$this->getNextGroup();
                    error_log("Group=".$group);
                    $sip_number=$this->getNextSipNumber();
                    $secret=$this->randomPassword();
                    error_log("SipNumber=".$sip_number);
                    
                    $this->createSipUser($device_uuid, $sip_number, $secret, $group);
                    $this->db_connection->commit();
                }
                catch(Exception $e){
                    $this->db_connection->rollBack();
                    error_log("Unable to initialize voice");
                    return False;
                }
            }
        }
        return True;
    }
 

    public function getNextSipNumber()
    {
        $sip_number=-1;
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("update counter set value=last_insert_id(value+1) where name='sip_number'");
            $query->execute();
            $query = $this->db_connection->prepare("select last_insert_id()");
            $query->execute();
            $sip_number = $query->fetch()[0];
        }
        return $sip_number;
    }
   

    public function loadAllSipForDevice($device_uuid){
        $sips=array();
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare('SELECT * FROM sip WHERE dev_uuid = :dev_uuid');
            $query->bindValue(':dev_uuid', $device_uuid, PDO::PARAM_STR);
            $query->setFetchMode(PDO::FETCH_CLASS, 'Sip');
            $query->execute();
            while($obj = $query->fetch()){
                $sips[]=$obj;
            }
        }
        return $sips;
    }
    
    private function randomPassword() {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }

    private function getNextGroup()
    {
        $group=-1;
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare("update counter set value=last_insert_id(value+1) where name='group'");
            $query->execute();
            $query = $this->db_connection->prepare("select last_insert_id()");
            $query->execute();
            $group = $query->fetch()[0];
        }
        return $group;
    }
    
    private function loadSip($uuid)
    {
        $sip=null;
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare('SELECT * FROM sip WHERE uuid = :uuid');
            $query->bindValue(':uuid', $this->uuid, PDO::PARAM_STR);
            $query->setFetchMode(PDO::FETCH_CLASS, 'Sip');
            $query->execute();
            $sip=$query->fetch();
        }
        return $sip;
    }

    private function createSipUser($device_uuid, $sip_number, $secret, $group){
        if ($this->databaseConnection()) {
            $query = $this->db_connection->prepare('INSERT INTO sip_user (NAME, defaultuser, secret, callerid, context, HOST, nat, qualify, TYPE) VALUES (:sip_number, :sip_number, :secret, :sip_number, :group, :dynamic, :yes, :no, :friend)');
            $query->bindValue(':sip_number', $sip_number, PDO::PARAM_STR);
            $query->bindValue(':secret', $secret, PDO::PARAM_STR);
            $query->bindValue(':group', $group, PDO::PARAM_STR);
            $query->bindValue(':dynamic', 'dynamic', PDO::PARAM_STR);
            $query->bindValue(':yes', 'yes', PDO::PARAM_STR);
            $query->bindValue(':no', 'no', PDO::PARAM_STR);
            $query->bindValue(':friend', 'friend', PDO::PARAM_STR);
            $query->execute();
            $query = $this->db_connection->prepare('INSERT INTO sip ( uuid, sip, secret, context, type, valid, dev_uuid, created, last_update) values (:uuid, :sip_number, :secret, :group, :device, :one, :uuid, now(), now())');
            $query->bindValue(':sip_number', $sip_number, PDO::PARAM_STR);
            $query->bindValue(':uuid', $device_uuid, PDO::PARAM_STR);
            $query->bindValue(':secret', $secret, PDO::PARAM_STR);
            $query->bindValue(':group', $group, PDO::PARAM_STR);
            $query->bindValue(':device', 'device', PDO::PARAM_STR);
            $query->bindValue(':one', 'one', PDO::PARAM_STR);
            $query->execute();
        }
    }

}
