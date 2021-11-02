<?php

require_once(__ROOT__.'/classes/AwsSns.php');
require_once(__ROOT__.'/classes/Device.php');

class DeviceToken
{
    public $user_name = null;
    public $phone_id = null;
    public $token = null;
    public $endpoint_arn = null;
    public $system = null;
    public $system_type = null;
    public $language = null;
    public $country = null;
    public $updated = null;

    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();
    private $awssns = null;
    
    public function __construct()
    {
        $this->updated=date(DateTime::ATOM);
        $this->awssns = new AwsSns();
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
    
    public function loadDeviceToken($token)
    {
        $device_token=array();
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('SELECT * FROM device_token WHERE token = :token');
            $query_device->bindValue(':token', $token, PDO::PARAM_STR);
            $query_device->setFetchMode(PDO::FETCH_CLASS, 'DeviceToken');
            $query_device->execute();
            while($obj = $query_device->fetch()){
                $device_token[]=$obj;
            }
        }
        error_log("loadDeviceTokens".print_r($device_token, true));
        return $device_token;
    }
    
    
    public function loadDeviceTokensForUser($user_name)
    {
        $device_token=array();
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('SELECT * FROM device_token WHERE user_name = :user_name');
            $query_device->bindValue(':user_name', $user_name, PDO::PARAM_STR);
            $query_device->setFetchMode(PDO::FETCH_CLASS, 'DeviceToken');
            $query_device->execute();
            while($obj = $query_device->fetch()){
                $device_token[]=$obj;
            }
        }
        //error_log("loadDeviceTokensForUser".print_r($device_token, true));
        return $device_token;
    }
    
    public function loadDeviceTokensForDevice($uuid)
    {
        $user_name = Device::getDeviceOwner($uuid);
        return $this->loadDeviceTokensForUser($user_name);
    }
    
    public function applyDeviceTokenForUser($user_name, $phone_id, $token, $system, $system_type, $language, $country){
        $result="";
        error_log("applyDeviceTokenForUser $token");
        $device_tokens = $this->loadDeviceToken($token);
        //remove the token if it exists, could be owned by a different user
        foreach ($device_tokens as &$device_token) {
            //delete arns
            $this->awssns->deleteEndPoint($device_token->endpoint_arn);
            //delete db entry
            $this->deleteDeviceToken($device_token->token);
        }
        try {
            //create a endpoitn arn
            $endpoint_arn = $this->awssns->createEndPoint($user_name, $system, $token, $phone_id);
            $result = $this->addDeviceToken($user_name, $phone_id, $token, $system, $system_type, $language, $country, $endpoint_arn);
        }
        catch (Aws\Sns\Exception\SnsException $e){
            error_log("Error on adding endpoint ".$e->getMessage());
            $result = "Aws Sns Error on adding endpoint";
        }
        return $result;
    }
    
    public function deleteDeviceToken($token)
    {
        error_log("Deleteing device token for =".$token);
        if ($this->databaseConnection()) {
            $query_device = $this->db_connection->prepare ( 'delete FROM device_token WHERE token = :token' );
            $query_device->bindValue ( ':token', $token, PDO::PARAM_STR );
            $query_device->execute ();
        }
    }
    
    public function addDeviceToken($user_name, $phone_id, $token, $system, $system_type, $language, $country, $endpoint_arn){
        error_log("addDeviceToken $user_name, $phone_id, $token, $system, $system_type, $language, $country, $endpoint_arn");
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into device_token(user_name, phone_id, token, endpoint_arn, system, system_type, language, country, updated) \
                                 values(:user_name, :phone_id, :token, :endpoint_arn, :system, :system_type, :language, :country, now()) \
                                    on duplicate key update token=:token, endpoint_arn=:endpoint_arn, system=:system, system_type=:system_type, language=:language, country=:country, updated=now()');
            $sth->bindValue(':user_name',  $user_name, PDO::PARAM_STR);
            $sth->bindValue(':phone_id', $phone_id, PDO::PARAM_STR);
            $sth->bindValue(':token', $token, PDO::PARAM_STR);
            $sth->bindValue(':endpoint_arn', $endpoint_arn, PDO::PARAM_STR);
            $sth->bindValue(':system', $system, PDO::PARAM_STR);
            $sth->bindValue(':system_type', $system_type, PDO::PARAM_STR);
            $sth->bindValue(':language', $language, PDO::PARAM_STR);
            $sth->bindValue(':country', $country, PDO::PARAM_STR);
            $sth->execute();
            if ($sth->errorInfo()[0] == "00000"){
                error_log("addDeviceToken No error");
                return "";
            }
            else {
                error_log("addDeviceToken".$sth->errorInfo()[2]);
                return $sth->errorInfo()[2];
            }
        }
        return "Database connection failed";
    }
    
    
}
