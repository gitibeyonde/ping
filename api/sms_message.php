<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/classes/SmsJob.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/classes/SmsUtils.php');
require_once(__ROOT__.'/classes/SmsMessage.php');
require_once(__ROOT__.'/classes/GsmDevice.php');

if (isset($_GET['phone']) && isset($_GET['id'])){
    $map=array();
    foreach (array_keys($_GET) as $gk){
        if ($gk == "id") {
            $message_id = $_GET[$gk];
        }
        else if ($gk == 'phone'){
            $phone = $_GET[$gk];
        }
        else{
            $map[$gk] = $_GET[$gk];
        }
    }
    
    foreach (array_keys($_POST) as $gk){
        if ($gk == "id") {
            $message_id = $_POST[$gk];
        }
        else if ($gk == 'phone'){
            $phone = $_POST[$gk];
        }
        else{
            $map[$gk] = $_POST[$gk];
        }
    }
    
    $host_key = $map['host_key'];
    
    $smsutils = new SmsUtils();
    list($uid, $exp) = $smsutils->checkHostKey($host_key);
    if ($exp < date(DateTime::ATOM)){
        echo "ERROR: your host key has expired, generate a new one GOTO <a href='https://simonline.in/index.php?view=sms_account'>Account->Security</a>\n";
    }
    
    $phone = $smsutils->normalizePhone($phone);
    
    $otp6 = $map['otp'] = mt_rand(100000, 999999);
    
    $smsmessage = new SmsMessage();
    error_log("Userid=" . $uid."Message id=".$message_id);
    $message = $smsmessage->getMessage($uid, $message_id);
    error_log("Message=".print_r($message, true));
    $ts =  $message['template'];
    error_log("Template=".print_r($ts, true)." to ".$phone);
    
    $sms = $smsutils->templateReplace($ts,$map);
    //^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$
    if(preg_match("/^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/", $phone)) {
        $gsm = new GsmDevice();
        $gsmdev = $gsm->getOneGsmDevice($uid);
        if ($gsmdev['uuid'] == null){
            echo "ERROR: You need a phone number in your account to deliver these messages, add one !</a>\n";
        }
        $smsjob = new SmsJob(); //($uuid, $user_id, $type, $tid, $my_number, $there_number, $sms, $ts)
        $smsjob->addJob($gsmdev['uuid'], $uid, "trig", $message_id, $smsutils->normalizePhone($gsmdev['my_number']), $phone, $sms);
        if ($smsutils->isOtpRequired($ts)){
            echo $otp6;
        }
        else {
            echo $phone.":".$sms;
        }
    }
    else {
        echo "ERROR: trig Bad number".$phone;
    }
}
else if  (isset($_GET['audience']) && isset($_GET['id'])){
    foreach (array_keys($_GET) as $gk){
        if ($gk == "audience") {
            $audience_id = $_GET[$gk];
        }
        else if ($gk == "id") {
            $message_id = $_GET[$gk];
        }
        else if ($gk == "host_key") {
            $host_key = $_GET[$gk];
        }
    }
    
    foreach (array_keys($_POST) as $gk){
        if ($gk == "audience") {
            $audience_id = $_POST[$gk];
        }
        else if ($gk == "id") {
            $message_id = $_GET[$gk];
        }
        else if ($gk == "host_key") {
            $host_key = $_GET[$gk];
        }
    }
    
    
    $smsutils = new SmsUtils();
    list($uid, $exp) = $smsutils->checkHostKey($host_key);
    if ($exp < date(DateTime::ATOM)){
        echo "ERROR: your host key has expired, generate a new one GOTO <a href='https://simonline.in/index.php?view=sms_account'>Account->Security</a>\n";
    }
    
    $smsmessage = new SmsMessage();
    error_log("Userid=" . $uid."Message id=".$message_id);
    $message = $smsmessage->getMessage($uid, $message_id);
    error_log("Message=".print_r($message, true));
    $ts =  $message['template'];
    error_log("Template=".print_r($ts, true));
    $audience = $smsutils->getAudience($uid, $audience_id);
    
    foreach ($audience as $person){
        $otp6 = mt_rand(100000, 999999);
        $per = json_decode($person['data'], true);
        error_log("Person=".print_r($per, true));
        $per['otp'] = $otp6;
        $sms = $smsutils->templateReplace($ts, $per);
        $phone = $smsutils->normalizePhone($person['number']);
        error_log("Phone=".$phone);
        if(preg_match("/^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/", $phone)) {
            
            $gsm = new GsmDevice();
            $gsmdev = $gsm->getOneGsmDevice($uid);
            if ($gsmdev['uuid'] == null){
                echo "ERROR: You need a phone number in your account to deliver these messages, add one !</a>\n";
            }
            $smsjob = new SmsJob(); //($uuid, $user_id, $type, $tid, $my_number, $there_number, $sms, $ts)
            $smsjob->addJob($gsmdev['uuid'], $uid, "surv", $message_id, $smsutils->normalizePhone($gsmdev['my_number']), $phone, $sms);
            if ($smsutils->isOtpRequired($sms)){
                echo $phone.":".$otp6."\n";
            }
            else {
                echo  $phone.":".$sms."\n";
            }
        }
        else {
            echo "ERROR: Bad number".$person['number']."Failed ".$sms."\n";
        }
    }
}
else if  (isset($_GET['template']) && isset($_GET['phone'])){
    $host_key = $_GET['host_key'];
    $phone = $_GET['phone'];
    
    $smsutils = new SmsUtils();
    list($uid, $exp) = $smsutils->checkHostKey($host_key);
    if ($exp < date(DateTime::ATOM)){
        echo "ERROR: your host key has expired, generate a new one GOTO <a href='https://simonline.in/index.php?view=sms_account'>Account->Security</a>\n";
    }
    
    $sms = $_GET['template'];
    //^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$
    error_log("Phone=".$phone);
    if(preg_match("/^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/", $phone)) {
        $gsm = new GsmDevice();
        $gsmdev = $gsm->getOneGsmDevice($uid);
        if ($gsmdev['uuid'] == null){
            echo "ERROR: You need a phone number in your account to deliver these messages, add one !</a>\n";
        }
        $smsjob = new SmsJob(); //($uuid, $user_id, $type, $tid, $my_number, $there_number, $sms, $ts)
        $smsjob->addJob($gsmdev['uuid'], $uid, "trig", 0, $smsutils->normalizePhone($gsmdev['my_number']), $smsutils->normalizePhone($phone), $sms);
        echo $phone.":".$sms;
    }
    else {
        echo "ERROR: send API Bad number".$phone;
    }
}


?>

