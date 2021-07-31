<?php
// include the config
require_once (__ROOT__ . '/config/config.php');
require_once(__ROOT__.'/classes/User.php');

class GsmDevice
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
    
    
    public function updateGsmDevice($uuid, $user_name, $my_number)
    {
        if ($this->databaseConnection()) {
            $user = new User($user_name);
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into gsm_device(uuid, user_id, my_number, changedOn) '.
                    'values(:uuid, :user_id,  :my_number, now())  on duplicate key update changedOn=now(), user_id=VALUES(user_id), my_number=VALUES(my_number);');
            $sth->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user->user_id, PDO::PARAM_STR);
            $sth->bindValue(':my_number', $my_number, PDO::PARAM_STR);
            $sth->execute();
            error_log("logSms Error=" . implode(",", $sth->errorInfo()).$user->user_id);
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    public function getJob($uuid, $type, $count){
        $jobs = array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select * from gsm_device where uuid=:uuid and type=:type limit :count');
            $sth->bindValue(':uuid', $uuid, PDO::PARAM_STR);
            $sth->bindValue(':type', $type, PDO::PARAM_STR);
            $sth->bindValue(':count', $count, PDO::PARAM_STR);
            $sth->execute();
            while($obj =  $sth->fetch()){
                $jobs[]=$obj;
            }
            error_log("getSmsLog Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return $jobs;
    }
    
    
    public function deleteJob($uuid, $id )
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from gsm_device where uuid=:uuid and id < :id');
            $sth->bindValue(':id', $id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $uuid, PDO::PARAM_STR);
            $sth->execute();
            error_log("deleteChatbot Error=" . implode(",", $sth->errorInfo()).$uuid."--".$id);
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
   
}