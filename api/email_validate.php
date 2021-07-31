<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/libraries/aws.phar');
require_once(__ROOT__.'/config/config.php');

use Aws\Common\Enum\Region;
use Aws\Ses\SesClient;


try {
    if (isset($_GET['email'])){
        $email = $_GET['email'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "This ($email) email address is considered invalid.";
            return;
        }
        
        $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
        $sql = "SELECT valid FROM email where email='$email'";
        $stmt = $db_connection->query($sql);
        $valid = $stmt->fetchAll(PDO::FETCH_ASSOC)[0];
        
    
        if ($valid == 0){
            $uuid=uniqid();
            $client = SesClient::factory(array(
                    'version' => SES_VERSION,
                    'key'    => SES_KEY,
                    'secret' => SES_SECRET,
                    'region' => SES_REGION
            ));
        
            $msg = array();
            $msg['Source'] = "no_reply@ibeyonde.com";
            $msg['Destination']['ToAddresses'][] = $email;

            $msg['Message']['Subject']['Data'] = "validate email for alerts from your device";
            $msg['Message']['Subject']['Charset'] = "UTF-8";

            $text ="To validate your email id please click on the following link:<br/>http://ping.ibeyonde.com/subscribe.php?email=$email&uuid=$uuid<br/>If this does not work then cut and paste it in your browser.<br/><br/>By doing so you agree to receive alerts from your device. In case you do not want to receive such alerts in future you can go to your device to re-configure the alerts.<br/><br/>-IbeyondE Team</br>";
            $msg['Message']['Body']['Html']['Data'] = $text;
            $msg['Message']['Body']['Html']['Charset'] = "UTF-8";

            try{
                $result = $client->sendEmail($msg);
                $msg_id = $result->get('MessageId');
                echo("MessageId: $msg_id");
                $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                $sql="replace into email (email, valid, last_update, uuid, msgid) values ( '$email', 0, now(), '$uuid', '$msg_id');";
                $db_connection->exec($sql);
            } catch (Exception $e) {
                 echo($e->getMessage());
            }
                
        }

    }

}
catch( Exception $e )
{
    echo $e->getMessage();
}


?>
