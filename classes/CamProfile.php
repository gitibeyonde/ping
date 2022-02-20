<?php

class CamProfile {
    public $profile = null;
    public $name = null;
    public $value = null;
    public $value_type = null;
    public $changedAt = null;
    
    const video_mode="video_mode";
    const always_on="always_on";
    const motion="motion";
    const tolerance="tolerance";
    const snapshot="snapshot";
    const zoom="zoom";
    const grid="grid";
    const motion_quality="motion_quality";
    const video_quality="video_quality";
    const capture_delta="capture_delta";
    const face_min="face_min";
    const face_detect="face_detect";
    const face_recognize="face_recognize";
    const public_key="public_key";
    const sip="sip";
    const license_plate="license_plate";
    const people_counting="people_counting";
    const object_counting="object_counting";
    const recording="recording";
    const broadcast="broadcast";
    const history_broadcast="history_broadcast";
    const temp="temp";
    
    
    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();
    
    public function __construct()
    {
        $updated=date(DateTime::ATOM);
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
    
    public function loadProfile($profile)
    {
        // if database connection opened
        $devices = array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query = $this->db_connection->prepare('SELECT * FROM cam_profile WHERE profile = :profile');
            $query->bindValue(':profile', $uuid, PDO::PARAM_STR);
            $query->setFetchMode(PDO::FETCH_CLASS, 'CamParam');
            $query->execute();
            while($obj = $query->fetch()){
                $devices[]=$obj;
            }
        }
        return $devices;
    }
    
    
    public function getProfileParamValue($profile, $param)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query = $this->db_connection->prepare('SELECT value FROM cam_profile WHERE profile = :profile and name = :name limit 1');
            $query->bindValue(':profile', $profile, PDO::PARAM_STR);
            $query->bindValue(':name', $param, PDO::PARAM_STR);
            $query->setFetchMode(PDO::FETCH_CLASS, 'CamParam');
            $query->execute();
            $value = $query->fetch () [0];
            return $value;
        }
        else {
            return null;
        }
    }
}


?>