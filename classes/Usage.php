<?php

class Usage
{
    public $uuid = null;
    public $created = null;
    public $network = 0;
    public $disk = 0;
    public $objects = 0;
    public $api_calls = null;
    
    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();

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
   

    public function monthToDateUsage($uuid)
    {
        $usage=null;
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('SELECT * FROM usage_daily WHERE uuid = :uuid order by created desc limit 1');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->setFetchMode(PDO::FETCH_CLASS, 'Usage');
            $query_device->execute();
            $usage = $query_device->fetch();
        }
        return $usage;
    }
    
}
