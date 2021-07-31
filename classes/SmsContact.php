<?php
// include the config
require_once (__ROOT__ . '/config/config.php');

class SmsContact
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
    
    public function createContact($user_id, $fname, $lname, $type, $number, $email, $comments)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into sms_contact(uuid, user_id, type, tid, my_number, there_number,  direction, sms, changedOn) '.
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
            error_log("createContact Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    public function getContact($user_id){
        $stmt = array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select * from sms_contact where user_id=:user_id and type=:type and tid=:tid');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':type', $type, PDO::PARAM_STR);
            $sth->bindValue(':tid', $tid, PDO::PARAM_STR);
            $sth->execute();
            while($obj =  $sth->fetch()){
                $stmt[]=$obj;
            }
            error_log("getContact Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return $stmt;
    }
    
}