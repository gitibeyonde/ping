<?php

class DeviceActivity
{
    public $uuid = null;
    public $weekday = 0;
    public $quater = 0;
    public $activity = null;
    public $updated = null;
    
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

    public function getActivity($uuid, $weekday, $quater)
    {
        $act=null;
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare("SELECT * FROM device_activity WHERE uuid =:uuid and weekday=:weekday and quater=:quater");
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->bindValue(':weekday', $weekday, PDO::PARAM_STR);
            $query_device->bindValue(':quater', $quater, PDO::PARAM_STR);
            $query_device->setFetchMode(PDO::FETCH_CLASS, 'DeviceActivity');
            $query_device->execute();
            $act = $query_device->fetch();
            //error_log("Error description: " . $this->db_connection->errorInfo()[0]);
        }
        return $act;
    }

    public function saveActivity($uuid, $weekday, $quater, $grid)
    {
        if (strlen($grid) > 2){
            $stmt = $this->db_connection->prepare ( "insert into device_activity values (:uuid, :weekday, :quater, :activity, now()) on duplicate key update activity=:activity, updated = now()" );
            $stmt->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $stmt->bindValue ( ':weekday', $weekday, PDO::PARAM_STR );
            $stmt->bindValue ( ':quater', $quater, PDO::PARAM_STR );
            $stmt->bindValue ( ':activity', $grid, PDO::PARAM_STR );
            $stmt->execute ();
            //error_log("Error description: " . $this->db_connection->errorInfo()[0]);
        }
    }
    
    
    public function checkActivity($uuid, $grid_str){
        if (strlen($grid_str) < 5){
            return 0;
        }
        $weekday=substr("$grid_str", 0, 1);
        $quater=substr("$grid_str", 2, 1);
        $cgrid=substr("$grid_str", 4);
        $pact = $this->getActivity($uuid, $weekday, $quater);
        $cbits = str_split($cgrid, 1);
        //error_log("pact ". print_r($pact, true));
        if ($pact != null) {
            $pbits = str_split($pact->activity, 1);
            $tbits=array();
            $act_score=0;
            //error_log("Cbit=".print_r($cbits, true). " Pbits=".print_r($pbits, true));
            for ($i=0;$i<sizeof($cbits);$i=$i+1){
                if ($cbits[$i] == 0 && $pbits[$i] > 0) {
                    $tbits[$i] = $pbits[$i] - 1;
                }
                else if ($cbits[$i] == 1 && $pbits[$i] < 9) {
                    $tbits[$i] = $pbits[$i] + 1;
                }
                else {
                    $tbits[$i] = $pbits[$i];
                }
                if ($cbits[$i] == 0 && $pbits[$i] == 9) {
                    $act_score = $act_score + 1;
                }
                else if ($cbits[$i] == 1 && $pbits[$i] == 0) {
                    $act_score = $act_score + 2;
                }
                //error_log("tbits $i = " . $tbits[$i] . " Activity score =". $act_score );
            }
            $this->saveActivity($uuid, $weekday, $quater, implode('', $tbits));
            return abs($act_score);
        }
        else {
            $tbits=array();
            for ($i=0;$i<sizeof($cbits);$i=$i+1){
                if ($cbits[$i] == 0) {
                    $tbits[$i] = 4;
                }
                else {
                    $tbits[$i] = 5;
                }
            }
            $this->saveActivity($uuid, $weekday, $quater, implode('', $tbits));
            return 0;
        }
    }
    
}
