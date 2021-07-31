<?php 

class User
{
    public $user_id = null;
    public $user_name = "";
    public $role = "";  // ADMIN,USER,PAID,DEV
    public $user_email = "";
    
    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();
    

    public function __construct($user_name)
    {
        // if database connection opened
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $query_user = $this->db_connection->prepare('SELECT * FROM users WHERE user_name = :user_name');
            $query_user->bindValue(':user_name', $user_name, PDO::PARAM_STR);
            $query_user->setFetchMode(PDO::FETCH_CLASS, 'User');
            $query_user->execute();
            // get result row (as an object)
            $usr = $query_user->fetchObject();
            $this->user_id = $usr->user_id;
            $this->user_name = $usr->user_name;
            $this->role = $usr->role;
            $this->user_email = $usr->user_email;
        }
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
}
    
?>