<?php

class Alert
{

    public $id = null;
    public $uuid = null;
    public $user_name = null;
    public $alert_type = null;
    public $alert_sub_type = null;
    public $param1 = null;
    public $param2 = null;
    public $last_triggered = null;
    public $total_triggers = null;
    public $created = null;
    public $deleted = null;

    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();

    public function __construct()
    {
        //$created=date("Y-m-d h:i:sa");
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

    
    public static function loadDeviceAlerts($uuid)
    {
        // if database connection opened
        $alerts = array();
        try {
            $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
            // database query, getting all the info of the selected user
            $query_user = $db_connection->prepare('SELECT * FROM alerts WHERE uuid = :uuid order by created limit 20');
            $query_user->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_user->setFetchMode(PDO::FETCH_CLASS, 'Alert');
            $query_user->execute();
            while($obj = $query_user->fetch()){
                $alerts[]=$obj;
            }
        }
        catch (PDOException $e) {
            $this->errors[] = MESSAGE_DATABASE_ERROR;
        }
        return $alerts;
    }
    

    public function deleteAlert($id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('delete FROM alert WHERE id = :id');
            $query_device->bindValue(':id', $id, PDO::PARAM_STR);
            $query_device->execute();
        }
    }
    

    public function addAlert($uuid, $username, $alert_type, $alert_subtype, $p1, $p2)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('insert into alert(uuid, user_name, alert_type, alert_subtype, param1, param2, last_triggered, total_triggers, created, deleted) '
                    + ' values(:uuid, :username, :alert_type, :alert_subtype, :p1, :p2, null, null, now(), null)');
            $query_device->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $query_device->bindValue(':user_name', $username, PDO::PARAM_STR);
            $query_device->bindValue(':alert_type', $alert_type, PDO::PARAM_STR);
            $query_device->bindValue(':alert_subtype', $alert_subtype, PDO::PARAM_STR);
            $query_device->bindValue(':p1', $p1, PDO::PARAM_STR);
            $query_device->bindValue(':p2', $p2, PDO::PARAM_STR);
            $query_device->execute();
            error_log("Error=".implode(",", $query_device->errorInfo()));
        }
        return 1;
    }
    
  

}
