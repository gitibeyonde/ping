<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/libraries/aws.phar');
require_once(__ROOT__.'/config/config.php');

use Aws\Common\Enum\Region;
use Aws\Ses\SesClient;

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
$msg['Destination']['ToAddresses'][] = "abhinandan.prateek@ibeyonde.com";

$msg['Message']['Subject']['Data'] = "Text only subject";
$msg['Message']['Subject']['Charset'] = "UTF-8";

$msg['Message']['Body']['Text']['Data'] ="Text data of email";
$msg['Message']['Body']['Text']['Charset'] = "UTF-8";
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
