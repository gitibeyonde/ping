<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once (__ROOT__ . '/classes/Utils.php');
require_once (__ROOT__ . '/classes/Registration.php');

error_log(print_r($_POST, true));
error_log(print_r($_GET, true));

// Array\n(\n    [uid] => gprsnn\n    [email] => agneya2001@iitbombay.org\n    [my] => +917288801232\n    [t] => bKt6r4cdXS\n    [thr] => +919701199011\n)

if (isset($_GET["uuid"])  &&  isset($_POST['t']) && isset($_POST['my']) && isset($_POST['thr']) && isset($_POST['uid']) && isset($_POST['email'])) {
    $uuid=urldecode($_GET["uuid"]);
    $token=urldecode($_POST["t"]);
    
    $utils = new Utils();
    
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. 'Bad token'. $tok_code);
        return "-1";
    }
    else { 
        $my_number = $_POST['my'];
        $user_phone = $_POST['thr'];
        $user_name = $_POST['uid'];
        $user_email = $_POST['email'];
        
        error_log("Validating $user_phone $my_number $user_name $user_email " );
        $reg= new Registration();
        echo $reg->validatePhoneNumber($user_name, $user_phone, $user_email, $my_number);
    }
    
}
?>

