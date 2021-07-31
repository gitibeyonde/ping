<?php

class Correlation
{
    public $id = null;
    public $user_name = null;
    public $box_name = 0;
    public $uuid1 = 0;
    public $uuid2 = 0;
    public $correlation = 0;
    public $updated = 0;
    
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

    public function getCorrelation($key)
    {
        $corr=null;
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('SELECT * FROM device_correlation WHERE id = :key');
            $query_device->bindValue(':key', $key, PDO::PARAM_STR);
            $query_device->setFetchMode(PDO::FETCH_CLASS, 'Correlation');
            $query_device->execute();
            $corr = $query_device->fetch();
        }
        return $corr;
    }

}
