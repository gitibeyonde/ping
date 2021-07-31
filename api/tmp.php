<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/libraries/aws.phar');

use Aws\Common\Enum\Region;
use Aws\Ses\SesClient;

try {
    echo "1Email=".$_GET['email']."</br>\n";
    if (isset($_GET['email'])){
        $email = $_GET['email'];

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

        echo "SELECT valid FROM email where email='$email';</br>\n";
        $result = mysql_query("SELECT valid FROM email where email='$email';");
        if ($result){
                        $row = mysql_fetch_assoc($result);
                        $valid = $row['valid'];
                }

        echo "Valid=$valid Valid</br>";

        $client = SesClient::factory(array(
                    'version' => AWS_VERSION,
                    'key'    => AWS_KEY,
                    'secret' => AWS_SECRET,
                    'region' => AWS_REGION
        ));

        $msg = array();
        $msg['Source'] = "no_reply@ibeyonde.com";
        $msg['Destination']['ToAddresses'][] = $email;

        $msg['Message']['Subject']['Data'] = "Text only subject";
        $msg['Message']['Subject']['Charset'] = "UTF-8";

        $msg['Message']['Body']['Text']['Data'] ="Text data of email";
        $msg['Message']['Body']['Text']['Charset'] = "UTF-8";
        $msg['Message']['Body']['Html']['Data'] ="HTML Data of email<br />";
        $msg['Message']['Body']['Html']['Charset'] = "UTF-8";
        
        echo "</br>\n".$msg."</br>\n";

        $result = $client->sendEmail($msg);
        $msg_id = $result->get('MessageId');
        echo("MessageId: $msg_id");
        print_r($result);

        $sql="replace into email (email, valid, last_update) values ( '$email', 0, now());";

        if (!mysql_query($sql, $link)) {
            die('Error: ' . mysql_error()); 
        }

        mysql_close($link);

    }

}
catch( Exception $e )
{
    echo $e->getMessage();
}


?>
