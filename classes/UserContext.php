<?php

require_once(__ROOT__.'/classes/Device.php');

class UserContext
{
    public $user_name = null;
    public $user_email = null;
    public $devices = array();
    public $boxes = array();
    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();

    public function __construct($user_name, $user_email)
    {
        $this->user_name=$user_name;
        $this->user_email=$user_email;
        $this->loadUserDevices();
        $this->loadDeviceBoxes();
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
    
    public function getDevices(){
        return  $this->devices;
    }

    public function getBoxes(){
        return  $this->boxes;
    }
    
    public function getDevice($uuid){
        foreach ($this->devices as $device) {
            if ($device->uuid == $uuid ){
                return $device;
            }
        }
        return  null;
    }
    
    public function createBox($username, $box){
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_user = $this->db_connection->prepare('insert into device_box value( :username, :box, now())');
            $query_user->bindValue(':username', $username, PDO::PARAM_STR);
            $query_user->bindValue(':box', $box, PDO::PARAM_STR);
            $query_user->execute();        
        }
        $this->loadDeviceBoxes();
    }

    public function deleteBox($username, $box){
        if ($this->databaseConnection()) {
            // move all devices to default
            $updqr = $this->db_connection->prepare('update device set box_name=:box_default where user_name=:username and box_name=:box');
            $updqr->bindValue(':username', $username, PDO::PARAM_STR);
            $updqr->bindValue(':box_default', 'default', PDO::PARAM_STR);
            $updqr->bindValue(':box', $box, PDO::PARAM_STR);
            $updqr->execute();
            // database query, getting all the info of the selected user
            $delqr = $this->db_connection->prepare('delete from device_box where user_name=:username and box_name=:box');
            $delqr->bindValue(':username', $username, PDO::PARAM_STR);
            $delqr->bindValue(':box', $box, PDO::PARAM_STR);
            $delqr->execute();
        }
        $this->loadDeviceBoxes();
    }
    

    public function moveDeviceToBox($username, $uuid, $box){
        if ($this->databaseConnection()) {
            // move all devices to default
            $updqr = $this->db_connection->prepare('update device set box_name=:box where user_name=:username and uuid=:uuid');
            $updqr->bindValue(':username', $username, PDO::PARAM_STR);
            $updqr->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $updqr->bindValue(':box', $box, PDO::PARAM_STR);
            $updqr->execute();
        }
        $this->loadDeviceBoxes();
    }
    
    public function changeDeviceName($device_name, $uuid){
        if ($this->databaseConnection()) {
            // move all devices to default
            $updqr = $this->db_connection->prepare('update device set device_name=:device_name where uuid=:uuid');
            $updqr->bindValue(':device_name', $device_name, PDO::PARAM_STR);
            $updqr->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $updqr->execute();
        }
    }

    public function changeShareName($share_name, $uuid){
        if ($this->databaseConnection()) {
            // move all devices to default
            $updqr = $this->db_connection->prepare('update motion_last set share_as=:share_name where uuid=:uuid');
            $updqr->bindValue(':share_name', $share_name, PDO::PARAM_STR);
            $updqr->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $updqr->execute();
        }
    }
    
    public function removeShareName($uuid){
        if ($this->databaseConnection()) {
            // move all devices to default
            $updqr = $this->db_connection->prepare('update motion_last set share_as = NULL where uuid=:uuid');
            $updqr->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $updqr->execute();
        }
    }
    
    public function loadUserDevices()
    {
        // if database connection opened
        $devices = array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_user = $this->db_connection->prepare('SELECT * FROM device WHERE user_name = :user_name order by updated desc');
            $query_user->bindValue(':user_name', $this->user_name, PDO::PARAM_STR);
            $query_user->setFetchMode(PDO::FETCH_CLASS, 'Device'); 
            $query_user->execute();
            while($obj = $query_user->fetch()){
                $devices[]=$obj;
            }
        } 
        $this->devices = $devices;
        return $devices;
    }

    private function loadDeviceBoxes()
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_user = $this->db_connection->prepare('SELECT box_name FROM device_box WHERE user_name = :user_name');
            $query_user->bindValue(':user_name', $this->user_name, PDO::PARAM_STR);
            $query_user->execute();
            $this->boxes  = $query_user->fetchAll(PDO::FETCH_COLUMN);
        }
    }
    

    
}
