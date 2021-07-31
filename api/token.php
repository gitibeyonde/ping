<?php 
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/libraries/password_compatibility_library.php');

if (isset($_POST['u']) && isset($_POST['p']) && isset($_GET['hn'])){

    $username=urldecode($_POST["u"]);
    $password=base64_decode(urldecode($_POST["p"]));
    $uuid=urldecode($_GET["hn"]);
    
    $utils = new Utils();
    if ( !$utils->autheticate($username, $password)){
        echo json_encode(array('errno' => 'sql_402', 'msg' => 'User does not exist or bad password'));
        error_log('User does not exist or bad password');
        die();
    }

    echo $utils->token($uuid);
}
else {
    echo json_encode(array('errno' => 'param_402', 'msg' => 'Bad Params'));
}
    
?>