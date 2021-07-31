<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/config/config.php');

try {
    if (isset($_GET['email']) ){
        $email = $_GET['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "This ($email) email address is considered invalid.";
            exit;
        }
        $servername = "mysql.ibeyonde.com";
        $username = "admin";
        $password = "1b6y0nd6";
        $database = "ibe";
        
        $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
        $sql = "SELECT valid FROM email where email='$email'";
        $stmt = $db_connection->query($sql);
        $valid = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
    
        if ($valid == "0"){
            echo "Invalid waiting callback";
        }
        else if ($valid == "1"){
            echo "Valid subscribed";
        }
        else {
            echo "Invalid request";
        }

    }

}
catch( PDOException $e )
{
    echo $e->getMessage();
}


?>
