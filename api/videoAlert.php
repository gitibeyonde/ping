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


if (isset($_POST['t']) && isset($_GET["hn"]) && isset($_GET["tz"]) && strlen(basename($_FILES["fileToUpload"]["name"])) > 0){
    
    $token=urldecode($_POST["t"]);
    $uuid=urldecode($_GET["hn"]);
    $timezone=urldecode($_GET["tz"]);
    $time=urldecode($_GET["time"]);
    $type=$_GET["tp"];
    
    $utils = new Utils();
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. 'Bad token'. $tok_code);
        die();
    }
    
    $s3client = S3Client::factory(array(
            'version' => S3_VERSION,
            'key'    => S3_KEY,
            'secret' => S3_SECRET,
            'region' => S3_REGION
    ));
    
    //error_log(print_r($_FILES, TRUE));
    if ($_FILES["fileToUpload"]["error"] != 0){
        echo json_encode(array('errno' => 'file_105', 'msg' => 'File upload failed error='.print_r($_FILES["fileToUpload"]["error"], TRUE)));
        error_log($uuid.' File upload failed '.print_r($_FILES["fileToUpload"], TRUE));
        die();
    }
    
    if ($_FILES["fileToUpload"]["type"] != 'application/octet-stream'){
        echo json_encode(array('errno' => 'file_106', 'msg' => 'Bad format '.$_FILES["fileToUpload"]["type"] ));
        error_log($uuid.' - bad format '.print_r($_FILES["fileToUpload"], TRUE));
        die();
    }
    
    if ($_FILES["fileToUpload"]["size"] > 10000000) {
        echo json_encode(array('errno' => 'file_102', 'msg' => 'Video File is too large'));
        error_log('Video File is too Large');
        die();
    }
    //error_log("Video Time from device=".$time.", Motion Timezone=".$timezone);
    //Video Time from device=1535008119, Motion Timezone=Asia/Calcutta
    $datetime = new DateTime();
    $datetime->setTimestamp($time);
    $datetime->setTimezone( new DateTimeZone($timezone));
    $target_file=$uuid."/".$datetime->format('Y/m/d/H_i_s')."/".Utils::randomString(6).".mp4";
    error_log("Target file=".$target_file);
    
    try {
        $upload = $s3client->upload($bucket, $target_file, fopen($_FILES['fileToUpload']['tmp_name'], 'rb'),
                'private', array('params' => array('ContentType' => 'video/mp4')));
        echo json_encode(array('success' => '0'));
        if ($upload) {
            $utils->updateLastAlert($uuid, $target_file);
            $timestamp = time();
            if (!isset($_GET["tz"])){
                //$cw->publishMetrics($uuid, 'Motion', 1, 'None', time());
                $utils->publishMotion($uuid, $timestamp, $target_file);
            }
            else {
                $utils->publishMotion($uuid, $datetime, $target_file);
            }
        }   
    }
    catch (Exception $e){
        echo json_encode(array('errno' => 'file_105', 'msg' => 'Exception uploading your file'.$e->getMessage()));
        error_log('Exception uploading your file'.$e->getMessage());
    }
}
else {
    echo json_encode(array('errno' => 'param_101', 'msg' => 'Param error'));
    //error_log('Param error'.print_r($_POST, true));
}

?>

