<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/SmsAgent.php');
require_once(__ROOT__.'/classes/SmsChatBot.php');
require_once(__ROOT__.'/classes/GsmDevice.php');
require_once(__ROOT__.'/classes/SmsUtils.php');

if (isset($_GET["uuid"])  &&  isset($_POST['t']) && isset($_GET["type"])){
    $uuid=urldecode($_GET["uuid"]);
    $token=urldecode($_POST["t"]);
    
    $utils = new Utils();
    
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. 'Bad token'. $tok_code);
        die;
    }
    else {
        $type=urldecode($_GET["type"]);
        
        if ($type == "response"){
            $utils = new SmsUtils();
            $my_phone = $utils->normalizePhoneNoPlus(urldecode($_GET["my_phone"]));
            $there_phone = $utils->normalizePhoneNoPlus(urldecode($_GET["there_phone"]));
            $sms = urldecode($_POST["sms"]);
            
            $dev = new GsmDevice();
            $user_id = $dev->getGsmDeviceFromUuid($uuid)['rentee'];
            error_log($uuid." = ".$user_id. "==".$my_phone);
            if ($user_id == null){
                error_log($uuid."Gsm Not configured");
                echo json_encode(array('errno' => 'param_402', 'msg' => 'The number does not belong to any user.'));
            }
            $bot = new SmsChatBot();
            $bots = $bot->getUserChatbotsForNumber($user_id, $my_phone);
            
            $int = new SmsAgent();
            //can_send=requests.post('http://simonline.in/api/sms_check_last_messages.php?uuid=%s&my_number=%s&there_number=%s'%(uuid, my_number, phone), { 't' : token }).content.strip().decode('utf-8')
            
            $response = $int->processChat($bots['id'], $there_phone, $sms);
            echo json_encode($response);
        }
        else {
            error_log($uuid."Unknown Op");
            echo json_encode(array('errno' => 'param_402', 'msg' => 'Unknown operation'));
        }
    }
}
else {
    echo json_encode(array('errno' => 'param_402', 'msg' => 'Bad Params'));
}

?>

