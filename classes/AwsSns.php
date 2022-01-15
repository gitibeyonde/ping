<?php
#define ( '__ROOT__',   dirname ( dirname ( __FILE__ )));

require_once (__ROOT__ . '/libraries/aws.phar');
require_once(__ROOT__.'/classes/Motion.php');
require_once(__ROOT__.'/classes/DeviceToken.php');
require_once(__ROOT__.'/classes/AlertRaised.php');
require_once(__ROOT__.'/classes/Device.php');
require_once(__ROOT__.'/config/config.php');

class AwsSns {
    private static $sns = null;
    private static $queue_dict = array ();
    private static $credentials = array (
                    'version' => S3_VERSION,
                    'key'    => S3_KEY,
                    'secret' => S3_SECRET,
                    'region' => S3_REGION
            );
    
    private static $app_arn = "arn:aws:sns:ap-south-1:574451441288:app/GCM/CleverCam";//"arn:aws:sns:us-west-2:574451441288:app/GCM/CleverCam";
    
    public function __construct() {
        if (self::$sns == null) {
            self::$sns = Aws\Sns\SnsClient::factory ( self::$credentials  );
        }
    }
    
    public function createEndPoint($username, $system, $devicetoken, $phone_id){
        $result = AwsSns::$sns->createPlatformEndpoint([
            'Attributes' => ['Enabled' => 'true'],
            'CustomUserData' => "%%$username%%$phone_id%%",
            'PlatformApplicationArn' => AwsSns::$app_arn, 
            'Token' => $devicetoken, 
        ]);
        error_log("Resultof createEndPoint = ".$result['EndpointArn']);
        return $result['EndpointArn'];
    }
    
    public function deleteEndPoint($endpoint_arn){
        $result = AwsSns::$sns->deleteEndpoint([
            'EndpointArn' => $endpoint_arn,
        ]);
        error_log("Resultof deleteEndPoint = ".$result);
        return $result;
    }
    
    //{"GCM":"{\"data\":{\"id\":\"30525\",\"title\":\"High Temperature of 36 Deg C detected on LakeView\",\"uuid\":\"3729a83d\",
    //\"name\":\"LakeView\",\"image\":\"\",\"value\":\"36\",\"comment\":\"Deg C\",\"created\":\"2018-07-23 13:24:52 Asia\\\/Calcutta\"}}”}
    public function publishToEndpoint($id, $uuid, $alert_type, $image, $value, $comment, $timestamp_str){
        $dt = new DeviceToken();
        $endpoint_arns = $dt->loadDeviceTokensForDevice($uuid);
       
       
        foreach ($endpoint_arns as &$endpoint) {
	    error_log("End point ----".print_r($endpoint->token, true));
            $json_message=null;
            if ($endpoint->system == 'iOS') {
                $json_message = json_encode(array(
                    'GCM' => json_encode(array(
                        "to" => $uuid,
                        'notification' => array(
                            'title' => Device::getDeviceName($uuid),
                            'subtitle' => AlertRaised::getAlertString($uuid, $alert_type, $value, $comment),
                            'body' => $timestamp_str,
                            'mutable_content' => true,
                            'image' => $image,
                            'id' => $id,
                            'type' => $alert_type,
                            'uuid' => $uuid,
                            'name' => Device::getDeviceName($uuid),
                            'value' => $value,
                            'comment' => $comment,
                            'created' => $timestamp_str,
                        ),
                    ))
                ));
            }
            else {
                //continue;
                $json_message = json_encode(array(
                    'GCM' => json_encode(array(
                        "to" => $uuid,
                        'data' => array(
                            'id' => $id,
                            'title' =>  AlertRaised::getAlertString($uuid, $alert_type, $value, $comment),
                            'type' => $alert_type,
                            'uuid' => $uuid,
                            'name' => Device::getDeviceName($uuid),
                            'image' => $image,
                            'value' => $value,
                            'comment' => $comment,
                            'created' => $timestamp_str,
                        ),
                    ))
                ));
            }
            error_log("publishToEndpoint".$json_message);
            try {
                error_log("Endpoint arn = ".  $endpoint->endpoint_arn);
                $result = AwsSns::$sns->publish([
                    'Message' => $json_message, 
                    'MessageStructure' => 'json',
                    'TargetArn' => $endpoint->endpoint_arn,
                ]);
                error_log("publishToEndpoint".$result['MessageId']);
            }
            catch (Aws\Sns\Exception\SnsException $e){
                if (strpos('EndpointDisabled', $e->getMessage())){
                    error_log("Error on publish  endpoint disable ".$endpoint->endpoint_arn);
                    $dt->deleteDeviceToken($endpoint->token);
                    $this->deleteEndPoint($endpoint->endpoint_arn);
                }
            }
        }
    }
}


?>
