<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/Device.php');
require_once(__ROOT__.'/classes/GsmDevice.php');
require_once (__ROOT__.'/libraries/password_compatibility_library.php');


if (isset($_GET["t"]) && isset($_GET["hn"]) && isset($_GET["type"]) && isset($_GET["time"])){

    $uuid=$_GET["hn"];
    $token=$_GET["t"];
    $time=$_GET["time"];
    $type=$_GET["type"];
    $data=file_get_contents('php://input');
    
    $utils = new Utils();
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. 'Bad token'. $tok_code);
    }
    else {
        if ($type=="device"){
            $device = new Device();
            $dev = $device->loadDevice($uuid);
            $dev->updateSettings($data);
        }
        else if ($type == "gsm"){
            $settings = (array)json_decode($data);
            $gsmd = new GsmDevice();
            $gsmd->updateGsmDevice($uuid, $settings['user'], $settings['phone']);
        }
    }
}
else {
    echo "Param error ".print_r($_GET,True);
    echo file_get_contents('php://input');
}
         
?>

