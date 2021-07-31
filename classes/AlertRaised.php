<?php

require_once(__ROOT__.'/classes/Device.php');
require_once(__ROOT__.'/classes/EmailUtils.php');
require_once(__ROOT__.'/classes/AwsSns.php');
require_once(__ROOT__.'/classes/Aws.php');
require_once(__ROOT__.'/classes/AlertConfig.php');

class AlertRaised
{

    public $id = null;
    public $type = null;
    public $uuid = null;
    public $user_name = null;
    public $image = null;
    public $value = null;
    public $comment = null;
    public $created = null;
    public $subcategory = null; 
    private $db_connection = null;
    public  $errors = array();
    public  $messages = array();
    
    
    const FACE_RECOGNIZED="fr";
    const FACE_DETECTED="fd";
    const GRID_DETECTED="gd";
    const MOTION="mt";
    const TEMP_HIGH="th";
    const TEMP_LOW="tl";
    const HUMID_HIGH="hh";
    const HUMID_LOW="hl";
    const LICENSE="lic";
    const PEOPLE_COUNT = "pc";
    const CLASSIFY = "cf";
    const DEVICE_OFFLINE = "do";
    
    const DATE_FORMAT =  "Y/m/d H:i:s";
    
    private $em = null;
    private $sns = null;
    private $ac = null;
    private $aws = null;
    
    public function __construct()
    {
        $this->em = new EmailUtils();
        $this->sns = new AwsSns();
        $this->ar = new AlertConfig();
        $this->aws = new Aws();
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
    public static function getAlertString($uuid, $alert, $value, $comment){
        if ($alert == AlertRaised::FACE_DETECTED){
            return "Face Detected on ".Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::FACE_RECOGNIZED){
            return "Face Recognized ".$comment." on ".Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::GRID_DETECTED){
            return "Grid Event Detected on ".Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::MOTION){
            return "Motion Detected on ".Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::TEMP_HIGH){
            return $value.' '.$comment.'  temp. detected on '.Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::TEMP_LOW){
            return $value.' '.$comment.' temp. detected on '.Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::HUMID_HIGH){
            return $value.' '.$comment.' humidity detected on '.Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::HUMID_LOW){
            return $value.' '.$comment.' humidity detected on '.Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::LICENSE){
            return "License Plate ".$comment.' on '.Device::getDeviceName($uuid);
        }
        else if ($alert == AlertRaised::LICENSE){
            return Device::getDeviceName($uuid) . " is offline for ".$value." minutes";
        }
        else {
            return $alert;
        }
        
    }
    
    public function loadDeviceAlerts($uuid)
    {
        // if database connection opened
        $alerts = array();
        try {
            if ($this->databaseConnection()) {
                // database query, getting all the info of the selected user
                $query_user = $this->db_connection->prepare('SELECT * FROM alert_raised WHERE uuid = :uuid order by created desc limit 5');
                $query_user->bindValue(':uuid', $uuid, PDO::PARAM_STR);
                $query_user->setFetchMode(PDO::FETCH_CLASS, 'AlertRaised');
                $query_user->execute();
                while($obj = $query_user->fetch()){
                    $alerts[]=$obj;
                }
                //error_log("Error=".implode(",", $query_user->errorInfo()));
            }
            
        }
        catch (PDOException $e) {
            $this->errors[] = MESSAGE_DATABASE_ERROR;
        }
        return $alerts;
    }
    
    public function loadDeviceAlertsOfType($uuid, $type, $limit)
    {
        // if database connection opened
        $alerts = array();
        try {
            if ($this->databaseConnection()) {
                // database query, getting all the info of the selected user
                $query_user = $this->db_connection->prepare('SELECT * FROM alert_raised WHERE uuid = :uuid and type = :type order by created desc limit '.$limit);
                $query_user->bindValue(':uuid', $uuid, PDO::PARAM_STR);
                $query_user->bindValue(':type', $type, PDO::PARAM_STR);
                $query_user->setFetchMode(PDO::FETCH_CLASS, 'AlertRaised');
                $query_user->execute();
                while($obj = $query_user->fetch()){
                    $alerts[]=$obj;
                }
                //error_log("Error=".implode(",", $query_user->errorInfo()));
            }
            
        }
        catch (PDOException $e) {
            $this->errors[] = MESSAGE_DATABASE_ERROR;
        }
        return $alerts;
    }
    
    public function loadAllDeviceAlerts($uuid)
    {
        // if database connection opened
        $alerts = array();
        try {
            if ($this->databaseConnection()) {
                // database query, getting all the info of the selected user
                $query_user = $this->db_connection->prepare('SELECT * FROM alert_raised WHERE uuid = :uuid order by created desc limit 50');
                $query_user->bindValue(':uuid', $uuid, PDO::PARAM_STR);
                $query_user->setFetchMode(PDO::FETCH_CLASS, 'AlertRaised');
                $query_user->execute();
                while($obj = $query_user->fetch()){
                    $alerts[]=$obj;
                }
                
                //error_log("Error=".implode(",", $query_user->errorInfo()));
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
            $query_device = $this->db_connection->prepare('delete FROM alert_raised WHERE id = :id');
            $query_device->bindValue(':id', $id, PDO::PARAM_STR);
            $query_device->execute();
        }
    }
    

    public function addAlert($uuid, $alert_type, $image, $value, $comment, $datetime)
    {
        if ($this->databaseConnection()) {
            //error_log("addAlert   ".$uuid.", ". $alert_type.", ". $image.", ". $value. ", ".$comment.", ". date_format($datetime, DateTime::ATOM));
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare('insert into alert_raised(type, uuid, image, value, comment, created)  values(:alert_type, :uuid, :image, :value, :comment, :timestamp)');
            $query_device->bindValue(':alert_type', $alert_type, PDO::PARAM_STR);
            $query_device->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $query_device->bindValue(':image', $image, PDO::PARAM_STR);
            $query_device->bindValue(':value', $value, PDO::PARAM_STR);
            $query_device->bindValue(':comment', $comment, PDO::PARAM_STR);
            $query_device->bindValue(':timestamp', date_format($datetime, DateTime::ATOM), PDO::PARAM_STR);
            $query_device->execute();
            //error_log("Last insert id ".$this->db_connection->lastInsertId());
            return $this->db_connection->lastInsertId();
            //error_log("Error=".implode(",", $query_device->errorInfo()));
        }
        return 1;
    }
    
    public function getLastAlert($uuid, $type){
        
    }
    public function checkNoRepeatAlert($uuid, $type, $current_date, $delta, $value, $comment){
        if ($delta == 0){
            return 0;
        }
        else {
            $delta_sec = $delta * 60;
            $ar = $this->loadDeviceAlertsOfType($uuid, $type, 1);
            if ($ar == null || $ar[0] == null) {
                return 0;
            }
            //error_log(">>>> checkNoRepeatAlert NEW ". $delta_sec. " uuid=".$uuid." type=".$type." value=".intval($value)." comment=".$comment. " time stamp =". date_format($current_date, $this::DATE_FORMAT));
            //error_log("checkNoRepeatAlert OLD ". $ar[0]->uuid . " type =". $ar[0]->type." value=".intval($ar[0]->value)." comment=".$ar[0]->comment . " timestamp =". $ar[0]->created);
            $created_date = DateTime::createFromFormat("Y-m-d H:i:s", $ar[0]->created);
            $current_date_str = date_format($current_date, $this::DATE_FORMAT);
            $current_date =  DateTime::createFromFormat($this::DATE_FORMAT, $current_date_str);
            $time_passed = $current_date->getTimestamp() - $created_date->getTimestamp();  
            //error_log("checkNoRepeatAlert time_passed " . intval($time_passed)."  and delta sec ".intval($delta_sec));
            if ( intval($delta_sec) > intval($time_passed)){
                if (
                        ( ($type == AlertRaised::TEMP_HIGH || $type == AlertRaised::HUMID_HIGH) && (intval($value) > intval($ar[0]->value)) ) 
                       || ( ($type == AlertRaised::TEMP_LOW || $type == AlertRaised::HUMID_LOW) && (intval($value) < intval($ar[0]->value)) ) 
                        || ( ($type == AlertRaised::FACE_RECOGNIZED) && ($comment != $ar[0]->comment) )
                        || ( $type == AlertRaised::FACE_DETECTED )
                        || ( $type == AlertRaised::GRID_DETECTED )
                        || ( $type == AlertRaised::MOTION && $time_passed > 30 )
                        ){
                            //error_log($uuid." Alert can be send as values changed in desired dir ".$type);
                    return 0;
                }
                else {
                    //error_log($uuid." Alert cannot be send ".$type);
                    return 1;
                }
            }
            else {
                //error_log($uuid." Alert can be send as delta is exceeded ".$type);
                return 0;
            }
        }
    }
   
    public function notifyTempAndHumidity($uuid, $temp, $humid, $datetime){
        $ac = AlertConfig::loadDeviceAlertConfig($uuid);
        if ($ac == null) return;
        $timestamp_str = date_format($datetime, $this::DATE_FORMAT);
        //error_log("$uuid notifyTempAndHumidity $timestamp_str ");
            if ($ac->temp_high != 999 && $ac->temp_high < $temp){
                if ($this->checkNoRepeatAlert($uuid, AlertRaised::TEMP_HIGH, $datetime, $ac->noRepeatTime(), $temp, "")) return;
                $id = $this->addAlert($uuid, AlertRaised::TEMP_HIGH, "", $temp, " Deg C", $datetime);
                if ($ac->checkEmailEnabled(AlertRaised::TEMP_HIGH)){
                    $this->em->sendEmailAlert($id, $ac->email, "High Temperature of ".$temp." on device ".Device::getDeviceName($uuid), "High Temperature of ".$temp." degree detected, on device ".Device::getDeviceName($uuid)." at ".$timestamp_str." </br> </br>For more info goto https://app.ibeyonde.com ");
                }
                if ($ac->checkPnsEnabled(AlertRaised::TEMP_HIGH)){
                    $this->sns->publishToEndpoint($id, $uuid, AlertRaised::TEMP_HIGH, "", $temp, "Deg C", $timestamp_str);
                }
            }
            else if ($ac->temp_low != -999 && $ac->temp_low > $temp){
                if ($this->checkNoRepeatAlert($uuid, AlertRaised::TEMP_LOW, $datetime, $ac->noRepeatTime(), $temp, "")) return;
                $id = $this->addAlert($uuid, AlertRaised::TEMP_LOW, "", $temp, " Deg C", $datetime);
                if ($ac->checkEmailEnabled(AlertRaised::TEMP_LOW)){
                    $this->em->sendEmailAlert($id, $ac->email, "Low Temperature of ".$temp ." on device ".Device::getDeviceName($uuid), "Low Temperature of ".$temp." degree detected, on device ".Device::getDeviceName($uuid)." at ".$timestamp_str." </br> </br>For more info goto https://app.ibeyonde.com ");
                }
                if ($ac->checkPnsEnabled(AlertRaised::TEMP_LOW)){
                    $this->sns->publishToEndpoint($id, $uuid, AlertRaised::TEMP_LOW, "", $temp, "Deg C", $timestamp_str);
                }
            }
            if ($ac->humid_high != 999 && $ac->humid_high < $humid){
                if ($this->checkNoRepeatAlert($uuid, AlertRaised::HUMID_HIGH, $datetime, $ac->noRepeatTime(), $humid, "")) return;
                $id = $this->addAlert($uuid, AlertRaised::HUMID_HIGH, "", $humid, " Units", $datetime);
                if ($ac->checkEmailEnabled(AlertRaised::HUMID_HIGH)){
                    $this->em->sendEmailAlert($id, $ac->email, "High Humidity of ".$humid." on device ".Device::getDeviceName($uuid), "High Humidity of ".$humid." units detected, on device ".Device::getDeviceName($uuid)." at ".$timestamp_str." </br> </br>For more info goto https://app.ibeyonde.com ");
                }
                if ($ac->checkPnsEnabled(AlertRaised::HUMID_HIGH)){
                    $this->sns->publishToEndpoint($id, $uuid, AlertRaised::HUMID_HIGH, "", $humid, " Units", $timestamp_str);
                }
            }
            else if ($ac->humid_low != -999 && $ac->humid_low > $humid){
                if ($this->checkNoRepeatAlert($uuid, AlertRaised::HUMID_LOW, $datetime, $ac->noRepeatTime(), $humid, "")) return;
                $id = $this->addAlert($uuid, AlertRaised::HUMID_LOW, "", $humid, " Units", $datetime);
                if ($ac->checkEmailEnabled(AlertRaised::HUMID_LOW)){
                    $this->em->sendEmailAlert($id, $ac->email, "Low Humidity of ".$humid." on device ".Device::getDeviceName($uuid), "Low Humidity of ".$humid." units detected, on device ".Device::getDeviceName($uuid)." at ".$timestamp_str." </br> </br>For more info goto https://app.ibeyonde.com ");
                }
                if ($ac->checkPnsEnabled(AlertRaised::HUMID_LOW)){
                    $this->sns->publishToEndpoint($id, $uuid, AlertRaised::HUMID_LOW, "", $humid, " Units", $timestamp_str);
                }
            }
    }
    
    public function notifyMotion($uuid, $type, $target_file, $grid, $datetime){
        // ALERTS
        $ac = AlertConfig::loadDeviceAlertConfig($uuid);
        if ($ac == null) return;
        $timestamp_str = date_format($datetime, $this::DATE_FORMAT);
        // check if similar alert was raised in no_repeat_delta time
        //error_log("$uuid notifyMotion $timestamp_str ");
        
        $user_name = Device::getDeviceOwner($uuid);
            // check face
            if ($type=="FACE" && ($ac->recog == 1 || $ac->unrecog == 1 )) {
                error_log("Command predict =https://bingo.ibeyonde.com:5081/?cmd=face&method=predict&image=".$target_file."&user=".$user_name);
                $output = Utils::getSSLPage("https://bingo.ibeyonde.com:5081/?cmd=face&method=predict&image=".$target_file."&user=".$user_name);
                $output = json_decode($output, true);
                error_log("Img recog output=".print_r($output, true));
                if ($output == "" || array_key_exists('name', $output) == 0){
                    if ($this->checkNoRepeatAlert($uuid, AlertRaised::FACE_DETECTED, $datetime, $ac->noRepeatTime(), 0, "")) return;
                    $id = $this->addAlert($uuid, AlertRaised::FACE_DETECTED, $target_file, "", "", $datetime);
                    if ($ac->checkEmailEnabled(AlertRaised::FACE_DETECTED)){
                        $this->em->sendEmailAlert($id, $ac->email, "Face detected", '<img src="'.$this->aws->getSignedFileUrl($target_file) .'"/> on device '.Device::getDeviceName($uuid).' at '. $timestamp_str .'  </br> </br>For more info goto https://app.ibeyonde.com ');
                    }
                    if ($ac->checkPnsEnabled(AlertRaised::FACE_DETECTED)){
                        $this->sns->publishToEndpoint($id, $uuid, AlertRaised::FACE_DETECTED, $this->aws->getSignedFileUrl($target_file), 0, "", $timestamp_str);
                    }
                }
                else {
                    if ($this->checkNoRepeatAlert($uuid, AlertRaised::FACE_RECOGNIZED, $datetime, $ac->noRepeatTime(), 0, $output)) return;
                    $id = $this->addAlert($uuid, AlertRaised::FACE_RECOGNIZED, $target_file, "", $output['name']."-".$output['conf'], $datetime);
                    if ($ac->checkEmailEnabled(AlertRaised::FACE_RECOGNIZED)){
                        $this->em->sendEmailAlert($id, $ac->email, "Face recognized ". $output['name'], $output['name'] . ' <img src="'.$this->aws->getSignedFileUrl($target_file) .'"/> on device '.Device::getDeviceName($uuid).' at '. $timestamp_str .' </br> </br>For more info goto https://app.ibeyonde.com ');
                    }
                    if ($ac->checkPnsEnabled(AlertRaised::FACE_RECOGNIZED)){
                        $this->sns->publishToEndpoint($id, $uuid, AlertRaised::FACE_RECOGNIZED, $this->aws->getSignedFileUrl($target_file), 0, $output['name'], $timestamp_str);
                    }
                }
            }
            
            if ($type=="MOTION" || $type=="MOTIOND") {
                //error_log("Grids ".$ac->grid . " grid ".$grid);
                if ($ac->grid != "00000000000000000000000000") {
                    $cgrid=substr("$grid", 4);
                    if (strlen($grid) > 25){
                        if ($ac->compareGrids($grid, $cgrid) != 0){
                            if ($this->checkNoRepeatAlert($uuid,  AlertRaised::GRID_DETECTED, $datetime, $ac->noRepeatTime(), 0, "")) return;
                            $id = $this->addAlert($uuid, AlertRaised::GRID_DETECTED, $target_file, $cgrid, "", $datetime);
                            if ($ac->checkEmailEnabled(AlertRaised::GRID_DETECTED)){
                                $this->em->sendEmailAlert($id, $ac->email, "Grid detected on device ".Device::getDeviceName($uuid), '<img src="'.$this->aws->getSignedFileUrl($target_file) .'"/> on device '.Device::getDeviceName($uuid).' at '. $timestamp_str .'  </br> </br>For more info goto https://app.ibeyonde.com ');
                            }
                            if ($ac->checkPnsEnabled(AlertRaised::GRID_DETECTED)){
                                $this->sns->publishToEndpoint($id, $uuid, AlertRaised::GRID_DETECTED, $this->aws->getSignedFileUrl($target_file), 0, $cgrid, $timestamp_str);
                            }
                        }
                    }
                }
                
                if ($ac->isMotionAlertEnabled()) {
                    if ($this->checkNoRepeatAlert($uuid,  AlertRaised::MOTION, $datetime, $ac->noRepeatTime(), 0, "")) return;
                    $id = $this->addAlert($uuid, AlertRaised::MOTION, $target_file, 0, "", $datetime);
                    if ($ac->checkEmailEnabled(AlertRaised::MOTION)){
                        $this->em->sendEmailAlert($id, $ac->email, "Motion detected on device ".Device::getDeviceName($uuid), '<img src="'.$this->aws->getSignedFileUrl($target_file) .'"/> on device '.Device::getDeviceName($uuid).' at '. $timestamp_str .'  </br> </br>For more info goto https://app.ibeyonde.com ');
                    }
                    if ($ac->checkPnsEnabled(AlertRaised::MOTION)){
                        $this->sns->publishToEndpoint($id, $uuid, AlertRaised::MOTION, $this->aws->getSignedFileUrl($target_file), 0, "", $timestamp_str);
                    }
                }
                
                if ($ac->isPeopleCountEnabled()){
                    if ($this->checkNoRepeatAlert($uuid,  AlertRaised::PEOPLE_COUNT, $datetime, $ac->noRepeatTime(), 0, "")) return;
                    $output = Utils::getSSLPage("https://bingo.ibeyonde.com:5081/?cmd=new_count&image=".$target_file);
                    $output = json_decode($output, true);
                    //error_log("people count is = ".print_r($output["no_of_people"], true));
                    $this->aws->addWaterMark($output["no_of_people"], $target_file);
                    //$count = (int)$ac->getCountPeople();
                    //$calcount = (int)$output["no_of_people"];
                    //if ($calcount>=$count)
                    //{
                    //    $id = $this->addAlert($uuid, AlertRaised::PEOPLE_COUNT, $target_file, $output["no_of_people"],"people", $datetime);
                    //}
                    
                }
                
                if ($ac->isClassifyEnabled()){
                    
                    #$query_user = $this->db_connection->prepare('SELECT category FROM camera_manual WHERE uuid = :uuid ');
                    #$query_user->bindValue(':uuid', $uuid, PDO::PARAM_STR);
                    
                    #$query_user->execute();
                    
                    
                    #while($obj = $query_user->fetch()){
                     #   array_push($subcategory,$obj['category']);
                    #}
                    
                    
                    $output = Utils::getSSLPage("https://bingo.ibeyonde.com:5081/?cmd=Aclassify&image=".$target_file."&uuid=".$uuid);
                    error_log($output);
                    $output = json_decode($output, true);
                    //error_log("classification is = ".print_r($output['label'], true));
                    $this->aws->addWaterMark($output["label"], $target_file);
                    //if ($output['label']!=null)
                    //{
                    //    $this->aws->addWaterMark($output["label"], $target_file);
                    //    $id = $this->addAlert($uuid, AlertRaised::CLASSIFY, $target_file, $output["label"],"classify", $datetime);
                    //}
                }
            
            // Unusual motion
            
            /**$da = new DeviceActivity();
             $change = $da->checkActivity($uuid, $grid);
             error_log($uuid."Alert status for $grid = ".$change);
             if ($change > 16){
             $ar = new AlertRaised();
             $ar->addAlert($uuid, "UnusualMotion", $target_file, $change, $grid);
             }**/
        }
    }
    
    public function notifyDeviceOffline($uuid, $time_delta, $datetime){
        // ALERTS
        $ac = AlertConfig::loadDeviceAlertConfig($uuid);
        if ($ac == null) return;
        $timestamp_str = date_format($datetime, $this::DATE_FORMAT);
        // check if similar alert was raised in no_repeat_delta time
        //error_log("$uuid notifyMotion $timestamp_str ");
        if ($ac != null) {
            $id = $this->addAlert($uuid, AlertRaised::DEVICE_OFFLINE, "", $time_delta, "", $datetime);
            $this->em->sendEmailAlert($id, $ac->email, "Device Offile", 'Your device '.Device::getDeviceName($uuid).' is offline for '.$time_delta.' minutes noted at '. $timestamp_str .'  </br> </br>For more info goto https://app.ibeyonde.com ');
            $this->sns->publishToEndpoint($id, $uuid, AlertRaised::DEVICE_OFFLINE, '', 0, $time_delta, $timestamp_str);
        }
    }

}
