<?php
// include the config
require_once (__ROOT__ . '/config/config.php');
require_once (__ROOT__ . '/libraries/password_compatibility_library.php');
class Utils {
    public static $dt;
    private $db_connection = null;
    
    // GENERAL IOT
    public $restart = array('Restart', 'Restart', '', 'You are about to restart this device. This will take the device offline for few minutes.');
    public $remove = array('Remove', 'Remove', '', 'You are about to remove this device. This will remove the device from your account and delete all the data. This will take upto few minutes.');
    public $reset = array('Reset', 'Reset', '', 'You are about to reset this device. This will take the device offline for few minutes.');
    public $update = array('Update', 'Update', '', 'You are about to update this device. This will take the device offline for few minutes.');
    public $settings = array('Settings', 'Settings', '', 'Settings will take few minutes time to sync.');
    //CAMERA
    public $rotate = array('Rote', 'Rotate 90deg Clockwise', '', 'None');
    public $hflip = array('Hflip', 'Camera Horizontal Flip', '', 'None');
    public $vflip = array('Vflip', 'Camera Vertical Flip', '', 'None');
    public $incbrt = array('InrBrt', 'Increase Camera Brightness', '', 'None');
    public $decbrt = array('DecBrt', 'Decrease Camera Brightness', '', 'None');
    //MOTION
    public $incmotionq = array('IncMotionQ', 'Increase Motion Capture Quality', '', 'None');
    public $decmotionq = array('DecMotionQ', 'Decrease Motion Capture Quality', '', 'None');
    public $incfacemin = array('IncFaceMin', 'Increase minimum face size detected', '', 'None');
    public $decfacemin = array('DecFaceMin', 'Decrease minimum face size detected', '', 'None');
    public $enfcdtct = array('EnFcDtct', 'Enable Face Detection', '', 'None');
    public $dsfcdtct = array('DsFcDtct', 'Disable Face Detection', '', 'None');
    public $inctol = array('IncTol', 'Decrease Motion Sensitivity', '', 'None');
    public $dectol = array('DecTol', 'Increase Motion Sensitivity', '', 'None');
    public $incmdelta = array('IncMDelta', 'Increase Motion Capture Interval', '', 'None');
    public $decmdelta = array('DecMDelta', 'Decrease Motion Capture Interval', '', 'None');
    public $engrd = array('EnGrd', 'Enable Grid Sensitivity', '', 'None');
    public $dsgrd = array('DsGrd', 'Disable Grid Sensitivity', '', 'None');
    //MISC
    public $recordtoggle = array('RecordToggle', 'Start/Stop Recording from current location', '', 'None');
    public $snap = array('Snap', 'Snap', '', 'None');
    public $buzz = array('Buzz', 'Buzz', '', 'None');
    //SNAPSHOTS
    public $dishrsnap = array('DsbHrSnap', 'Disable Hourly Snapshot', '', 'None');
    public $enbhrsnap = array('EnbHrSnap', 'Enable Hourly Snapshot', '', 'None');
    public $incsnapq = array('IncSnapQ', 'Increase Snap Capture Quality', '', 'None');
    public $decsnapq = array('DecSnapQ', 'Decrease Snap Capture Quality', '', 'None');
    
    public function __construct() {
    }
    private function databaseConnection() {
        // if connection already exists
        if ($this->db_connection != null) {
            return true;
        } else {
            try {
                $this->db_connection = new PDO ( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS );
                return true;
            } catch ( PDOException $e ) {
                $this->errors [] = MESSAGE_DATABASE_ERROR . $e->getMessage ();
            }
        }
        return false;
    }
    public function autheticate($user_name, $password64) {
        // if database connection opened
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ('SELECT user_password_hash FROM users WHERE user_name = :user_name' );
            $sth->bindValue ( ':user_name', $user_name, PDO::PARAM_STR );
            $sth->execute ();
            $dbpass = $sth->fetch () [0];
            return password_verify ( $password64, $dbpass );
        } else {
            return false;
        }
    }
    public function token($uuid) {
        // if database connection opened
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ('SELECT token, expiry FROM device WHERE uuid = :uuid' );
            $sth->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $sth->execute ();
            $val = $sth->fetch ();
            //error_log("Value returned ".print_r($val, True));
            if ($val[0] == null){
                $token = $this->randomString();
                $expiry = time() + 60*60*240; // after 10 days
                $sth = $this->db_connection->prepare ('UPDATE device SET token=:token, expiry=:expiry where uuid=:uuid ');
                $sth->bindValue ( ':token', $token, PDO::PARAM_STR );
                $sth->bindValue ( ':expiry', $expiry, PDO::PARAM_STR );
                $sth->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
                $sth->execute ();
                return $token;
            }
            else {
                $token = $val[0];
                $expiry = $val[1];
                if ($expiry < time()){
                    $ntoken = $this->randomString();
                    $expiry = time() + 60*60*240; // after 10 days
                    $sth = $this->db_connection->prepare ('UPDATE device SET token=:token, expiry=:expiry, ltoken=:ltoken where uuid=:uuid ');
                    $sth->bindValue ( ':token', $ntoken, PDO::PARAM_STR );
                    $sth->bindValue ( ':ltoken', $token, PDO::PARAM_STR );
                    $sth->bindValue ( ':expiry', $expiry, PDO::PARAM_STR );
                    $sth->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
                    $sth->execute ();
                    return $token;
                }
                else {
                    return $token;
                }
            }
            return false;
        } else {
            return false;
        }
    }
    public function checkToken($ctoken, $uuid) {
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ('SELECT token, expiry, ltoken FROM device WHERE uuid = :uuid' );
            $sth->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $sth->execute ();
            $val = $sth->fetch (); 
            if ($val[0] == null){
                return 200; // no token exists
            }
            else {
                $token = $val[0];
                $ltoken = $val[2];
                $expiry = $val[1];
                if ($ctoken == $token || $ctoken == $ltoken) {
                    return 0;
                }
                else {
                    error_log($ctoken . ", ". $token . ", ". $ltoken);
                    return 400;
                }
            }
            return 300;
        } else {
            return 300;
        }
    }
    static public function dateNow($tz) {
        if (strlen ( $tz ) > 2) {
            date_default_timezone_set ( $tz );
        }
        $date = date ( 'Y/m/d', time () );
        return $date;
    }

    static public function datetimeNow($tz=NULL) {
        //error_log("Timezone=".$tz);
        if (strlen ( $tz ) > 2) {
            date_default_timezone_set ( $tz );
        }
        //error_log( date ( 'Y/m/d H:i:s', time () ));
        return time ();
    }
    static public function convertUTCDatetimeStringToTimezone($str_time, $device_tz){
        $time = strtotime($str_time);
        Utils::$dt->setTimeZone($device_tz);
        $date = Utils::$dt->setTimeStamp($time);
        $date_str_tz = $date->format(DateTime::ATOM) ;
        return $date_str_tz;
        
    }
    static function randomString($length = 10) {
        $str = "";
        $characters = array_merge ( range ( 'A', 'Z' ), range ( 'a', 'z' ), range ( '0', '9' ) );
        $max = count ( $characters ) - 1;
        for($i = 0; $i < $length; $i ++) {
            $rand = mt_rand ( 0, $max );
            $str .= $characters [$rand];
        }
        return $str;
    }
    public static function forecast($total_net) {
        // cost $1 for 1073741824
        error_log ( "total_net=" . $total_net );
        $total_net_gb = $total_net / 1073741824;
        error_log ( "total_net_gb=" . $total_net_gb );
        $days_in_month = date ( "t" );
        error_log ( "days_in_month=" . $days_in_month );
        $timestamp = strtotime ( 'now' );
        $current_day = date ( "d", $timestamp );
        error_log ( "current_day=" . $current_day );
        $forecasted_usage = ($total_net_gb / $current_day) * $days_in_month;
        return round ( $forecasted_usage, 2 );
    }
    public static function formatSizeUnits($bytes) {
        if ($bytes >= 1073741824) {
            $result = number_format ( $bytes / 1073741824, 2 ) . ' GB';
        } elseif ($bytes >= 1048576) {
            $result = number_format ( $bytes / 1048576, 2 ) . ' MB';
        } elseif ($bytes >= 1024) {
            $result = number_format ( $bytes / 1024, 2 ) . ' KB';
        } elseif ($bytes > 1) {
            $result = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $result = $bytes . ' byte';
        } else {
            $result = '0 bytes';
        }
        return $result;
    }
    public function validateDevice($user_name, $uuid) {
        // error_log("Validating".$user_name.$uuid);
        try {
            $db_connection = new PDO ( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS );
            // database query, getting all the info of the selected user
            $sth = $db_connection->prepare ( '/*qc=on*/' .'SELECT uuid FROM device WHERE user_name = :user_name and uuid = :uuid' );
            $sth->bindValue ( ':user_name', $user_name, PDO::PARAM_STR );
            $sth->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $sth->execute ();
            $check_uuid = $sth->fetch () [0];
            return $check_uuid;
        } catch ( PDOException $e ) {
            error_log ( "DBError" . $e->getMessage () );
        }
        return null;
    }
    public function validateShare($share_name, $uuid) {
        // error_log("Validating".$user_name.$uuid);
        try {
            $db_connection = new PDO ( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS );
            // database query, getting all the info of the selected user
            $sth = $db_connection->prepare ( '/*qc=on*/' .'SELECT uuid FROM motion_last WHERE share_as = :share_name and uuid = :uuid' );
            $sth->bindValue ( ':share_name', $share_name, PDO::PARAM_STR );
            $sth->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $sth->execute ();
            $check = $sth->fetch () [0];
            return $check;
        } catch ( PDOException $e ) {
            error_log ( "DBError" . $e->getMessage () );
        }
        return null;
    }
    public function updateLastAlert($uuid, $file) {
        if ($this->databaseConnection () != null && ! strpos ( $file, "face" )) {
            $ptr = $this->getLastMotionPtr($uuid);
            //error_log($uuid." updateLastAlert ".$ptr);
            $ptr = $ptr + 1;
            if ($ptr > 9){
                $ptr=0;
            }
            $stmt_str="insert into motion_last ( uuid, time, ptr, image0) values ('".$uuid."' , now(), '".$ptr."', '".$file.
                    "') on duplicate key update time = now(), ptr ='". $ptr."', image".$ptr."='".$file."'" ;
            //error_log($stmt_str);
            $stmt = $this->db_connection->prepare ($stmt_str);
            //error_log("updateLastAlert Error=".implode(",", $stmt->errorInfo()));
            $stmt->execute ();
        }
    }
    public function getLastMotionPtr($uuid) {
        if ($this->databaseConnection () != null) {
            $query = $this->db_connection->prepare ( 'SELECT ptr FROM motion_last WHERE uuid = :uuid' );
            $query->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query->execute ();
            $ptr = $query->fetch () [0];
            return $ptr;
        }
        return null;
    }
    public function getShareName($uuid) {
        if ($this->databaseConnection () != null) {
            $query = $this->db_connection->prepare ( 'SELECT share_as FROM motion_last WHERE uuid = :uuid' );
            $query->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query->execute ();
            $share = $query->fetch () [0];
            return $share;
        }
        return null;
    }
    public function getLastAlert($uuid) {
        if ($this->databaseConnection () != null) {
            $ptr = $this->getLastMotionPtr($uuid);
            $stmt_str="SELECT image".$ptr." FROM motion_last WHERE uuid ='".$uuid."'";
            $query = $this->db_connection->prepare ($stmt_str );
            $query->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query->execute ();
            $file = $query->fetch ()[0];
            return $file;
        }
        return null;
    }
    public function getLastAlerts($uuid) {
        if ($this->databaseConnection () != null) {
            $query = $this->db_connection->prepare ( 'SELECT ptr, image0, image1, image2, image3, image4, image5, image6, image7, image8, image9 FROM motion_last WHERE uuid = :uuid' );
            $query->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query->execute ();
            $file = $query->fetch ();
            //error_log("last alerts ".print_r($file, True));
            return $file;
        }
        return null;
    }
    public function publishStats($uuid, $datetime, $temp, $humid, $mean, $rms, $var, $median){
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into stat(uuid, time, temp, humid, mean, rms, var, median)  values(:uuid, :time, :temp, :humid, :mean, :rms, :var, :median)');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->bindValue(':time', $datetime->format(DateTime::ATOM), PDO::PARAM_STR);
            $sth->bindValue(':temp', $temp, PDO::PARAM_STR);
            $sth->bindValue(':humid', $humid, PDO::PARAM_STR);
            $sth->bindValue(':mean', $mean, PDO::PARAM_STR);
            $sth->bindValue(':rms', $rms, PDO::PARAM_STR);
            $sth->bindValue(':var', $var, PDO::PARAM_STR);
            $sth->bindValue(':median', $median, PDO::PARAM_STR);
            $sth->execute();
            //error_log("publishStats Error=".implode(",", $sth->errorInfo()));
        }
    }
    public function retriveTempStats($uuid, $fromString){
        $result=array();
        if ($this->databaseConnection()) {
            $date = Utils::$dt->setTimeStamp($fromString);
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select time, temp, humid from stat where uuid = :uuid and time > :time');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);//strtotime($fromString, $now),
            $sth->bindValue(':time', $date->format(DateTime::ATOM), PDO::PARAM_STR);
            $sth->execute();
            //error_log("retriveTempStats Error=".implode(",", $sth->errorInfo()));
            $result = $sth->fetchAll();
            //print_r($result);
        }
        return $result;
    }
    public function retriveImageStats($uuid, $fromString){
        $result=array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select time, mean, rms, var, median from stat where uuid = :uuid and time > :time');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);//strtotime($fromString, $now),
            $sth->bindValue(':time', strtotime($fromString, time ()), PDO::PARAM_STR);
            $sth->execute();
            //error_log("retriveImageStats Error=".implode(",", $sth->errorInfo()));
            $result = $sth->fetchAll();
            //error_log(print_r($result, true));
        }
        return $result;
    }
    
    public function publishMotion($uuid, $datetime, $image){
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into motion(uuid, time, image) values(:uuid, :time, :image)');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->bindValue(':time', $datetime->format(DateTime::ATOM), PDO::PARAM_STR);
            $sth->bindValue(':image', $image, PDO::PARAM_STR);
            $sth->execute();
            //error_log("publishMotion Error=".implode(",", $sth->errorInfo()));
        }
    }
    public function retriveMotion($uuid, $fromString){
        $result=array();
        if ($this->databaseConnection()) {
            $date = Utils::$dt->setTimeStamp($fromString);
            //error_log($uuid."retriveMotion ".$date->formatDateTime::ATOM));
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(`time`)/900)*900) AS timeslice, count(image) AS count from motion where uuid = :uuid and time > :time GROUP BY timeslice');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->bindValue(':time', $date->format(DateTime::ATOM), PDO::PARAM_STR);
            $sth->execute();
            //error_log("retriveMotion Error=".implode(",", $sth->errorInfo()));
            $result = $sth->fetchAll();
            //print_r($result);
        }
        return $result;
    }

    public function retriveMotionForCorrelation($uuid, $fromString){
        $result=array();
        if ($this->databaseConnection()) {
            $date = Utils::$dt->setTimeStamp($fromString);
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(`time`)/60)*60) AS timeslice, count(image) AS count from motion where uuid = :uuid and time > :time GROUP BY timeslice');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->bindValue(':time', $date->format(DateTime::ATOM), PDO::PARAM_STR);
            $sth->execute();
            //error_log("retriveMotion Error=".implode(",", $sth->errorInfo()));
            $result = $sth->fetchAll();
            //print_r($result);
        }
        return $result;
    }
    public function publishNetwork($uuid, $time, $bytes){
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into network(uuid, time, bytes) values(:uuid, :time, :bytes)');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->bindValue(':time', $time, PDO::PARAM_STR);
            $sth->bindValue(':bytes', $bytes, PDO::PARAM_STR);
            $sth->execute();
            //error_log("publishNetwork Error=".implode(",", $sth->errorInfo()));
        }
    }
    public function retriveTotalNetwork($uuid, $fromString){
        $result=array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select SUM(bytes) from network where uuid = :uuid and time > :time');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->bindValue(':time', strtotime($fromString, time ()), PDO::PARAM_STR);
            $sth->execute();
            error_log("retriveTotalNetwork Error=".implode(",", $sth->errorInfo()));
            $result = $sth->fetchAll();
            //print_r($result);
        }
        return $result;
    }
    public function saveTag($uuid, $name, $time, $url){
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into history_tag(device_uuid, name, time, url, created)  values(:uuid, :name, :time, :url, now())');
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':time', $time, PDO::PARAM_STR);
            $sth->bindValue(':url', $url, PDO::PARAM_STR);
            $sth->execute();
        }
    }
    public function getTags($uuid, $limit){
        $result=array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select name, time, url from history_tag where device_uuid = :uuid order by time asc limit '.$limit );
            $sth->bindValue(':uuid',  $uuid, PDO::PARAM_STR);
            $sth->execute();
            $result = $sth->fetchAll();
        }
        return $result;
    }
    public function getConfig($name){
        $result=array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select value from config where name = :name');
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->execute();
            $result = $sth->fetch()[0];
        }
        return $result;
    }
    public function registerSipDevice($uuid, $user, $pass){
        
        error_log('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/showUsers.html?remove.".$uuid.'@sip.ibeyonde.com=on"');
        exec ('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/showUsers.html?remove.".$uuid.'@sip.ibeyonde.com=on"');
        
        error_log('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/addUser.html?user=".$uuid."&domain=".SIP_HOST."&password=".urlencode($pass)."&name=".$uuid."_".$user."&email=".$uuid."_".$user.'@sip.ibeyonde.com&submit=Add" | grep Added');
        exec ('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/addUser.html?user=".$uuid."&domain=".SIP_HOST."&password=".urlencode($pass)."&name=".$uuid."_".$user."&email=".$uuid."_".$user.'@sip.ibeyonde.com&submit=Add" | grep Added', $contentv);
        
        
        error_log('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/showUsers.html?remove.".$user.'@sip.ibeyonde.com=on"');
        exec ('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/showUsers.html?remove.".$user.'@sip.ibeyonde.com=on"');
        
        error_log('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/addUser.html?user=".$user."&domain=".SIP_HOST."&password=".urlencode($pass)."&name=".$user."&email=".$user.'@sip.ibeyonde.com&submit=Add" | grep Added');
        exec ('curl --silent "http://'.SIP_USER.":".SIP_PASS."@".SIP_HOST.":".SIP_PORT."/addUser.html?user=".$user."&domain=".SIP_HOST."&password=".urlencode($pass)."&name=".$user."&email=".$user.'@sip.ibeyonde.com&submit=Add" | grep Added', $contentv);
        
        return json_encode(array('success' => $contentv[0]));
    }
    
    public function generateKeyPair($uuid, $username, $user_email, $passphrase) {
        $cert = array ();
        $dn = array ("countryName" => "IN","stateOrProvinceName" => "Telangana","localityName" => "Manikonda","organizationName" => "Ibeyonde Ltd","organizationalUnitName" => "$username",
                "commonName" => "$uuid","emailAddress" => "$user_email" 
        );
        // Generate a new private (and public) key pair
        $rsaKey = openssl_pkey_new ();
        // Generate a certificate signing request
        $csr = openssl_csr_new ( $dn, $rsaKey );
        $sscert = openssl_csr_sign ( $csr, null, $rsaKey, 365 );
        openssl_csr_export ( $csr, $csrout );
        openssl_x509_export ( $sscert, $certout );
        openssl_pkey_export ( $rsaKey, $privKey, $passphrase );
        $cert ['private'] = $privKey;
        $pubKey = $this->sshEncodePublicKey( $rsaKey );
        $cert ['public'] = $pubKey;
        $cert ['passphrase'] = $passphrase;
        error_log ( "Public Key = " . $pubKey );
        // Show any errors that occurred here
        while ( ($e = openssl_error_string ()) !== false ) {
            echo $e . "\n";
        }
        return $cert;
    }
    function sshEncodePublicKey($privKey) {
        $keyInfo = openssl_pkey_get_details ( $privKey );
        $buffer = pack ( "N", 7 ) . "ssh-rsa" . $this->sshEncodeBuffer( $keyInfo ['rsa'] ['e'] ) . $this->sshEncodeBuffer ( $keyInfo ['rsa'] ['n'] );
        return "ssh-rsa " . base64_encode ( $buffer );
    }
    function sshEncodeBuffer($buffer) {
        $len = strlen ( $buffer );
        if (ord ( $buffer [0] ) & 0x80) {
            $len ++;
            $buffer = "\x00" . $buffer;
        }
        return pack ( "Na*", $len, $buffer );
    }
    public function comb($m, $a) {
        if (! $m) {
            yield [ ];
            return;
        }
        if (! $a) {
            return;
        }
        $h = $a [0];
        $t = array_slice ( $a, 1 );
        foreach ( $this->comb ( $m - 1, $t ) as $c )
            yield array_merge ( [ $h 
            ], $c );
        foreach ( $this->comb ( $m, $t ) as $c )
            yield $c;
    }
    public static function getSSLPage($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        #curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    public static function getLastNDays($days, $format = 'd/m'){
        $m = date("m"); $de= date("d"); $y= date("Y");
        $dateArray = array();
        for($i=0; $i<=$days-1; $i++){
            $dateArray[] =  date($format, mktime(0,0,0,$m,($de-$i),$y));
        }
        return array_reverse($dateArray);
    }
}
   

Utils::$dt=new DateTime();
