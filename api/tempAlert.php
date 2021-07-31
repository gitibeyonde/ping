<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/AlertConfig.php');


if (isset($_POST['t']) && isset($_GET["hn"]) && isset($_GET["time"]) && isset($_GET["temp"])&& isset($_GET["humid"]) && isset($_GET["tz"])){
    $uuid=urldecode($_GET["hn"]);
    $token=urldecode($_POST["t"]);
    $time=$_GET["time"];
    $temp=$_GET["temp"];
    $timezone=$_GET["tz"];
    $humid=$_GET["humid"];
    if (isset($_GET["mean"])){
        $mean=$_GET["mean"];
        $rms=$_GET["rms"];
        $var=$_GET["var"];
        $median=$_GET["median"];
    }
    $utils = new Utils();
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. 'Bad token'. $tok_code);
        die;
    }
    else {
        //error_log("Temperature Time from device=".$time.", Motion Timezone=".$timezone);
        $dtz = new DateTimeZone($timezone);
        $datetime = DateTime::createFromFormat('YmdHis', $time, $dtz);
        if (empty($datetime)){
            error_log("Inavlid date time received in tempAlert $timezone  $time  $datetime->date");
            return;
        }
        if (isset($_GET["mean"])){
            $utils->publishStats($uuid, $datetime, $temp, $humid, $mean, $rms, $var, $median);
        }
        else {
            $utils->publishStats($uuid, $datetime, $temp, $humid, "0", "0", "0", "0");
        }
        // ALERTS
        $ar = new AlertRaised();
        $ar->notifyTempAndHumidity($uuid, $temp, $humid, $datetime);
    }
}
else {
    echo json_encode(array('errno' => 'param_402', 'msg' => 'Bad Params'));
}
         
?>

