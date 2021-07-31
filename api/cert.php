<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once (__ROOT__ . '/classes/DeviceCert.php');

if (isset($_POST['u']) && isset($_POST['p']) && isset($_GET["hn"])){
    $username=urldecode($_POST["u"]);
    $password=base64_decode(urldecode($_POST["p"]));
    $uuid=urldecode($_GET["hn"]);
    $dcert = new DeviceCert ();
    $device_cert = $dcert->loadDeviceCert ( $uuid );
    if (! $device_cert) {
        $passphrase = utils::randomString ( 8 );
        $cert = $utils->generateKeyPair ( $uuid, $user_name, $user_email, $passphrase );
        $device_cert = $dcert->saveDeviceCert ( $uuid, $cert ['public'], $cert ['private'], $cert ['passphrase'] );
    }
    echo $device_cert->public;
}
else {
    echo json_encode(array('errno' => 'param_402', 'msg' => 'Bad Params'));
}
?>
