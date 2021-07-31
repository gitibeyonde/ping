<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/SmsLog.php');


//Array\n(\n    [t] => ITQT8irirp\n    [uid] => 95\n    [my] => +918297150231\n    [thr] => 09701199011\n    [d] => 0\n    [typ] => trig\n    [tid] => 1\n    
//[sms] => Dear abhi, 991209 is your one time password (OTP). Please enter the OTP to proceed. Thank you, simonlin\n)\n
// Array\n(\n    [uuid] => 1ffea827\n)\n


if (isset($_GET["uuid"])  &&  isset($_POST['t']) && isset($_POST["uid"]) && isset($_POST["my"]) && isset($_POST["thr"]) && isset($_POST["d"]) 
        && isset($_POST["typ"]) && isset($_POST["tid"]) && isset($_POST["sms"])){
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
        $smslogs = new SmsLog();
        $uid=urldecode($_POST["uid"]);
        $my=trim(urldecode($_POST["my"]));
        $there=trim(urldecode($_POST["thr"]));
        $direction=urldecode($_POST["d"]);
        $type=urldecode($_POST["typ"]);
        $tid=urldecode($_POST["tid"]);
        $sms=urldecode($_POST["sms"]);
        
        $id = $smslogs->logSms($uuid, $uid, $type, $tid, $my, $there, $direction, $sms, null);
        echo $id;
    }
    echo json_encode(array('errno' => 'success', 'msg' => 'Logged'));
}
else {
    echo json_encode(array('errno' => 'param_402', 'msg' => 'Bad Params'));
}

?>

