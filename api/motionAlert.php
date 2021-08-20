<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/MotionFile.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/Device.php');
require_once(__ROOT__.'/classes/DeviceActivity.php');
require_once(__ROOT__.'/classes/AlertRaised.php');
require_once(__ROOT__.'/classes/AlertConfig.php');
require_once(__ROOT__.'/classes/EmailUtils.php');
require_once(__ROOT__.'/classes/Aws.php');
require_once(__ROOT__.'/libraries/aws.phar');
require_once(__ROOT__.'/libraries/password_compatibility_library.php');

use Aws\S3\S3Client;
use Aws\Common\Enum\Region;

session_start();
set_time_limit(20);

$bucket='data.ibeyonde';

function outputRequest(){
     error_log("FILE CONTENT ".file_get_contents( 'php://input' ));
     error_log("HEADER ".print_r(apache_request_headers(), true));
     error_log("SERVER ".print_r($_SERVER, true));
     error_log("POST ".print_r($_POST, true));
     error_log("GET ".print_r($_GET, true));
     error_log("FILEs ".print_r($_FILES, true));
}

//outputRequest();

if (isset($_GET["hn"]) && isset($_GET["tz"]) && strlen(basename($_FILES["fileToUpload"]["name"])) > 0){
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
    $timezone=urldecode($_GET["tz"]);
    $type=$_GET["tp"];
    $grid="";
    if (isset($_GET["grid"])){
        $grid=$_GET["grid"];
    }

    $utils = new Utils();
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. ' Bad token ' . $tok_code . ' bad token is '.$token);
        die();
    }


    $s3client = S3Client::factory(array(
                    'version' => S3_VERSION,
                    'key'    => S3_KEY,
                    'secret' => S3_SECRET,
                    'region' => S3_REGION
    ));

    if ($_FILES["fileToUpload"]["error"] != 0){
        echo json_encode(array('errno' => 'file_105', 'msg' => 'File upload failed error='.print_r($_FILES["fileToUpload"]["error"], TRUE)));
        error_log($uuid.' File upload failed for '.$_FILES["fileToUpload"]["name"] . ' with error ' . $_FILES["fileToUpload"]["error"] . ' size is ' . $_FILES["fileToUpload"]["size"]);
        //outputRequest();
        die();
    }

    if ($_FILES["fileToUpload"]["type"] != 'image/jpeg'){
        echo json_encode(array('errno' => 'file_106', 'msg' => 'Bad format '.$_FILES["fileToUpload"]["type"] ));
        error_log($uuid.' - bad format for '.$_FILES["fileToUpload"]["name"] . ' with error ' .$_FILES["fileToUpload"]["type"]);
        die();
    }

    if ($_FILES["fileToUpload"]["size"] > 100000000) {
        echo json_encode(array('errno' => 'file_102', 'msg' => 'File is too LARGE'));
        error_log($uuid. 'File is too large for '.$_FILES["fileToUpload"]["name"] . ' with error ' . $_FILES["fileToUpload"]["size"]);
        die();
    }

    $motion = new MotionFile(basename($_FILES["fileToUpload"]["name"]));
    $target_file="";

    if ($type == "FACE"){
        $target_file = $uuid."/".$motion->directory."/".$motion->time."/FC".Utils::randomString(4).".jpg";
    }
    else {
        $target_file = $uuid."/".$motion->directory."/".$motion->time."/".Utils::randomString(6).".jpg";
    }

    try {
        $upload = $s3client->upload($bucket, $target_file, fopen($_FILES['fileToUpload']['tmp_name'], 'rb'),
                'private', array('params' => array('ContentType' => 'image/jpeg')));
        if ($upload) {
            $timestamp = time();
            if (!isset($_GET["tz"])){
                //$cw->publishMetrics($uuid, 'Motion', 1, 'None', time());
                $utils->publishMotion($uuid, $timestamp, $target_file);
            }
            else {
                //error_log("Motion Time from device=".$motion->datetime.", Motion Timezone=".$timezone);
                $dtz = new DateTimeZone($timezone);
                $datetime = DateTime::createFromFormat('d/m/Y - H:i:s', $motion->datetime, $dtz);
                $utils->publishMotion($uuid, $datetime, $target_file);
            }
            if ($type=="MOTION") {
                $utils->updateLastAlert($uuid, $target_file);
            }
            // ALERTS
            $ar = new AlertRaised();
            $ar->notifyMotion($uuid, $type, $target_file, $grid, $datetime);
            echo json_encode(array('success' => '0'));
        } else {
            echo json_encode(array('errno' => 'file_104', 'msg' => 'There was an error uploading your file'));
            error_log('There was an error uploading your file');
        }
    }
    catch (Exception $e){
        echo json_encode(array('errno' => 'file_105', 'msg' => 'Exception uploading your file'.$e->getMessage()));
        error_log('Exception uploading your file'.$e->getMessage());
    }
}
else {
    echo json_encode(array('errno' => 'param_101', 'msg' => 'Param error'));
    outputRequest();
}

?>


