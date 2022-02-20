<?php 

require_once(__ROOT__.'/classes/NetUtils.php');
require_once(__ROOT__.'/classes/DeviceContext.php');
require_once(__ROOT__.'/classes/VQuality.php');
require_once(__ROOT__.'/classes/Aws.php');


class Mp4 {
    const ROOT = "/srv/www/udp1.ibeyonde.com/public_html/live_cache/" ;
    const INDEX = "/index";
    //const D_ERROR = "/video/error.mp4";
    //const D_NO_SIGNAL = "/video/no_signal.mp4";
    //const D_UNAUTHORIZED = "/video/unauthorized.mp4";
    //const D_LOADING = "/video/loading.mp4";
    
    const D_ERROR = "error";
    const D_NO_SIGNAL = "no_signal";
    const D_UNAUTHORIZED = "unauthorized";
    const D_LOADING = "loading";
    
    private $record="0";
    private $last_index="-1";
    
    public $aws;
    
    public function __construct($uuid)
    {
        if (!file_exists(Mp4::ROOT.$uuid)) {
            mkdir(Mp4::ROOT.$uuid, 0777, true);
        }
        $this->aws = new Aws();
    }
    
    public function setQuality($uuid, $q){
        file_put_contents(Mp4::ROOT.$uuid."/.qual", $q);
    }
    
    public function getQuality($uuid, $dq){
        if (file_exists(Mp4::ROOT.$uuid."/.qual")){
            $q = file_get_contents(Mp4::ROOT.$uuid."/.qual");
            return $q;
        }
        else {
            return $dq;
        }
    }
    
    public function display($uuid, $type){
        if (file_exists($uuid)){
            //TODO
            error_log("------".$type);
        }
    }
    
    public function updateVideo($uuid, $vid, $stamp, $timezone){
        $ind_tsv = explode("-",  $stamp); //index-timestamp-cache_size
        $index = intval($ind_tsv[0]);
        $timestamp = intval($ind_tsv[1]);
        $cache_size = intval($ind_tsv[2]);

        file_put_contents(Mp4::ROOT.$uuid."/vid".$index.".mp4", $vid);
        touch(Mp4::ROOT.$uuid."/vid".$index.".mp4", $timestamp);
        //error_log("Mp4 writing to ".Mp4::ROOT.$uuid."/vid".$index.".mp4");
        
        $rec_start = $this->checkRecording($uuid, $timezone);
        if ($rec_start != false){
            $time_now = new DateTime("now", new DateTimeZone($timezone));
            $time_now_str = $time_now->format('Y/m/d/H_i_s');
            $this->aws->uploadFile($uuid, 'record/'.$rec_start."/".$time_now_str."/".Utils::randomString(6).".mp4", 
                    Mp4::ROOT.$uuid."/vid".$index.".mp4", 'video/mp4');
        }
        return 1;
    }
    
    public function checkRecording($uuid, $timezone){
        if (!file_exists(Mp4::ROOT.$uuid."/.record")) {
            return false;
        }
        $rec_start = file_get_contents(Mp4::ROOT.$uuid."/.record");
        if ($rec_start == "") {
            return false;
        }
        $dtz = new DateTimeZone($timezone);
        $rec_start_time= DateTime::createFromFormat(DateTime::ATOM, $rec_start, $dtz);
        $time_now = new DateTime("now", new DateTimeZone($timezone));
        error_log("Start time = " . $rec_start . "  Current time = " . $time_now->format(DateTime::ATOM) . " timezone =". $timezone);
        if (($time_now->getTimestamp() - $rec_start_time->getTimestamp()) < 300 ){
            return $rec_start;
        }
        else {
            $this->stopRecording($uuid, $timezone);
            return false;
        }
    }
    
    public static function startRecording($uuid, $timezone){
        $time_now = new DateTime("now", new DateTimeZone($timezone));
        $rec_start = $time_now->format(DateTime::ATOM);
        file_put_contents(Mp4::ROOT.$uuid."/.record", $rec_start);
    }
    
    public static function stopRecording($uuid, $timezone){
        file_put_contents(Mp4::ROOT.$uuid."/.record", "");
    }
    
    public static function isRecording($uuid){
        if (!file_exists(Mp4::ROOT.$uuid."/.record")) {
            return false;
        }
        $rec_start = file_get_contents(Mp4::ROOT.$uuid."/.record");
        if ($rec_start == "") {
            return false;
        }
        else {
            return true;
        }
    }
    public function establishOwnership($uuid, $stream_id){
        $context = new DeviceContext();
        $cid = $context->getDeviceContext($uuid, 'live');
        if ($cid == 'closed' || $cid == null) {
            $context->updateDeviceContext($uuid, "live", $stream_id);
            error_log(">>>>>>>>>>>>>>>>>>>Mp4: establising ownership " .$stream_id);
            return true;
        }
        else if ($cid == $stream_id){
            return true;
        }
        else {
            return false;
        }
    }
    
    public function releaseOnership($uuid, $stream_id){
        $context = new DeviceContext();
        $cid = $context->getDeviceContext($uuid, 'live');
        if ($cid == $stream_id){
            $context->updateDeviceContext($uuid, "live", 'closed');
            //error_log("<<<<<<<<<<<<<<<<Mp4: releasing ownership " .$stream_id);
        }
    }

}

?>