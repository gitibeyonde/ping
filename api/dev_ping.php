<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
if (isset($_GET['uuid'])){
    $uuid = $_GET['uuid'];
    $tz = $_GET['tz'];
    $ip = 'none';
    $mjpgport = '0';
    if (isset($_GET['ip'])){
        $ip = $_GET['ip'];
    }
    if (isset($_GET['mjpgport'])){
        $mjpgport = $_GET['mjpgport'];
    }
    $cam_ip = $_SERVER['REMOTE_ADDR'];

    if ($tz == ''){
        die("The timezone value is blank, cam ip is ".$cam_ip);
    }
    try {
        $db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
        date_default_timezone_set($tz);
        $now = date('Y-m-d H:i:s');
        $sql="update device set deviceip='".$ip."', port='".$mjpgport."', timezone='".$tz."', updated='".$now."', visibleip='".$cam_ip."' where uuid='". $uuid."'";
        echo "ts=".$now;
        $db_connection->exec($sql);
    }
    catch( Exception $e )
    {
        die('Sql error '.$e->getMessage());
    }
}
    
?>

