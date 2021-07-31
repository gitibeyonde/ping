<?php


define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/libraries/aws.phar');

use Aws\Common\Enum\Region;
use Aws\Ses\SesClient;


try {
    if (isset($_GET['email']) && isset($_GET['uuid'])){
        $email = $_GET['email'];
        $uuid = $_GET['uuid'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "This ($email) email address is considered invalid.";
            exit;
        }
        $servername = "mysql.ibeyonde.com";
        $username = "admin";
        $password = "1b6y0nd6";
        $database = "ibe";

        $link = mysql_connect($servername,$username,$password);
        if (!$link) {
            die('Not connected : ' . mysql_error());
        }

        $db_selected = mysql_select_db($database, $link);
        if (!$db_selected) {
            die ('Can\'t use foo : ' . mysql_error());
        }

        $valid=-1;
        $sql = "SELECT valid FROM email where email='$email' and uuid='$uuid'";
        $result = mysql_query($sql);
        if ($result){
            $row = mysql_fetch_assoc($result);
            $valid = $row['valid'];
        }
        else {
            echo "Invalid request";
            exit;
        }
    
        if ($valid == "0"){
            $client = SesClient::factory(array(
                    'version' => SES_VERSION,
                    'key'    => SES_KEY,
                    'secret' => SES_SECRET,
                    'region' => SES_REGION
            ));
        
            $msg = array();
            $msg['Source'] = "no_reply@ibeyonde.com";
            $msg['Destination']['ToAddresses'][] = $email;

            $msg['Message']['Subject']['Data'] = "email address now subscribes to alerts";
            $msg['Message']['Subject']['Charset'] = "UTF-8";

            $text ="You are now subscribed to alerts.<br/><br/>In case you do not want to receive such alerts in future you can go to your device to re-configure the alerts.<br/><br/>-IbeyondE Team</br>";
            $msg['Message']['Body']['Html']['Data'] = $text;
            $msg['Message']['Body']['Html']['Charset'] = "UTF-8";

            try{
                $result = $client->sendEmail($msg);
                $msg_id = $result->get('MessageId');
                echo("MessageId: $msg_id");
                echo "</br> You have been subscribed to alert service";
                
                $sql="replace into email (email, valid, last_update, uuid, msgid) values ( '$email', 1, now(), '$uuid', '$msg_id');";
                if (!mysql_query($sql, $link)) {
                    die('Error: ' . mysql_error()); 
                }
            } catch (Exception $e) {
                 echo($e->getMessage());
            }
                
        }
        else if ($valid == "1"){
            echo "Email is already subscribed";
        }
        else {
            echo "Invalid request";
            exit;
        }


        mysql_close($link);

    }

}
catch( Exception $e )
{
    echo $e->getMessage();
}


?>
