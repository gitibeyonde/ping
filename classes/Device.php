<?php

class Device
{
    public $uuid = null;
    public $user_name = null;
    public $device_name = null;
    public $type = null;
    public $profile = null;
    public $profile_id = null;
    public $box_name = null;
    public $timezone = null;
    public $capabilities = null;
    public $version = null;
    public $setting = null;
    public $email_alerts = null;
    public $deviceip = null;
    public $visibleip = null;
    public $port = null;
    public $created = null;
    public $updated = null;
    public $token = null;

    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();

    public function __construct()
    {
        $this->updated=date(DateTime::ATOM);
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


    public function loadDevice($uuid)
    {
        $device=null;
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('SELECT * FROM device WHERE uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->setFetchMode(PDO::FETCH_CLASS, 'Device');
            $query_device->execute();
            $device = $query_device->fetch();
        }
        return $device;
    }


    public function deleteLastAlerts($uuid)
    {
        //error_log("Deleteing device =".$uuid);
        if ($this->databaseConnection()) {

            $query_device = $this->db_connection->prepare ( 'delete FROM motion_last WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            //error_log("Error=".implode(",", $query_device->errorInfo()));
        }
    }

    
    public function deleteHistory($uuid)
    {
        //error_log("Deleteing device =".$uuid);
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('update registry_port set uuid = NULL where uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();
            $query_device = $this->db_connection->prepare ( 'delete FROM device_cert WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare ( 'delete FROM stat WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare ( 'delete FROM motion_last WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            //$query_device = $this->db_connection->prepare('delete FROM device WHERE uuid = :uuid');
            //$query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();
            $query_device = $this->db_connection->prepare('delete FROM alert_config WHERE uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();
            //error_log("Error=".implode(",", $query_device->errorInfo()));
        }
    }



    public function deleteDevice($uuid)
    {
        //error_log("Deleteing device =".$uuid);
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('update registry_port set uuid = NULL where uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();
            $query_device = $this->db_connection->prepare ( 'delete FROM device_cert WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare ( 'delete FROM stat WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare ( 'delete FROM motion_last WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare('delete FROM device WHERE uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();

            //error_log("Error=".implode(",", $query_device->errorInfo()));
        }
    }


    public function resetDevice($uuid)
    {
        error_log("Resetting device =".$uuid);
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('update registry_port set uuid = NULL where uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();
            $query_device = $this->db_connection->prepare ( 'delete FROM device_cert WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare ( 'delete FROM stat WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare ( 'delete FROM motion_last WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
            $query_device = $this->db_connection->prepare('update device set type="RESET" WHERE uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();

            error_log("Error=".implode(",", $query_device->errorInfo()));
        }
    }


    public function alert($state)
    {
        $this->email_alerts=$state;
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('update device set email_alerts = :state where uuid = :uuid');
            $query_device->bindValue(':state', $state, PDO::PARAM_INT);
            $query_device->bindValue(':uuid', $this->uuid, PDO::PARAM_STR);
            $query_device->execute();
        }
        return 1;
    }

    public function updateSettings($setting)
    {
        //error_log("Setting=".$setting);
        $ver_set = (array)json_decode($setting);
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('update device set setting = :setting, version = :version where uuid = :uuid');
            $query_device->bindValue(':setting',  $setting, PDO::PARAM_STR);
            $query_device->bindValue(':version',   $ver_set['version'], PDO::PARAM_STR);
            $query_device->bindValue(':uuid', $this->uuid, PDO::PARAM_STR);
            $query_device->execute();
            //error_log("Error=".implode(",", $query_device->errorInfo()));
        }
        return 1;
    }

    public function isCapable($capability){
        if (strpos($this->capabilities, $capability) !== false){
            return True;
        }
        else {
            return False;
        }
    }


    public static function loadUserDevices($username)
    {
        // if database connection opened
        $devices = array();
        try {
            $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
            // database query, getting all the info of the selected user
            $query_user = $db_connection->prepare('SELECT * FROM device WHERE user_name = :user_name and type="NORMAL" order by updated desc');
            $query_user->bindValue(':user_name', $username, PDO::PARAM_STR);
            $query_user->setFetchMode(PDO::FETCH_CLASS, 'Device');
            $query_user->execute();
            while($obj = $query_user->fetch()){
                $devices[]=$obj;
            }
        }
        catch (PDOException $e) {
            error_log( MESSAGE_DATABASE_ERROR. $e);
        }
        return $devices;
    }


    public static function loadAllDevices()
    {
        // if database connection opened
        $devices = array();
        try {
            $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
            // database query, getting all the info of the selected user
            $query_user = $db_connection->prepare('SELECT * FROM device order by updated desc');
            $query_user->setFetchMode(PDO::FETCH_CLASS, 'Device');
            $query_user->execute();
            while($obj = $query_user->fetch()){
                $devices[]=$obj;
            }
        }
        catch (PDOException $e) {
            error_log( MESSAGE_DATABASE_ERROR. $e);
        }
        return $devices;
    }

    public static function getDeviceOwner($uuid){
        $db_connection = new PDO ( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS );
        // database query, getting all the info of the selected user
        $query_device = $db_connection->prepare('SELECT user_name FROM device WHERE uuid = :uuid');
        $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $query_device->execute();
        return $query_device->fetch()[0];
    }

    public static function getDeviceName($uuid){
        $db_connection = new PDO ( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS );
        // database query, getting all the info of the selected user
        $query_device = $db_connection->prepare('SELECT device_name FROM device WHERE uuid = :uuid');
        $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $query_device->execute();
        return $query_device->fetch()[0];
    }
}
