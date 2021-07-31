<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/Aws.php');
require_once(__ROOT__.'/classes/Device.php');
require_once(__ROOT__.'/libraries/password_compatibility_library.php');


if (isset ( $_GET ['uuid'] )) {
    $uuid = $_GET ['uuid'];
    $user = urldecode($_POST ['u']);
    $pass = base64_decode ( urldecode($_POST['p'] ));
    
    try {
       $utils = new Utils(); 
       if ( !$utils->autheticate($user, $pass)){
           echo json_encode(array('errno' => 'sql_403', 'msg' => 'Bad password'));
       }
       else {
           $aws = new Aws();
           $aws->deleteMotionData ( $uuid );
           $d = new Device();
           $d->deleteDevice($uuid);
           echo json_encode(array('success' => 'device un-registered'));
       }
    }
    catch( Exception $e )
    {
       error_log(print_r($e, true));
       echo json_encode(array('errno' => 'sql_400', 'msg' => 'Exception '.$e->getMessage()));
    }
}
else {
    echo json_encode(array('errno' => 'param_100', 'msg' => 'Bad params'));
}
        

?>

