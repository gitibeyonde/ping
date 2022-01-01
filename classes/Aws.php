<?php

require_once (__ROOT__ . '/libraries/aws.phar');
require_once(__ROOT__.'/classes/Motion.php');
require_once(__ROOT__.'/classes/RecordFile.php');
require_once(__ROOT__.'/classes/Utils.php');
require_once(__ROOT__.'/config/config.php');

class Aws {
    private static $s3 = null;
    private static $queue_dict = array ();
    private static $credentials = array (
                    'version' => S3_VERSION,
                    'key'    => S3_KEY,
                    'secret' => S3_SECRET,
                    'region' => S3_REGION
            );

    
    const bucket = 'data.ibeyonde';
    public function __construct() {
        if (self::$s3 == null) {
            self::$s3 = Aws\S3\S3Client::factory ( self::$credentials  );
        }
    }
    public function uploadFile($uuid, $fileKey, $filePath, $type){
        error_log("Adding image to s3 with key ".$fileKey. " image path =". $filePath);
        $result = self::$s3->upload(
                self::bucket, 
                $uuid . "/" . $fileKey, 
                fopen($filePath, 'rb'),
                'public-read', 
                array('params' => array('ContentType' => $type))
                );
        error_log("Result=".print_r($result, true));
    }
    
    public function uploadImage($fileKey, $image, $type){
        // Create temp file
        $tempFilePath = basename($fileKey);
        //error_log("upload Image temp file=".$tempFilePath);
        $result = imagejpeg($image, $tempFilePath);
        //error_log("upload Image temp file saving result =".print_r($result, true)." image file size ".print_r(getimagesize($tempFilePath), true));
        $result = self::$s3->upload( 
                self::bucket, 
                $fileKey, 
                fopen($tempFilePath, 'rb'), 
                'public-read',
                array('params' => array('ContentType' => $type))
                );
        //error_log("Result=".print_r($result['ObjectURL'], true));
        //error_log("Result=".print_r($result, true));
        unlink($tempFilePath);
    }
    
    public function addWaterMark($watermarktext, $target_file)
    {
        $image=imagecreatefromjpeg($this->getSignedFileUrl($target_file));
        $font="/srv/www/ping.ibeyonde.com/public_html/classes/font.ttf";
        $height=imagesy($image);
        
        $fontsize = (string)intval(0.06*$height);
        
        $white = imagecolorallocate($image, 255, 255, 255);
        $result = imagettftext($image, $fontsize, 0, 30, $height/2, $white, $font, $watermarktext);
        //error_log(" AddWaterMark  $target_file".print_r($result, true));
        $this->uploadImage($target_file, $image, 'image/jpeg');
    }
    
    public function getSignedFileUrl($file) {
        $cmd = self::$s3->getCommand ( 'GetObject', 
                [ 
                        'Bucket' => self::bucket,
                        'Key' => ( string ) $file 
                ] );
        
        $request = self::$s3->createPresignedRequest ( $cmd, '+240 minutes' );
        
        // Get the actual presigned-url
        $presignedUrl = ( string ) $request->getUri ();
        return $presignedUrl;
    }
    public function getFileUrl($file) {
        return self::$s3->getObjectUrl(self::bucket, $file);
        //return $this->getSignedFileUrl($file);
    }
    //depricated
    public function getObjectUrl($uuid, $img_name) {
        return self::$s3->getObjectUrl ( self::bucket, $uuid . "/" . $img_name);
    }
    public function checkTrainingData($user_name){
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Prefix' => "train_data/". $user_name. ".xml"
        ));
        return iterator_count($iterator) == 1;
    }
    public function removeTrainingData($user_name){
        $result = self::$s3->deleteMatchingObjects(self::bucket, "train_data/". $user_name . ".xml");
    }
    public function loadMotionDataDesc($uuid, $date) // format 2016/06/02
    {
        if (!isset($uuid)){
            throw new Exception("AWS Invalid uuid ".$uuid);
        }
        $motion=array();
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Prefix' => $uuid.'/'.$date 
        ));
    
        foreach ($iterator as $object) {
            $motion[] = new Motion($object['Key']);
        }
        usort($motion, array($this, "compareDesc"));
    
        return $motion;
    }
    

    public function loadTimeMotionData($uuid, $path, $time1, $time2){ // format path = 2016/06/02; time1 = 05 time2=08
        $motions = $this->loadMotionData ( $uuid,  $path."/".$time1 );
        $time1=intval($time1);
        $time2=intval($time2) + 1;
        $motion_array = array();
        error_log($time1 . ".." . $time2);
        foreach ($motions as $motion){
            error_log("check"." --".intval($motion->hour) ."..".intval($motion->minute));
            if (intval($motion->hour) == $time1 && intval($motion->minute) <= $time2) {
                error_log("push"." --".print_r($motion, true));
                array_push($motion_array, $motion);
            }
        }
        $time1 = $time1 - 1;
        $motions = $this->loadMotionData ( $uuid,  $path."/".($time1 < 10 ? "0".$time1 : $time1));
        if (count($motion_array) < 10){
            error_log($time1 . "..**");
            foreach ($motions as $motion){
                error_log("check"." --".intval($motion->hour) ."..".intval($motion->minute));
                if (intval($motion->hour) == $time1) {
                    error_log("push"." --".print_r($motion, true));
                    array_push($motion_array, $motion);
                }
	    }
        }
        error_log("Size=" . count($motion_array));
        usort($motion_array, array($this, "compareAsc"));
        return array_slice($motion_array, 0, 10);;
    }
    

    public function loadMotionData($uuid, $date) // format 2016/06/02
    {
        if (!isset($uuid)){
            throw new Exception("AWS Invalid uuid ".$uuid);
        }
        $motion_array=array();
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Prefix' => $uuid.'/'.$date
        ));
        
        foreach ($iterator as $object) {
            $motion_array[] = new Motion($object['Key']);
        }
        usort($motion_array, array($this, "compareAsc"));
        
        return $motion_array;
    }
    
    
    public function loadMinuteMotionDataUrl($uuid, $path, $time){ // format path = 2016/06/02; time = 05_21 NOTE this loads URL not objects 
        $motions = $this->loadMotionData ( $uuid,  $path );
        $urls = array();
        foreach ($motions as $motion){
            if (strcmp($motion->hour."_".$motion->minute, $time) == 0) {
                array_push($urls, $motion->image);
            }
        }
        return $urls;
    }
  
    public function latestMotionDataUrl($uuid)
    {
        $utils = new Utils();
        $file = $utils->getLastAlert($uuid);
        if ($file != null){
           $motion = new Motion($file);
           return array($this->getSignedFileUrl($motion->image ), $motion->datetime);
        }
        else {
            return array("https://app.ibeyonde.com/img/no_update_error.png", "");
        }
    }
    public function latestMotionDataUrls($uuid)
    {
        $utils = new Utils();
        $mv = $utils->getLastAlerts($uuid);
        $ptr=$mv['ptr'];
        $iv = array();
        //error_log("latestMotionDataUrls ".print_r($mv, True));
        for ($i=0;$i<10;$i++){
            $fmv = $mv['image'.$ptr];
            if ($fmv != null){
                $mot = new Motion($fmv);
                //error_log(print_r($mot, True));
                $iv[$i] = array($this->getSignedFileUrl($mot->image ), $mot->datetime);
            }
            else {
                break;
            }
            //error_log(print_r($iv[$i], true));
            $ptr = $ptr - 1;
            if ($ptr < 0) {
                $ptr=9;
            }
        }
        //error_log("latestMotionDataUrls ". print_r($iv, True));
        return $iv;
    }
    public function deleteMotionData($uuid)
    {
        if (!isset($uuid)){
            throw new Exception("AWS Invalid uuid ".$uuid);
        }
        $result = self::$s3->deleteMatchingObjects(self::bucket, $uuid.'/');
    }
    
    public function loadRecording($uuid, $key) // format 2016/06/02
    {
        if (!isset($uuid)){
            throw new Exception("AWS Invalid uuid ".$uuid);
        }
        $recordings=array();
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Prefix' => $uuid.'/record/'.$key
        ));
        
        foreach ($iterator as $object) {
            $recordings[] = new RecordFile($object['Key']);
        }
        //error_log(print_r($recordings));
        usort($recordings, array($this, "compareDesc"));
        
        return $recordings;
    }
    public function deleteRecording($uuid, $key){
        if (!isset($uuid)){
            throw new Exception("AWS Invalid uuid ".$uuid);
        }
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Prefix' => $uuid.'/record/'.$key
        ));
        $keys=array();
        foreach ($iterator as $object) {
            $keys[] = $object['Key'];
        }
        //error_log("Keys=".print_r($keys, true));
        $result = self::$s3->deleteObjects(array(
                'Bucket' => self::bucket, 
                'Delete' => [ 'Objects' => array_map(function ($key) {
                                        return array('Key' => $key);
                                }, $keys),
                             'Quiet' => true 
                            ]
        ));
        //error_log(print_r($result, true));
    }
    public function listRecordings($uuid) // format 2016/06/02
    {
        if (!isset($uuid)){
            throw new Exception("AWS Invalid uuid ".$uuid);
        }
        $recordings=array();
        $result = self::$s3->ListObjects(array(
                'Bucket' => self::bucket, 'Delimiter' => '/', 'Prefix' => $uuid.'/record/'
        ));
        if ($result != null) {
            foreach ($result->get("CommonPrefixes") as $object) {
                $recordings[] = str_replace($uuid.'/record/', '', $object['Prefix']);
            }
        }
        return $recordings;
    }
    public function getFolderSize($uuid){
        $totalCount = 0;
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Delimiter' => '/', 'Prefix' => $uuid.'/'
        ));
        error_log("Getting folder size");
        foreach ($iterator as $object) {
            $totalCount += 1;
        }
        error_log("Got folder size ".$totalCount);
        return $totalCount;
    }

    public function getFolderStats($uuid, $date){
        $totalCount = 0;
        $totalSize = 0;
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Prefix' => $uuid
        ));
        foreach ($iterator as $object) {
            $totalCount += 1;
            $totalSize += $object['Size'];
        }
        $stats = array ( $totalCount, Utils::formatSizeUnits($totalSize) );
        return $stats;
    }

    public function isFolderObjectCount($uuid){
        $count=0;
        $iterator = self::$s3->getIterator('ListObjects', array(
                'Bucket' => self::bucket, 'Prefix' => $uuid.'/'.$date
        ));
        foreach ($iterator as $object) {
            $count += 1;
        }
        return $count;
    }
    function compareAsc($a, $b)
    {
        return strcmp($b->datetime, $a->datetime);
    }

    function compareDesc($a, $b)
    {
        return strcmp($a->datetime, $b->datetime);
    }
}


