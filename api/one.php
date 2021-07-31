<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/classes/OneDo.php');

if (isset($_GET['id'])){
    $id=$_GET['id'];
    error_log("Logging access one id=".$id);
    if ($id=="favicon.ico") {
        die;
    }
    
    $od = new OneDo();
    $map =  $od->getUrl($id);
    $ip = getenv('HTTP_CLIENT_IP')?:
    getenv('HTTP_X_FORWARDED_FOR')?:
    getenv('HTTP_X_FORWARDED')?:
    getenv('HTTP_FORWARDED_FOR')?:
    getenv('HTTP_FORWARDED')?:
    getenv('REMOTE_ADDR');
    error_log("Logging access one ".$ip);
    $ag = $_SERVER['HTTP_USER_AGENT'];
    $od->logAccess($id, $ip);
    if ($map==null){
        header("Location: /api/removed.html", true, 301);
    }
    else {
        header("Location: ". $map, true, 301);
    }
}
else {
    echo "http://1do.in/api/error.html";
}
?>

