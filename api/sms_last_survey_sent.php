<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once (__ROOT__ . '/classes/SmsLog.php');

if (isset($_GET['my_number']) && isset($_GET['there_number'])) {
    $log = new SmsLog();
    error_log("Last Sms Survey sent time=".$log->getLastSurveySent($_GET['my_number'], $_GET['there_number']));
    echo $log->getLastSurveySent($_GET['my_number'], $_GET['there_number']);
} else {
    echo "Incorrect Params";
}
?>

