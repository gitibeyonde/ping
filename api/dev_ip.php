<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');

if (isset($_GET['uuid'])){
    $uuid = $_GET['uuid'];
    $sensor_uuid=$_GET['sensor-uuid'];
    $req_ip = $_SERVER['REMOTE_ADDR'];

     $link = mysql_connect(DB_HOST,DB_USER,DB_PASS);
     if (!$link) {
        die('Not connected : ' . mysql_error());
     }           
                     
     $db_selected = mysql_select_db(DB_NAME, $link);
     if (!$db_selected) {
            die ('Can\'t use foo : ' . mysql_error());
     }           

    $sql="select uuid, sensor_ipv4 from sensor_map where uuid='$sensor_uuid'";

    $result = mysql_query($sql, $conn);

    if ($result) {
        $row= mysql_fetch_assoc($result);
        echo json_encode($row);
    } else {
            echo "0";
    }

    mysql_close($conn);
}
?>

