<?php

    define('__ROOT__', dirname(dirname(__FILE__)));
    require_once(__ROOT__.'/config/config.php');
    require_once(__ROOT__.'/classes/Utils.php');
    require_once(__ROOT__.'/libraries/password_compatibility_library.php');

    if (isset($_GET['uuid'])){
        $uuid = $_GET['uuid'];
        $timezone = urldecode($_GET['tz']);
        $user = urldecode($_POST['u']);
        $pass = base64_decode(urldecode($_POST['p']));
        $device_name = $_GET['name'];
        $capabilities = urldecode($_GET['cap']);
        $version = urldecode($_GET['v']);
        $mac = urldecode($_GET['mac']);
        $ip = urldecode($_GET['ip']);
        $remoteip = urldecode($_SERVER['REMOTE_ADDR']);

        error_log("Cap=".$capabilities);
        if (strlen($capabilities) < 1 || strlen($version) < 1){
            echo json_encode(array('errno' => 'sql_401', 'msg' => 'Bad request capabilities or version not specified'));
        }
        else {
            try {
                $utils = new Utils();
                
                if ( !$utils->autheticate($user, $pass)){
                    echo json_encode(array('errno' => 'sql_403', 'msg' => 'Bad password'));
                }
                else {
                    $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                    $sql="insert into device (uuid, user_name, device_name, box_name, timezone, capabilities, version, setting, nat, deviceip, visibleip, created, updated) ".
                            "values ( '$uuid', '$user', '$device_name', 'default', '$timezone', '$capabilities', '$version', '', 0, '$ip', '$remoteip', now(), now()) ".
                            "on duplicate key update user_name=VALUES(user_name), box_name=VALUES(box_name), capabilities=VALUES(capabilities), timezone=VALUES(timezone), version=VALUES(version), setting=VALUES(setting), deviceip=VALUES(deviceip), visibleip=VALUES(visibleip);";
                    $db_connection->exec($sql);
                    echo json_encode(array('success' => 'device registered or device parameters updated'));
                    $utils->token($uuid);
                    //if (strpos($capabilities, 'SIP') !== false){
                    //    $utils->registerSipDevice($uuid, $user, $pass);
                    //}
                }
            }
           catch( Exception $e )
           {
                error_log(print_r($e, true));
                echo json_encode(array('errno' => 'sql_400', 'msg' => 'Exception '.$e->getMessage()));
            }
        }
   }

?>

