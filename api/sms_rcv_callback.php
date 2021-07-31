<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/FindTypes.php');


//Array\n(\n    [t] => ITQT8irirp\n    [uid] => 95\n    [my] => +918297150231\n    [thr] => 09701199011\n    [d] => 0\n    [typ] => trig\n    [tid] => 1\n    
//[sms] => Dear abhi, 991209 is your one time password (OTP). Please enter the OTP to proceed. Thank you, simonlin\n)\n
// Array\n(\n    [uuid] => 1ffea827\n)\n

$drname = array("Dr Nageshwar Reddy", "Dr. G. V. Rao", "Dr. Vishwanath Gella", "Dr. Rajendra Prasad", "Dr. Rupa Banerjee", "Dr. Sundeep Lakhtakia", "Dr. C. Sukesh Kumar Reddy",
        "Dr. P Nagaraja Rao", "Dr. Rupjyoti Talukdar", "Dr. Mohan Ramchandani", "Dr. Rakesh Kalapala", "Dr. Mithun Sharma"
);


if (isset($_GET["sms"]) && isset($_POST["my"]) && isset($_POST["thr"]) && isset($_POST["ctx"])){
    $my_number = $_POST['my'];
    $user_phone = $_POST['thr'];
    $sms = $_POST['sms'];
    $ctx = $_POST['email'];
    
    $sms = preg_replace('/\s+/', ' ', $sms);
    $sms = strtolower(preg_replace("/(?![.=$'€%-])\p{P}/u", "", $sms));
    
    if ($ctx == "illness_general"){
        //If you already know which doctor to look for an appointment then write [Dr name, date and time of appointment] or else [list doc categories]
        $date = FindTypes::find_date($sms);
        $name = FindTypes::find_match($sms, $drname);
    }
    else if ($ctx == "horoscope") {
        //To get your weekly horoscope write [Future this week, date of birth]
        
    }
    else {
        
    }
}

?>

