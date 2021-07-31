<?php

require_once(__ROOT__.'/classes/AlertRaised.php');
require_once(__ROOT__.'/classes/Device.php');


class AlertConfig
{
    public $uuid = null;
    public $email_mask = "000000000000000000000000";
    public $pns_mask = "000000000000000000000000";
    public $email = null;
    public $grid = "0000000000000000000000000";
    public $ping = 0;
    public $all_motion = 0;
    public $no_motion_hour = 0;
    public $motion_burst = 0;
    public $unrecog = 0;
    public $recog = 0;
    public $people_count = 0;
    public $subcategory = null;
    public $classify = 0;
    public $license = 0;
    public $temp_high = 999;
    public $temp_low = -99;
    public $humid_high = 999;
    public $humid_low = -99;
    public $no_repeat = 1;
    public $no_repeat_delta = 10;
    public $unusual = 0;
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
    
    
    public static function loadManualCategories($uuid)
    
    {
    $categories = array();    
    try {
        $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
        // database query, getting all the info of the selected user
        $query = $db_connection->prepare('SELECT category FROM camera_manual WHERE uuid = :uuid');
        $query->bindValue(':uuid', $uuid, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetchAll();
        foreach ($result as $cat)
        {
            //error_log(print_r($ac, true));
            array_push($categories,$cat['category']);
        }
        
        return $categories;
    }
    catch (PDOException $e) {
        error_log( MESSAGE_DATABASE_ERROR.$e);
    }
      
    }

    public static function loadDeviceAlertConfig($uuid)
    {
        // if database connection opened
        $ac = null;
        try {
            $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
            // database query, getting all the info of the selected user
            $query = $db_connection->prepare('SELECT * FROM alert_config WHERE uuid = :uuid');
            $query->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query->setFetchMode(PDO::FETCH_CLASS, 'AlertConfig');
            $query->execute();
            if ($query->rowCount() > 0){
                $ac =  $query->fetch();
                //error_log(print_r($ac, true));
                return $ac;
            }
            else {
                return null;
            }
        }
        catch (PDOException $e) {
            error_log( MESSAGE_DATABASE_ERROR.$e);
        }
        return null;
    }
    
    public static function loadDeviceWithPingCheck()
    {
        // if database connection opened
        $acs = array();
        try {
            $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
            // database query, getting all the info of the selected user
            $query = $db_connection->prepare('SELECT * FROM alert_config WHERE ping >= 60');
            $query->setFetchMode(PDO::FETCH_CLASS, 'AlertConfig');
            $query->execute();
            while($obj = $query->fetch()){
                $acs[]=$obj;
            }
        }
        catch (PDOException $e) {
            error_log( MESSAGE_DATABASE_ERROR.$e);
        }
        return $acs;
    }

    public function deleteAlertConfig($uuid)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('delete FROM alert_config WHERE uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();
            $query_device = $this->db_connection->prepare('delete FROM camera_manual WHERE uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->execute();
        }
    }
    
    public function enableEmailAlert($uuid, $emailmask)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('update alert_config set email = :email WHERE uuid = :uuid');
            $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $query_device->bindValue(':email', $emailmask, PDO::PARAM_STR);
            $query_device->execute();
        }
    }
    
    public function isPeopleCountEnabled(){
        return $this->people_count > 0;
    }
    
    public function getCountPeople()
    {
        return $this->people_count;
    }
    
    public function isClassifyEnabled(){
        return $this->classify == 1;
    }
    
    public function isMotionAlertEnabled(){
        return $this->all_motion == 1;
    }
    
    public function isMotionBurstAlertEnabled(){
        return $this->motion_burst == 1;
    }
    
    public function isNoMotionAlertEnabled(){
        return $this->no_motion_hour > 0 ? $this->no_motion_hour : 0;
    }
    
    public function noRepeatTime(){
        return $this->no_repeat == 1 ? $this->no_repeat_delta : 0 ;
    }
    
    public function checkEmailEnabled($alertType){
        $cbits = str_split($this->email_mask, 1);
        return $this->checkEnabled($alertType, $cbits);
    }
    
    public function checkPnsEnabled($alertType){
        $cbits = str_split($this->pns_mask, 1);
        return $this->checkEnabled($alertType, $cbits);
    }
    
    public function checkEnabled($alertType, $cbits){
        if ($alertType == AlertRaised::FACE_RECOGNIZED){
            return ($cbits[0] == "1") ;
        }
        else if ($alertType == AlertRaised::FACE_DETECTED){
            return ($cbits[1] == "1");
        }
        else if ($alertType == AlertRaised::GRID_DETECTED){
            return ($cbits[2] == "1");
        }
        else if ($alertType == AlertRaised::MOTION){
            return ($cbits[3] == "1");
        }
        else if ($alertType == AlertRaised::TEMP_HIGH){
            return ($cbits[4] == "1");
        }
        else if ($alertType == AlertRaised::TEMP_LOW){
            return ($cbits[5] == "1");
        }
        else if ($alertType == AlertRaised::HUMID_HIGH){
            return ($cbits[6] == "1");
        }
        else if ($alertType == AlertRaised::HUMID_LOW){
            return ($cbits[7] == "1");
        }
        else if ($alertType == AlertRaised::LICENSE){
            return ($cbits[8] == "1");
        }
    }
    
    public function addAlertConfig($uuid, $grid, $ping, $all_motion, $no_motion_hour, $motion_burst, $unrecog, $recog, $license, 
        $temp_high, $temp_low, $humid_high, $humid_low, $mbits, $email, $pbits,$people_count,$classify, $no_repeat, $no_repeat_delta, $unusual,$subcategory)
    {
        error_log("$uuid, $grid, $ping, $all_motion, $no_motion_hour, $motion_burst, $unrecog, $recog, $license, 
            $temp_high, $temp_low, $humid_high, $humid_low, $mbits, $email, $pbits,$people_count,$classify, $no_repeat, $no_repeat_delta, $unusual");
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('insert into alert_config(uuid, email_mask, pns_mask, email, grid, ping, all_motion, '
                    . '  no_motion_hour, motion_burst, unrecog, recog, temp_high, temp_low, humid_high, humid_low, license, updated, people_count, classify, no_repeat, no_repeat_delta, unusual) '
                    . ' values(:uuid, :email_mask, :pns_mask, :email, :grid, :ping, :all_motion, '
                    . ' :no_motion_hour, :motion_burst, :unrecog, :recog, :temp_high, :temp_low, :humid_high, :humid_low, :license, now(),:people_count,:classify, :no_repeat, :no_repeat_delta, :unusual)');
            $query_device->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $query_device->bindValue(':email_mask', $mbits, PDO::PARAM_STR);
            $query_device->bindValue(':pns_mask', $pbits, PDO::PARAM_STR);
            $query_device->bindValue(':email', $email, PDO::PARAM_STR);
            $query_device->bindValue(':grid', $grid, PDO::PARAM_STR);
            $query_device->bindValue(':ping', $ping, PDO::PARAM_STR);
            $query_device->bindValue(':all_motion', $all_motion, PDO::PARAM_STR);
            $query_device->bindValue(':no_motion_hour', $no_motion_hour, PDO::PARAM_STR);
            $query_device->bindValue(':motion_burst', $motion_burst, PDO::PARAM_STR);
            $query_device->bindValue(':unrecog', $unrecog, PDO::PARAM_STR);
            $query_device->bindValue(':recog', $recog, PDO::PARAM_STR);
            $query_device->bindValue(':license', $license, PDO::PARAM_STR);
            $query_device->bindValue(':temp_high', $temp_high, PDO::PARAM_STR);
            $query_device->bindValue(':temp_low', $temp_low, PDO::PARAM_STR);
            $query_device->bindValue(':humid_high', $humid_high, PDO::PARAM_STR);
            $query_device->bindValue(':humid_low', $humid_low, PDO::PARAM_STR);
            $query_device->bindValue(':people_count', $people_count, PDO::PARAM_STR);
            $query_device->bindValue(':classify', $classify, PDO::PARAM_STR);
            $query_device->bindValue(':no_repeat', $no_repeat, PDO::PARAM_STR);
            $query_device->bindValue(':no_repeat_delta', $no_repeat_delta, PDO::PARAM_STR);
            $query_device->bindValue(':unusual', $unusual, PDO::PARAM_STR);
            $query_device->execute();
            //error_log("Error=".implode(",", $query_device->errorInfo()));
            foreach ($subcategory as $sub)
            {
                error_log($sub);
                $query_device = $this->db_connection->prepare('insert into camera_manual (uuid,category) values(:uuid,:category)');
                $query_device->bindValue(':uuid', $uuid, PDO::PARAM_STR);
                $query_device->bindValue(':category', $sub, PDO::PARAM_STR);
                $query_device->execute();
            }
            
            
            
            
        }
        return 1;
    }
    
  
    public function parseAndUpdate($uuid, $user_email, $arr){
        $grid = "00000000000000000000000000";
        $email_mask = "000000000000000000000000";
        $pns_mask = "000000000000000000000000";
        $ping = 0;
        $all_motion = 0;
        $no_motion_hour = -1;
        $motion_burst = 0;
        $unrecog = 0;
        $recog = 0;
        $license = 0;
        $temp_high = 999;
        $temp_low = -99;
        $humid_high = 999;
        $humid_low = -99;
        $people_count = 0;
        $classify = 0;
        $no_repeat = 0;
        $no_repeat_delta = 0;
        $unusual = 0;
        
        if (isset($_GET['ping'])){
            $ping = $_GET['ping'];
        }
        if (isset($_GET['all_motion'])){
            $all_motion = $_GET['all_motion'];
        }
        
        if (isset($_GET['people_count'])){
            $people_count = $_GET['people_count'];
        }
        
        if (isset($_GET['classify'])){
            $classify = $_GET['classify'];
        }
        
        if(isset($_GET['sub_category']))
        {
            $subcategory = $_GET['sub_category'];
        }
        
        
        
        if (isset($_GET['no_motion_hour_cb']) && isset($_GET['no_motion_hour']) && $_GET['no_motion_hour'] > 0){
            $no_motion_hour = $_GET['no_motion_hour'];
        }
        if (isset($_GET['motion_burst'])){
            $motion_burst = $_GET['motion_burst'];
        }
        if (isset($_GET['unrecog'])){
            $unrecog = $_GET['unrecog'];
        }
        if (isset($_GET['recog'])){
            $recog = $_GET['recog'];
        }
        if (isset($_GET['license'])){
            $license = $_GET['license'];
        }
        if (isset($_GET['temp_high_cb']) && isset($_GET['temp_high'])){
            $temp_high = $_GET['temp_high'];
        }
        if (isset($_GET['temp_low_cb']) && isset($_GET['temp_low'])){
            $temp_low = $_GET['temp_low'];
        }
        if (isset($_GET['humid_high_cb']) && isset($_GET['humid_high'])){
            $humid_high = $_GET['humid_high'];
        }
        if (isset($_GET['humid_low_cb']) && isset($_GET['humid_low'])){
            $humid_low = $_GET['humid_low'];
        }
        if (isset($_GET['no_repeat'])){
            $no_repeat = $_GET['no_repeat'];
        }
        if (isset($_GET['no_repeat_delta'])){
            $no_repeat_delta = $_GET['no_repeat_delta'];
        }
        if (isset($_GET['unusual'])){
            $unusual = $_GET['unusual'];
        }
        
        $cbits = str_split($grid, 1);
        $cbits[0] = isset($_GET['r1']) ? 1 : 0;
        $cbits[1] = isset($_GET['r2']) ? 1 : 0;
        $cbits[2] = isset($_GET['r3']) ? 1 : 0;
        $cbits[3] = isset($_GET['r4']) ? 1 : 0;
        $cbits[4] = isset($_GET['r5']) ? 1 : 0;
        $cbits[5] = isset($_GET['r6']) ? 1 : 0;
        $cbits[6] = isset($_GET['r7']) ? 1 : 0;
        $cbits[7] = isset($_GET['r8']) ? 1 : 0;
        $cbits[8] = isset($_GET['r9']) ? 1 : 0;
        $cbits[9] = isset($_GET['r10']) ? 1 : 0;
        $cbits[10] = isset($_GET['r11']) ? 1 : 0;
        $cbits[11] = isset($_GET['r12']) ? 1 : 0;
        $cbits[12] = isset($_GET['r13']) ? 1 : 0;
        $cbits[13] = isset($_GET['r14']) ? 1 : 0;
        $cbits[14] = isset($_GET['r15']) ? 1 : 0;
        $cbits[15] = isset($_GET['r16']) ? 1 : 0;
        $cbits[16] = isset($_GET['r17']) ? 1 : 0;
        $cbits[17] = isset($_GET['r18']) ? 1 : 0;
        $cbits[18] = isset($_GET['r19']) ? 1 : 0;
        $cbits[19] = isset($_GET['r20']) ? 1 : 0;
        $cbits[20] = isset($_GET['r21']) ? 1 : 0;
        $cbits[21] = isset($_GET['r22']) ? 1 : 0;
        $cbits[22] = isset($_GET['r23']) ? 1 : 0;
        $cbits[23] = isset($_GET['r24']) ? 1 : 0;
        $cbits[24] = isset($_GET['r25']) ? 1 : 0;
        $grid = implode('', $cbits);
        
        $mbits = str_split($email_mask, 1);
        $mbits[0] = isset($_GET['m1']) ? 1 : 0;
        $mbits[1] = isset($_GET['m2']) ? 1 : 0;
        $mbits[2] = isset($_GET['m3']) ? 1 : 0;
        $mbits[3] = isset($_GET['m4']) ? 1 : 0;
        $mbits[4] = isset($_GET['m5']) ? 1 : 0;
        $mbits[5] = isset($_GET['m6']) ? 1 : 0;
        $mbits[6] = isset($_GET['m7']) ? 1 : 0;
        $mbits[7] = isset($_GET['m8']) ? 1 : 0;
        $mbits[8] = isset($_GET['m9']) ? 1 : 0;;
        $mbits[9] = 0;
        $mbits[10] = 0;
        $mbits[11] = 0;
        $mbits[12] = 0;
        $mbits[13] = 0;
        $mbits[14] = 0;
        $mbits[15] = 0;
        $mbits[16] = 0;
        $mbits[17] = 0;
        $mbits[18] = 0;
        $mbits[19] = 0;
        $mbits[20] = 0;
        $mbits[21] = 0;
        $mbits[22] = 0;
        $mbits[23] = 0;
        $mbits[24] = 0;
        $mbits = implode('', $mbits);
        
        $pbits = str_split($pns_mask, 1);
        $pbits[0] = isset($_GET['p1']) ? 1 : 0;
        $pbits[1] = isset($_GET['p2']) ? 1 : 0;
        $pbits[2] = isset($_GET['p3']) ? 1 : 0;
        $pbits[3] = isset($_GET['p4']) ? 1 : 0;
        $pbits[4] = isset($_GET['p5']) ? 1 : 0;
        $pbits[5] = isset($_GET['p6']) ? 1 : 0;
        $pbits[6] = isset($_GET['p7']) ? 1 : 0;
        $pbits[7] = isset($_GET['p8']) ? 1 : 0;
        $pbits[8] = isset($_GET['p9']) ? 1 : 0;;
        $pbits[9] = 0;
        $pbits[10] = 0;
        $pbits[11] = 0;
        $pbits[12] = 0;
        $pbits[13] = 0;
        $pbits[14] = 0;
        $pbits[15] = 0;
        $pbits[16] = 0;
        $pbits[17] = 0;
        $pbits[18] = 0;
        $pbits[19] = 0;
        $pbits[20] = 0;
        $pbits[21] = 0;
        $pbits[22] = 0;
        $pbits[23] = 0;
        $pbits[24] = 0;
        $pbits = implode('', $pbits);
        
        $this->deleteAlertConfig($uuid);
        $this->addAlertConfig($uuid, $grid, $ping, $all_motion, $no_motion_hour, $motion_burst, $unrecog, 
            $recog, $license, $temp_high, $temp_low, $humid_high, $humid_low, $mbits, $user_email, $pbits, $people_count,$classify, $no_repeat, $no_repeat_delta, $unusual,$subcategory);
       
    
    }
    
    public function compareGrids($grid, $cgrid){
        $bits = str_split($grid, 1);
        $cbits = str_split($cgrid, 1);
        for ($i=0;$i<sizeof($bits);$i=$i+1){
            if ($cbits[$i] !=  $bits[$i]) {
                return 1;
            }
        }
        return 0;
    }
    
}
