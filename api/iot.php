<?php

define ( '__ROOT__', dirname ( dirname ( __FILE__ ) ) );

//Installed on app.ibeyonde.com

if (version_compare(PHP_VERSION, '5.3.7', '<')) {
    exit('Sorry, this script does not run on a PHP version smaller than 5.3.7 !');
} else if (version_compare(PHP_VERSION, '5.5.0', '<')) {
    require_once(__ROOT__.'/libraries/password_compatibility_library.php');
}
require_once(__ROOT__.'/classes/Registration.php');
require_once(__ROOT__ .'/config/config.php');
require_once(__ROOT__.'/translations/en.php');
require_once(__ROOT__ .'/classes/Registration.php');
require_once(__ROOT__ .'/classes/Device.php');
require_once(__ROOT__ .'/classes/Aws.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/UserFactory.php');
require_once(__ROOT__.'/classes/User.php');
require_once(__ROOT__.'/classes/RegistryPort.php');
require_once(__ROOT__.'/classes/AlertRaised.php');
require_once(__ROOT__.'/classes/DeviceToken.php');
require_once(__ROOT__.'/classes/Mjpeg.php');


function getSSLPage($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_URL, $url);
    #curl_setopt($ch, CURLOPT_SSLVERSION, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

if (!isset($_SERVER['PHP_AUTH_USER'])) {
    if (isset($_GET['view'])){
        switch ($_GET['view']) {
            case 'register':
                $user_name = $_POST['user_name'];
                $user_email = $_POST['user_email'];
                $user_phone = $_POST['user_phone'];
                $user_password = $_POST['user_password_new'];
                $user_password_repeat = $_POST['user_password_repeat'];
                $registration = new Registration();
                $registration->registerNewUserFromApp($user_name, $user_email, $user_phone, $user_password, $user_password_repeat);
                if (count($registration->errors) > 0){
                    echo json_encode(array('code' => 405, 'message' => $registration->errors[0]));
                }
                else {
                    echo json_encode(array('code' => 205, 'message' => 'Registration Successful'));
                }
                break;
            default:
                echo json_encode(array('code' => 402, 'message' => 'Unrecognized unauthenticated command'));
        }
    }
    //echo json_encode(array('code' => 101, 'message' => 'UnAuthorized, for more info contact Administrator at info@ibeyonde.com'));
    exit;
} else {
    $utils = new Utils();
    $user_name=$_SERVER['PHP_AUTH_USER'];
    $result = $utils->autheticate($user_name, $_SERVER['PHP_AUTH_PW']);
    if ($result){
        if (isset($_GET['view'])){
            switch ($_GET['view']) {
                case 'login':
                    echo json_encode(array('code' => 200, 'message' => 'Success'));
                    break;
                case 'logout':
                    echo json_encode(array('code' => 200, 'message' => 'Success'));
                    break;
                case 'devicelist':
                    echo json_encode( (array)Device::loadUserDevices($user_name));
                    break;
                case 'devicedetails':
                    if (isset($_GET['uuid'])){
                        $dev = new Device();
                        echo json_encode( (array)$dev->loadDevice($_GET['uuid']));
                    }
                    break;
                case 'deviceip':
                	if (isset($_GET['uuid'])){
                        $dev = new Device();
                        echo json_encode(array($dev->getDeviceIP($_GET['uuid'])));
                    }
                    break;
                case 'lastalert':
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        
                        if (isset($_GET['type'])) {
                            $type=$_GET['type'];
                            
                            $ar = new AlertRaised();
                            if ($type == 'fd'){
                                // return face detected
                                $fo = $face->getFaces($user_name, AlertRaised::FACE_DETECTED, 1);
                                error_log("Last face detected $fo");
                            }
                            else if ($type == 'fr') {
                                // return face recognized
                                $fo = $face->getFaces($user_name, AlertRaised::FACE_RECOGNIZED, 1);
                                error_log("Last face recognized $fo");
                            }
                            else if ($type == 'gd') {
                                // return face recognized
                                $fo = $face->getFaces($user_name, AlertRaised::GRID_DETECTED, 1);
                                error_log("Last grid detected $fo");
                            }
                            else {
                                echo json_encode(array('code' => 434, 'message' => 'Alert type not found'));
                            }
                        }
                        else { // if type not set send last motion alert
                            $client = new Aws ();
                            $dev = new Device();
                            $device = $dev->loadDevice ( $uuid );
                            $today=Utils::dateNow($device->timezone);
                            list($furl, $datetime)= $client->latestMotionDataUrl($_GET['uuid'], $today);
                            if ($datetime == ""){
                                echo json_encode(array('code' => 431, 'message' => 'No alerts found for this device'));
                            }
                            echo json_encode(array($furl, $datetime));
                        }
                    }
                    else {
                        echo json_encode(array('code' => 433, 'message' => 'Device uuid not set'));
                    }
                    break;
                case 'lastalerts':
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        
                        
                        if (isset($_GET['type'])) {
                            $type=$_GET['type'];
                            
                            $ar = new AlertRaised();
                            if ($type == 'fd'){
                                // return face detected
                                $fo = $face->getFaces($user_name, AlertRaised::FACE_DETECTED, 10);
                                error_log("Last face detected $fo");
                            }
                            else if ($type == 'fr') {
                                // return face recognized
                                $fo = $face->getFaces($user_name, AlertRaised::FACE_RECOGNIZED, 10);
                                error_log("Last face recognized $fo");
                            }
                            else if ($type == 'gd') {
                                // return face recognized
                                $fo = $face->getFaces($user_name, AlertRaised::GRID_DETECTED, 10);
                                error_log("Last grid detected $fo");
                            }
                            else {
                                echo json_encode(array('code' => 434, 'message' => 'Alert type not found'));
                            }
                        }
                        else { // if type not set send last motion alert
                            
                            $client = new Aws ();
                            $ivs = $client->latestMotionDataUrls($uuid);
                            if (count($ivs) == 0){
                                echo json_encode(array('code' => 431, 'message' => 'No alerts found for this device'));
                            }
                            else {
                                $motion_array = array();
                                foreach (array_reverse($ivs) as $iv) {
                                    $motion_array[] = $iv;
                                }
                                echo json_encode($motion_array);
                            }
                        }
                    }
                    else {
                        echo json_encode(array('code' => 433, 'message' => 'Device uuid not set'));
                    }
                    break;
                case 'history':
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        $client = new Aws ();
                        $dev = new Device();
                        $device = $dev->loadDevice ( $uuid );
                        if ($device == null){
                            echo json_encode(array('code' => 441, 'message' => 'Device not found for this account'));
                            break;
                        }
                        $date = '';
                        $today = Utils::dateNow ( $device->timezone );
                        if (isset ( $_GET ["date"] )) {
                            $date = $_GET ["date"];
                        } else {
                            $date = $today;
                        }
                        $motions = null;
                        if (isset ( $_GET ["hour"] )) {
                            $hour = $_GET ["hour"];
                            $motions = $client->loadTimeMotionData ( $uuid, $date, $hour );
                        } else {
                            $motions = $client->loadMotionData ( $uuid, $date );
                        }
                        $history = array();
                        foreach ( $motions as $motion ) {
                            $furl = $client->getSignedFileUrl ( $motion->image );
                            $history[]=array('datetime'=>$motion->datetime, 'url'=> $furl);
                        }
                        echo json_encode($history);
                    }
                    break;
                case 'live':
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        $quality=$_GET['quality'];
                        $dev = new Device();
                        $device =  $dev->loadDevice ( $uuid );
                        if ($device == null){
                            echo json_encode(array('code' => 441, 'message' => 'Device not found for this account'));
                            break;
                        }
                        $user = new User($user_name);
                        $remoteip = urldecode($_SERVER['REMOTE_ADDR']); 
                        
                        $url = null;
                        $pr = new RegistryPort();
                        list($ip, $port) = $pr->getIpAndPort($uuid);  // BINI, TINI, SINI, MINI, HINI
                        
                        $url = "https://".$ip."/udp/live_n.php?timezone=".$device->timezone."&user_name=".$user_name."&quality=".$quality."&user_id=".
                         $user->user_id."&uuid=".$uuid."&port=".$port."&sid=".mt_rand().
                        "&tk=".$device->token."&rand=".mt_rand();
                        error_log("Live url = ". $url);
                        echo json_encode($url);
                    }
                    break;
                case 'liveclose':
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        $stream_id=$_GET['sid'];
                        
                        $mjpeg = new Mjpeg();
                        $mjpeg->releaseOnership($uuid, $stream_id);
                        echo json_encode(array('code' => 200, 'message' => 'Success'));
                    }
                    break;
                case 'mp4stream' :
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        $quality="VK";
                        if (isset($_GET['quality'])){
                            $quality=$_GET['quality'];
                        }
                        $dev = new Device();
                        $device =  $dev->loadDevice ( $uuid );
                        if ($device == null){
                            echo json_encode(array('code' => 441, 'message' => 'Device not found for this account'));
                            break;
                        }
                        $user = new User($user_name);
                        //$remoteip = urldecode($_SERVER['REMOTE_ADDR']);
                        $stream_id = mt_rand();
                        
                        $pr = new RegistryPort();
                        list($ip, $port) = $pr->getIpAndPort($uuid); //"VK";
                        $url = "https://".$ip."/udp/live_v.php?timezone=".$device->timezone."&user_name=".$user_name."&quality=".$quality."&user_id=".$user->user_id."&uuid=".$uuid.
                            "&port=".$port."&sid=".$stream_id."&tk=".$device->token;
                        $res = getSSLPage($url);
                        echo json_encode(array('code' => 208, 'message' => 'Mp4 streaming completed'.$res));}
                    else {
                        echo json_encode(array('code' => 404, 'message' => 'Missing uuid'));
                    }
                    break;
                case 'mp4index' :
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        $indexes = getSSLPage("https://udp1.ibeyonde.com/udp/video_next.php?uuid=". $uuid);
                        //error_log($uuid. " Indexes " .$indexes);
                        echo json_encode(array( 'index' => $indexes));
                    }
                    else {
                        echo json_encode(array('code' => 404, 'message' => 'Missing uuid'));
                    }
                    break;
                case 'snap':
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        $quality=$_GET['quality'];// bad, low, medium, high, super
                        $client = new Aws ();
                        
                        $client->sendActionBroker ( $uuid, "Snap", $quality );
                        echo json_encode(array('code' => 206, 'message' => 'Snap action sent'));
                    }
                    else {
                        echo json_encode(array('code' => 404, 'message' => 'Missing uuid'));
                    }
                    break;
                case 'sip':
                    if (isset($_GET['uuid'])){
                        $uuid=$_GET['uuid'];
                        $client = new Aws ();
                        
                        echo json_encode(array('code' => 206, 'message' => 'Snap action sent'));
                    }
                    else {
                        echo json_encode(array('code' => 404, 'message' => 'Missing uuid'));
                    }
                    break;
                case 'token':
                    if (isset($_GET['username']) && isset($_GET['token']) && isset($_GET['phone_id'])){
                        $username=$_GET['username'];
                        $phone_id=$_GET['phone_id']; # phone's device id
                        $token=$_GET['token'];
                        $system=$_GET['system'];
                        $system_type=$_GET['system_type'];
                        $language=$_GET['language'];
                        $country=$_GET['country'];
                        
                        if (trim($phone_id) == "") {
                            error_log("add device token Device Token blank phone id");
                            echo json_encode(array('code' => 207, 'message' => 'Device Token blank phone id'));
                            break;
                        }
                        
                        $dt = new DeviceToken();
                        $result = $dt->applyDeviceTokenForUser($username, $phone_id, $token, $system, $system_type, $language, $country);
                        if ($result == ""){
                            echo json_encode(array('code' => 206, 'message' => 'Device Token saved'));
                        }
                        else {
                            echo json_encode(array('code' => 412, 'message' => $result));
                        }
                    }
                    else {
                        echo json_encode(array('code' => 404, 'message' => 'Missing parameter'));
                    }
                    break;
                default:
                    echo json_encode(array('code' => 402, 'message' => 'Unrecognized command'));
            }
        }
        else {
            echo json_encode(array('code' => 411, 'message' => 'Command not passed'));
        } 
    }
    else {
        echo json_encode(array('code' => 403, 'message' => 'UnAuthorized, for more info contact Administrator at info@ibeyonde.com'));
    }
}
?>