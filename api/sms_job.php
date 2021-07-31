<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/SmsJob.php');
require_once(__ROOT__.'/classes/SmsLog.php');

//error_log(print_r($_POST, true));
//error_log(print_r($_GET, true));
if (isset($_GET["uuid"])  &&  isset($_POST['t']) && isset($_GET["opr"])){
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
        $smsjob = new SmsJob();
        $operation=urldecode($_GET["opr"]);
        
        if ($operation == "get"){
            $resp=array();
            for ($i=0; $i< 120; $i++){
                $resp = $smsjob->getJobForDevice($uuid);
                if (sizeof($resp) > 0){
                    break;
                }
                else {
                    sleep(1);
                }
            }
            echo json_encode($resp);
            $smslog = new SmsLog();
            $smslog->pingGsmHealth($uuid, "gsm", "1");
        }
        else if ($operation == "del"){
            $id=urldecode($_GET["id"]);
            $smsjob->deleteJob($uuid, $id);
            echo json_encode(array('errno' => 'success', 'msg' => 'Delete Successful'));
        }
        else {
            error_log($uuid."Unknown Op");
            echo json_encode(array('errno' => 'param_402', 'msg' => 'Unknow operation'));
        }
    }
}
else {
    echo json_encode(array('errno' => 'param_402', 'msg' => 'Bad Params'));
}

?>

