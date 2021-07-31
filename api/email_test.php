<?php
$_SERVER['SERVER_NAME']="simonline.in";
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/libraries/aws.phar');
require_once(__ROOT__.'/config/config.php');


use Aws\Ses\SesClient;


$client = SesClient::factory(array(
        'version' => SES_VERSION,
        'key'    => SES_KEY,
        'secret' => SES_SECRET,
        'region' => SES_REGION
));

$msg = array();
$msg['Source'] = EMAIL_VERIFICATION_FROM;
$msg['Destination']['ToAddresses'][] = "agneya2001@gmail.com";

$msg['Message']['Subject']['Data'] = EMAIL_VERIFICATION_SUBJECT;
$msg['Message']['Subject']['Charset'] = "UTF-8";

$msg['Message']['Body']['Html']['Data'] = "Hellow owrld world";
$msg['Message']['Body']['Html']['Charset'] = "UTF-8";

$result = $client->sendEmail($msg);
$msg_id = $result->get('MessageId');

error_log("agneya2001@gmail.com"." Msg id=".$msg_id. " sent from ".EMAIL_VERIFICATION_FROM);

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
$msg['Source'] = "no_reply@simonline.in";
$msg['Destination']['ToAddresses'][] = "agneya2001@gmail.com";

$msg['Message']['Subject']['Data'] = "Hello World TWO";
$msg['Message']['Subject']['Charset'] = "UTF-8";

$msg['Message']['Body']['Html']['Data'] ="HTML Data of email<br />";
$msg['Message']['Body']['Html']['Charset'] = "UTF-8";

try{
     $result = $client->sendEmail($msg);
     $msg_id = $result->get('MessageId');
     echo("MessageId: $msg_id");
     print_r($result);
} catch (Exception $e) {
     echo($e->getMessage());
}

print_r($msg);

?>
