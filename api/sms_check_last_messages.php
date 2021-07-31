<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once (__ROOT__ . '/classes/SmsLog.php');

if (isset($_GET['my_number']) && isset($_GET['there_number'])) {
    $log = new SmsLog();
    echo $log->respondToMessages($_GET['my_number'], $_GET['there_number']);
} else {
    echo "Incorrect Params";
}
?>

