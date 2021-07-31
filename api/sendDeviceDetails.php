<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/Device.php');
require_once (__ROOT__.'/libraries/password_compatibility_library.php');
require_once(__ROOT__.'/libraries/aws.phar');

use Aws\Common\Enum\Region;
use Aws\Ses\SesClient;


if (isset($_GET["t"]) && isset($_GET["hn"]) && isset($_GET["ip"]) && isset($_GET["time"]) && isset($_GET["email"])){

    $uuid=$_GET["hn"];
    $token=$_GET["t"];
    $ts=$_GET["time"];
    $email=$_GET["email"];
    $type=$_GET["type"];
    $ip=$_GET["ip"];
    $data=file_get_contents('php://input');
    
    $utils = new Utils();
    $hltoken=$utils->getConfig('hltoken');
    
    
    $year=substr($ts, 0, 4);
    $month=substr($ts, 4, 2);
    $day=substr($ts, 6, 2);
    $hour=substr($ts, 8, 2);
    $minute=substr($ts, 10, 2);
    $second=substr($ts, 12, 2);
    
    $datetime=$day."/".$month."/".$year." - ".$hour.":".$minute.":".$second;
    
    if ( $token != $hltoken){//check token
        echo json_encode(array('errno' => 'token='.$token, 'msg' => 'Bad token'));
        error_log($uuid. 'Bad token'. $token);
    }
    else {
        
        try {
            $client = SesClient::factory(array(
                'version' => SES_VERSION,
                'key'    => SES_KEY,
                'secret' => SES_SECRET,
                'region' => SES_REGION
            ));
        }
        catch( Exception $e )
        {
            echo $e->getMessage();
        }
        
        $msg = array();
        $msg['Source'] = "no_reply@ibeyonde.com";
        $msg['Destination']['ToAddresses'][] = "$email";
        
        $msg['Message']['Subject']['Data'] = "Details of the $type - $uuid sent at $datetime";
        $msg['Message']['Subject']['Charset'] = "UTF-8";
        
        $msg['Message']['Body']['Html']['Data'] ="Your device [ $uuid ] is now configured and available on this ip $ip <br/><br/>
Click here to goto device web interface: <href='http://$ip/'>http://$ip/</a>
<br/><br/>
Other details are listed below: <br/>
<p>
<font color=grey>
$data
</font>
</p>
<br/>
<br/>
<b> Ibeyonde </b> 
<br/>
http://www.ibeyonde.com
<br/>
";
        $msg['Message']['Body']['Html']['Charset'] = "UTF-8";
        
        try{
            $result = $client->sendEmail($msg);
            $msg_id = $result->get('MessageId');
            echo("MessageId: $msg_id");
            //print_r($result);
        } catch (Exception $e) {
            echo($e->getMessage());
        }
        
        print_r($msg);
    }
}
else {
    echo "Param error ".print_r($_GET,True);
    echo file_get_contents('php://input');
}
         
?>

