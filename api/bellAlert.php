<?php

define ( '__ROOT__',   dirname ( dirname ( __FILE__ )));

require_once(__ROOT__.'/classes/AlertRaised.php');
require_once(__ROOT__.'/classes/Utils.php');

if (isset($_GET["hn"])){
    if (isset($_POST["t"])){
        $token=urldecode($_POST["t"]);
    }
    else if (isset($_SERVER["HTTP_TOKEN"])){
        $token=$_SERVER["HTTP_TOKEN"];
    }
    else {
        echo json_encode(array('errno' => 'param_102', 'msg' => 'Token error'));
        die;
    }

    $uuid=urldecode($_GET["hn"]);
    $utils = new Utils();
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. ' Bad token ' . $tok_code . ' bad token is '.$token);
        die();
    }

    $ar = new AlertRaised();

    $timezone=urldecode($_GET["tz"]);
    if (isset($_GET["ts"])){
	$ts=urldecode($_GET["ts"]);
        $time = substr($ts, 0, 4). "/" .substr($ts, 4, 2). "/" .substr($ts, 6, 2) ." - " .substr($ts, 8, 2).":".substr($ts, 10, 2).":".substr($ts, 12, 2);
	//error_log($ts);
        $dtz = new DateTimeZone($timezone);
	//error_log($time);// 2021/10/03 - 21:22:38
        $datetime = DateTime::createFromFormat('Y/m/d - H:i:s', $time, $dtz);
    }
    else {
        $datetime = new DateTime();
        $timezone = new DateTimeZone($timezone);
        $datetime->setTimezone($timezone);
    }
    $ar = new AlertRaised();
    $ar->notifyBellRing($uuid, $datetime);

    echo json_encode(array('success' => '0'));
}
else {
    echo json_encode(array('errno' => 'param_101', 'msg' => 'Param error'));
}
?>

