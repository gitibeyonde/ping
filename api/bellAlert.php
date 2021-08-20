<?php

define ( '__ROOT__',  dirname ( __FILE__ ));

require_once(__ROOT__.'/classes/AlertRaised.php');
require_once(__ROOT__.'/classes/Utils.php');


if (isset($_GET["hn"])){
    if (isset($_POST["t"])){
        $token=urldecode($_POST["t"]);
    }
    else if (isset($_SERVER["HTTP_TOKEN"])){
        $token=$_SERVER["HTTP_TOKEN"];
    }
    else {
        echo json_encode(array('errno' => 'param_102', 'msg' => 'Token error'));
        die;
    }

    $uuid=urldecode($_GET["hn"]);
    $utils = new Utils();
    $tok_code=$utils->checkToken($token, $uuid);
    if ( $tok_code != 0){//check token
        echo json_encode(array('errno' => 'token_'.$tok_code, 'msg' => 'Bad token'));
        error_log($uuid. ' Bad token ' . $tok_code . ' bad token is '.$token);
        die();
    }

$ar = new AlertRaised();

$ar->notifyBellRing($uuid, new DateTime('now'));
}
else {
    echo json_encode(array('errno' => 'param_101', 'msg' => 'Param error'));
}
?>

