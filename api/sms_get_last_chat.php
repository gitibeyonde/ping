<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once (__ROOT__ . '/classes/SmsLog.php');

if (isset($_GET['my_number']) && isset($_GET['there_number']) && isset($_GET['host_key'])) {
    $log = new SmsLog();
    //error_log($_GET['my_number'].$_GET['there_number']."Last Sms entry=".$log->getResponse(trim($_GET['my_number']), trim($_GET['there_number']), strtotime("-1 month")));
    echo json_encode($log->getResponse(trim($_GET['my_number']), trim($_GET['there_number']), strtotime("-1 week")));
} else {
    echo "Incorrect Params";
}
?>

