<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once (__ROOT__ . '/classes/SmsLog.php');

if (isset($_GET['number'])) {
    $log = new SmsLog();
    $count=$log->getSentCountSinceMidnight($_GET['number']);
    error_log("Sms Count=".$count);
    echo $count;
} else {
    echo "Incorrect Params";
}
?>

