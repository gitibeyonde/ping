<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/classes/AwsSqs.php');

if (isset($_GET['p'])){
    $phone=$_GET['p'];
    //^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$
    if(preg_match("/^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/", $phone)) {
        $otp = mt_rand(100000, 999999);
        $aws = new AwsSqs();
        echo $otp;
        $aws->sendOtp("919701199011", "The otp is $otp", "test");
    }
    else {
        echo "ERROR: Bad number".$phone;
    }}
?>

