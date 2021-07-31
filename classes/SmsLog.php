<?php
// include the config
require_once (__ROOT__ . '/config/config.php');

class SmsLog
{
    
    private $db_connection = null;
    
    public function __construct()
    {
    }
    private function databaseConnection()
    {
        // if connection already exists
        if ($this->db_connection != null) {
            return true;
        } else {
            try {
                $this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                return true;
            } catch (PDOException $e) {
                $_SESSION['message']  = MESSAGE_DATABASE_ERROR . $e->getMessage();
            }
        }
        return false;
    }
    public function logSms($uuid, $user_id, $type, $tid, $my_number, $there_number, $direction, $sms, $ts)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into sms_log(uuid, user_id, type, tid, my_number, there_number,  direction, sms, changedOn) '.
                    'values(:uuid, :user_id, :type, :tid, :my_number, :there_number, :direction, :sms, now())');
            $sth->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $sth->bindValue(':my_number', $my_number, PDO::PARAM_STR);
            $sth->bindValue(':type', $type, PDO::PARAM_STR);
            $sth->bindValue(':tid', $tid, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':there_number', $there_number, PDO::PARAM_STR);
            $sth->bindValue(':direction', $direction, PDO::PARAM_STR);
            $sth->bindValue(':sms', $sms, PDO::PARAM_STR);
            $sth->execute();
            error_log("logSms Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    public function getSmsLog($user_id, $type, $tid){
        $stmt = array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select * from sms_log where user_id=:user_id and type=:type and tid=:tid');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':type', $type, PDO::PARAM_STR);
            $sth->bindValue(':tid', $tid, PDO::PARAM_STR);
            $sth->execute();
            while($obj =  $sth->fetch()){
                $stmt[]=$obj;
            }
            error_log("getSmsLog Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return $stmt;
    }
    
    public function getSmsLogCount($user_id, $type, $tid){
        $stmt = 0;
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select count(*) from sms_log where user_id=:user_id and type=:type and tid=:tid');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':type', $type, PDO::PARAM_STR);
            $sth->bindValue(':tid', $tid, PDO::PARAM_STR);
            $sth->execute();
            $stmt =  $sth->fetch()[0];
            error_log("getSmsLogCount Error=" . implode(",", $sth->errorInfo()).print_r($stmt, true));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return intval($stmt);
    }
    
    public function getSentCountSinceMidnight($number){
        $stmt = 0;
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select count(*) from sms_log where my_number=:number and changedOn > DATE_FORMAT(now(), "%Y-%m-%d 00:00:00")');
            $sth->bindValue(':number', $number, PDO::PARAM_STR);
            $sth->execute();
            $stmt =  $sth->fetch()[0];
            error_log("getSentCountSinceMidnight Error=" . implode(",", $sth->errorInfo()).print_r($stmt, true));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return intval($stmt);
    }
    
    
    public function getLastSurveySent($my_number, $there_number){
        $stmt = null;
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select changedOn from sms_log where my_number=:my_number and there_number=:there_number order by changedOn desc limit 1');
            $sth->bindValue(':my_number', $my_number, PDO::PARAM_STR);
            $sth->bindValue(':there_number', $there_number, PDO::PARAM_STR);
            $sth->execute();
            $stmt=$sth->fetch()[0];
            error_log("getLastSurveySent Error=" . implode(",", $sth->errorInfo()).print_r($stmt, true));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return $stmt;
    }
    
    
    public function getLastChatEntry($my_number, $there_number){
        $stmt = array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select * from sms_log where my_number=:my_number or there_number=:there_number order by changedOn desc limit 1');
            $sth->bindValue(':my_number', $my_number, PDO::PARAM_STR);
            $sth->bindValue(':there_number', $there_number, PDO::PARAM_STR);
            $sth->execute();
            $stmt =  $sth->fetch();
            error_log("getLastChatEntry Error=" . implode(",", $sth->errorInfo()).print_r($stmt, true));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return $stmt;
    }
    
    ///////////////////TRIGGER////////////////////////
    
    public function getTriggerReport($user_id, $trigger_id){
        return $this->getSmsLog($user_id, 'trig', $trigger_id);
    }
    
    public function getTriggerReportCount($user_id, $trigger_id){
        return $this->getSmsLogCount($user_id, 'trig', $trigger_id);
    }
    
    ///////////////////SURVEY////////////////////////
    
    public function getSurveyReport($user_id, $survey_id){
        return $this->getSmsLog($user_id, 'surv', $survey_id);
    }
    
    public function getSurveyReportCount($user_id, $survey_id){
        return $this->getSmsLogCount($user_id, 'surv', $survey_id);
    }
    
    
    public function getGsmHealth($uuid, $from){
        $stmt = array();
        if ($this->databaseConnection()) {
            $date = Utils::$dt->setTimeStamp($from);
            // database query, getting all the info of the selected usersms_log
            $sth = $this->db_connection->prepare('select * from  gsm_health where uuid=:uuid and changedOn > :time order by changedOn desc');
            $sth->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $sth->bindValue(':time', $date->format(DateTime::ATOM), PDO::PARAM_STR);
            $sth->execute();
            while($obj =  $sth->fetch()){
                $stmt[]=$obj;
            }
            error_log("getLastChatEntry Error=" . implode(",", $sth->errorInfo()).print_r($stmt, true));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return $stmt;
    }
    
    public function pingGsmHealth($uuid, $type, $alive)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into gsm_health(uuid, type, alive, changedOn) '.
                    'values(:uuid, :type, :alive, now())');
            $sth->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $sth->bindValue(':type', $type, PDO::PARAM_STR);
            $sth->bindValue(':alive', $alive, PDO::PARAM_STR);
            $sth->execute();
            error_log("pingGsmHealth Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
}