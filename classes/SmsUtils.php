<?php
// include the config
require_once (__ROOT__ . '/config/config.php');
require_once (__ROOT__ . '/libraries/password_compatibility_library.php');
require_once (__ROOT__ . '/libraries/aws.phar');
require_once(__ROOT__.'/config/config.php');

class SmsUtils
{

    public static $dt;
    private static $dv;
    private static $ld;
    private static $kl;
    private static $s3 = null;
    const bucket = 'data.simonline';

    private $db_connection = null;
    
    private static $credentials = array (
            'version' => S3_VERSION,
            'key'    => S3_KEY,
            'secret' => S3_SECRET,
            'region' => "ap-south-1"
    );
    public function __construct()
    {
        SmsUtils::$dv = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-.~_');
        SmsUtils::$ld = count(SmsUtils::$dv);
        SmsUtils::$kl = 64;
        if (self::$s3 == null) {
            self::$s3 = Aws\S3\S3Client::factory ( self::$credentials  );
        }
    }
    
    public function uploadFileToSimOnline($fileKey, $filePath){
        error_log("Saving ".$fileKey. " to =". $filePath);
        $result = self::$s3->upload(
                self::bucket,
                $fileKey,
                fopen($filePath, 'rb'),
                'public-read',
                array('params' => array('ContentType' => 'text/plain'))
                );
        error_log("Result=".print_r($result, true));
    }
    private function databaseConnection()
    {
        // if connection already exists
        if ($this->db_connection != null) {
            return true;
        } else {
            try {
                $this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                return true;
            } catch (PDOException $e) {
                $_SESSION['message']  = MESSAGE_DATABASE_ERROR . $e->getMessage();
            }
        }
        return false;
    }
    
    public function templateReplace($template, $data){
        if (preg_match_all("/{{(.*?)}}/", $template, $m)) {
            foreach ($m[1] as $i => $varname) {
                $template = str_replace($m[0][$i], sprintf('%s', $data[$varname]), $template);
            }
        }
        return $template;
    }
    
    public function templateInputFields($template){
        $fields=array();
        if (preg_match_all("/{{(.*?)}}/", $template, $m)) {
            foreach ($m[1] as $i => $varname) {
                error_log($i."Field = " . $varname);
                $fields[]=$varname;
            }
        }
        return $fields;
    }
    public function getNewKey()
    {
        $cv = "";
        for ($i = 0; $i < SmsUtils::$kl; $i ++) {
            $cv = $cv . SmsUtils::$dv[rand(0, SmsUtils::$ld - 1)];
        }
        return $cv;
    }


    public function createHostKey($user_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $hk = $this->getNewKey();
            $exp = date(DateTime::ATOM, strtotime('+9 month'));
            $sth = $this->db_connection->prepare('insert into host_key(user_id, host_key, expiringOn, changedOn)  values(:user_id, :host_key, :expiringOn, now())');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':host_key', $hk, PDO::PARAM_STR);
            $sth->bindValue(':expiringOn', $exp, PDO::PARAM_STR);
            $sth->execute();
            error_log("createHostKey Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return array(
            $hk,
            $exp
        );
    }

    public function getHostKey($user_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select host_key, expiringOn from host_key where user_id=:user_id');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            $val = $sth->fetch();
            $hk = $val[0];
            $exp = $val[1];
            error_log("getHostKey Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return array(
            $hk,
            $exp
        );
    }
    
    public function checkHostKey($host_key)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select user_id, expiringOn from host_key where host_key=:host_key');
            $sth->bindValue(':host_key', $host_key, PDO::PARAM_STR);
            $sth->execute();
            $val = $sth->fetch();
            $uid = $val[0];
            $exp = $val[1];
            error_log("checkHostKey Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
        return array(
                $uid,
                $exp
        );
    }
    
    public function delHostKey($user_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from host_key where user_id=:user_id');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("delHostKey Error=" . implode(",", $sth->errorInfo()));
        }
    }

    public function validateSms($sms){
        //$regex = '/(\@£$¥èéùìòÇ\fØø\nÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !#¤%&()*+,-.[0-9]:;<=>\?¡[A-Z]ÄÖÑÜ§¿[a-z]äöñüà\^\{\}\[~\]\|€)+/';
        $regex = '/([0-9A-Za-z\{\}].)/';
        if (preg_match($regex, $sms, $matches)){
            error_log(print_r($matches, true));
            return strlen($sms);
        }
        else {
            error_log("Validation failed");
            return 0;
        }  
    }
    
    public function normalizePhone($number){
        $number = trim($number);
        if(preg_match("/^(?:(?:\+|0{0,2})91(\s*[\-]\s*)?|[0]?)?[789]\d{9}$/", $number)) {
            $number = str_replace('(', '', $number);
            $number = str_replace(')', '', $number);
            $number = str_replace(' ', '', $number);
            $number = str_replace('-', '', $number);
            if(preg_match("/^[789]\d{9}$/", $number)) {
                return "91".$number;
            }
            if(preg_match("/^0[789]\d{9}$/", $number)) {
                return "91".substr($number, 1);
            }
            if(preg_match("/^91[789]\d{9}$/", $number)) {
                return $number;
            }
            if(preg_match("/^\+91[789]\d{9}$/", $number)) {
                return substr($number, 1);
            }
        }
        else {
           return null;
        }
    }
    
    
    ///////////////////TEMPLATE////////////////////////
    
    
    public function getSmsTemplates($user_id, $template_id="")
    {
        $smst = array();
        if ($this->databaseConnection()) {
            if ($template_id == "") {
                // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_templates where user_id=:user_id');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("getSmsTemplates Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                while($obj =  $sth->fetch()){
                    $smst[]=$obj;
                }
                return $smst;
            }
            else { // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_templates where user_id=:user_id and id=:template_id');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->bindValue(':template_id', $template_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("getSmsTemplates Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                return $sth->fetch();
            }
        }
    }
    
    public function getSmsTemplatesOfType($user_id, $template_type)
    {
        $smst = array();
        if ($this->databaseConnection()) {
                // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_templates where user_id=:user_id and type=:type');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->bindValue(':type', $template_type, PDO::PARAM_STR);
                $sth->execute();
                while($obj =  $sth->fetch()){
                    $smst[]=$obj;
                }
                error_log("getSmsTemplatesOfType Error=" . implode(",", $sth->errorInfo())."count=".count($smst));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
            return $smst;
        }
    }
    public function updateSmsTemplate($user_id, $template_id, $template)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('update sms_templates set template=:template where id=:id and user_id=:user_id');
            $sth->bindValue(':id', $template_id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':template', $template, PDO::PARAM_STR);
            $sth->execute();
            error_log("updateSmsTemplate Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    public function deleteSmsTemplate($sms_template_id, $user_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from sms_templates where id=:id and user_id=:user_id');
            $sth->bindValue(':id', $sms_template_id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("delSmsTemplate Error=" . implode(",", $sth->errorInfo()).$sms_template_id.$user_id);
        }
    }
    
    public function createSmsTemplate($user_id, $name, $template, $type)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into sms_templates(user_id, name, template, type)  values(:user_id, :name, :template, :type)');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':template', $template, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':type', $type, PDO::PARAM_STR);
            //$sth->bindValue(':changedOn', date(DateTime::ATOM), PDO::PARAM_STR);
            $sth->execute();
            error_log("createSmsTemplate Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    
    ///////////////////SURVEY////////////////////////
    public function getSurvey($user_id, $survey_id="")
    {
        $smst = array();
        if ($this->databaseConnection()) {
            if ($survey_id == "") {
                // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_survey where user_id=:user_id');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("no survey id getSurvey Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                while($obj =  $sth->fetch()){
                    $smst[]=$obj;
                }
                return $smst;
            }
            else { // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_survey where user_id=:user_id and id=:survey_id');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->bindValue(':survey_id', $survey_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("survey id getSurvey Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                return $sth->fetch();
            }
        }
    }
    
    public function createSurvey($user_id, $name, $description, $template_id, $response_type, $start, $end)
    {
        if ($this->databaseConnection()) {
            $sth = $this->db_connection->prepare('insert into sms_survey(user_id, name, description, template_id, response_type, startDate, endDate, createdOn) '
                                                            .' values (:user_id, :name, :description, :template_id, :response_type, :startDate, :endDate, now())');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':description', $description, PDO::PARAM_STR);
            $sth->bindValue(':template_id', $template_id, PDO::PARAM_STR);
            $sth->bindValue(':response_type', $response_type, PDO::PARAM_STR);
            $sth->bindValue(':startDate', $start, PDO::PARAM_STR);
            $sth->bindValue(':endDate', $end, PDO::PARAM_STR);
            $sth->execute();
            error_log("createSurvey Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    
    public function deleteSurvey($survey_id, $user_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from sms_survey where id=:id and user_id=:user_id');
            $sth->bindValue(':id', $survey_id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("delSmsTemplate Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    ///////////////////TRIGGER////////////////////////
    
    public function getTrigger($user_id, $trigger_id="")
    {
        $smst = array();
        if ($this->databaseConnection()) {
            if ($trigger_id == "") {
                // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_trigger where user_id=:user_id');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("getTrigger Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                while($obj =  $sth->fetch()){
                    $smst[]=$obj;
                }
                return $smst;
            }
            else { // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_trigger where user_id=:user_id and id=:trigger_id');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->bindValue(':trigger_id', $trigger_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("getTrigger Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                return $sth->fetch();
            }
        }
    }
    
    
    public function createTrigger($user_id, $name, $description, $template_id)
    {
        if ($this->databaseConnection()) {
            $sth = $this->db_connection->prepare('insert into sms_trigger(user_id, name, description, template_id, enable, createdOn)  values (:user_id, :name, :description, :template_id, 1, now())');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':description', $description, PDO::PARAM_STR);
            $sth->bindValue(':template_id', $template_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("createTrigger Error=" . implode(",", $sth->errorInfo()).$sth->queryString);
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    
    public function deleteTrigger($trigger_id, $user_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from sms_trigger where id=:id and user_id=:user_id');
            $sth->bindValue(':id', $trigger_id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("deleteTrigger Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    ///////////////////AUDIENCE   FILE ////////////////////////
    
    
    public function createAudienceFile($user_id, $name, $description, $fkey)
    {
        if ($this->databaseConnection()) {
            $sth = $this->db_connection->prepare('insert into audience_file(user_id, name, description, file, createdOn)  values (:user_id, :name, :description, :file, now())');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':description', $description, PDO::PARAM_STR);
            $sth->bindValue(':file', $fkey, PDO::PARAM_STR);
            $sth->execute();
            error_log("createAudienceFile Error=" . implode(",", $sth->errorInfo()).$sth->queryString);
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
                return -1;
            }
            return $this->db_connection->lastInsertId();
        }
    }
    
    public function getAudienceFile($user_id, $file_id="")
    {
        $smst = array();
        if ($this->databaseConnection()) {
            if ($file_id == "") {
                // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from audience_file where user_id=:user_id and removed is NULL');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("getAudienceFile Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                while($obj =  $sth->fetch()){
                    $smst[]=$obj;
                }
                return $smst;
            }
            else { // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from audience_file where user_id=:user_id and id=:audience_id and removed is NULL');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->bindValue(':audience_id', $file_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("getAudienceFile Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                return $sth->fetch();
            }
        }
    }
    public function deleteAudienceFile($user_id, $audience_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            //$sth = $this->db_connection->prepare('update audience_file set removed=now() where id=:audience_id and user_id=:user_id');
            $sth = $this->db_connection->prepare('delete from audience_file where id=:audience_id and user_id=:user_id');
            $sth->bindValue(':audience_id', $audience_id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("deleteAudienceFile Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    ///////////////////AUDIENCE////////////////////////
    
    public function getAudience($user_id, $file_id)
    {
        $smst = array();
        if ($this->databaseConnection()) {
                // database query, getting all the info of the selected user
                $sth = $this->db_connection->prepare('select * from sms_audience where user_id=:user_id and file_id=:file_id limit 200');
                $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
                $sth->bindValue(':file_id', $file_id, PDO::PARAM_STR);
                $sth->execute();
                error_log("getAudience Error=" . implode(",", $sth->errorInfo()));
                if ( $sth->errorInfo()[0] != "0000"){
                    $_SESSION['message'] = print_r($sth->errorInfo(), true);
                }
                while($obj =  $sth->fetch()){
                    $smst[]=$obj;
                }
                return $smst;
        }
    }
    
    
    public function createAudience($user_id, $audience_file_id, $name, $description, $number, $json_data)
    {
        if ($this->databaseConnection()) {
            $sth = $this->db_connection->prepare('insert into sms_audience(user_id, file_id, number, data, createdOn)  values (:user_id, :file_id, :number, :data, now())');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':file_id', $audience_file_id, PDO::PARAM_STR);
            $sth->bindValue(':number', $number, PDO::PARAM_STR);
            $sth->bindValue(':data', $json_data, PDO::PARAM_STR);
            $sth->execute();
            error_log("createAudience Error=" . implode(",", $sth->errorInfo()).$sth->queryString);
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    public function createAudienceFromUpload($user_id, $name, $description, $fkey, $ffrom)
    {
        $this->uploadFileToSimOnline($fkey, $ffrom);
        $audience_file_id = $this->createAudienceFile($user_id, $name, $description, $fkey);
        if ($audience_file_id == -1){
            error_log("Filed to create the audience file entry in DB.");
            return -1;
        }
        $pd = array_map('str_getcsv', file($ffrom));
        $number="";
        $sim=0;
        foreach ($pd[0] as $header){
            $dsim = similar_text(trim($header), "number");
            if ($sim < $dsim){
                $sim = $dsim;
                $number = $header;
            }
        }
        error_log("Number=".$number);
        foreach(array_splice($pd, 1) as $row){
            error_log(" Row=".print_r($row, true));
            $data = array();
            for ($i=0; $i<count($pd[0]);$i++){
                $header = trim($pd[0][$i]);
                if ($header == $number){
                    $phone = $this->normalizePhone($row[$i]);
                }
                else {
                    $data[$header] = trim($row[$i]);
                }
            }
            error_log("Number=".$phone."Data=".print_r($data, true));
            $this->createAudience($user_id, $audience_file_id, $name, $description, $phone, json_encode($data));
        }
    }
    
    public function deleteAudience($audience_id, $user_id)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from sms_audience where id=:audience_id and user_id=:user_id');
            $sth->bindValue(':audience_id', $audience_id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("deleteAudience Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
}
?>