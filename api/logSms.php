<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/SmsUtils.php');

error_log(print_r($_POST, true));
error_log(print_r($_GET, true));

if (isset($_POST['t']) && isset($_GET["uuid"])  && isset($_POST["my"]) && isset($_POST["there"]) && isset($_POST["ctxt"])
    && isset($_POST["sent"]) && isset($_POST["rcvd"]) && isset($_POST["date"]) && isset($_POST["time"])){
    $uuid=urldecode($_GET["uuid"]);
    $token=urldecode($_POST["t"]);
    $my=urldecode($_POST["my"]);
    $there=urldecode($_POST["there"]);
    $context=urldecode($_POST["ctxt"]);
    $rcvd=urldecode($_POST["rcvd"]);
    $sent=urldecode($_POST["sent"]);
    $date=urldecode($_POST["date"]);
    $time=urldecode($_POST["time"]);
    
    $utils = new Utils();
    $smsUtils = new SmsUtils();
    
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. 'Bad token'. $tok_code);
        die;
    }
    else {
        $smsUtils->logSms($uuid, $my, $there, $context, $rcvd, $sent, $date, $time);
    }
}
else {
    echo json_encode(array('errno' => 'param_402', 'msg' => 'Bad Params'));
}

?>

