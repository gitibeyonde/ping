<?php
require_once(__ROOT__ . '/libraries/aws.phar');
require_once(__ROOT__.'/classes/Motion.php');
require_once(__ROOT__.'/classes/Utils.php');
use Aws\CloudWatch\CloudWatchClient;

class CloudWatch {
    private static $cw = null;
    private static $credentials = array (
                    'version' => AWS_VERSION,
                    'key'    => AWS_KEY,
                    'secret' => AWS_SECRET,
                    'region' => AWS_REGION
            );
    public function __construct() {
        if (self::$cw == null) {
            self::$cw = Aws\CloudWatch\CloudWatchClient::factory ( self::$credentials  );
        }
    }
    
    
    public function publishMetrics($deviceUuid, $metricsName, $value, $unit, $time){
        if ($value < 0) return;
        try {
            $result = self::$cw->putMetricData(array(
                    'Namespace' => $deviceUuid,
                    'MetricData' => array(
                            array(
                                    'MetricName' => $metricsName,
                                    'Timestamp' => $time,
                                    'Value' => $value,
                                    'Unit' => $unit
                            )
                    )
            ));
        } catch (Exception $e) {
            // output error message if fails
            error_log($e->getMessage());
        }
        
    }


    public function publishMetricsNow($deviceUuid, $metricsName, $value, $unit){
        $this->publishMetrics($deviceUuid, $metricsName, $value, $unit, time());
    }
    
    public function publishTempHumid($deviceUuid, $temp, $humid, $time){
        if ($temp < 0 || $humid < 0) return;
        try {
            $this->publishMetrics($deviceUuid, 'Temperature', $temp, 'None', $time);
            $this->publishMetrics($deviceUuid, 'Humidity', $temp, 'None', $time);
        } catch (Exception $e) {
            error_log("publishTempHumid".$e->getMessage());
        }
    }

    public function publishTempHumidImgParams($time, $deviceUuid, $temp, $humid, $mean, $rms, $var, $median){
        try {

            if ($temp > 0 && $humid > 0) {
               $this->publishMetrics($deviceUuid, 'Temperature', $temp, 'None', $time);
               $this->publishMetrics($deviceUuid, 'Humidity', $humid, 'None', $time);
            }
            if ($mean > 0 ){
               //$this->publishMetrics($deviceUuid, 'Mean', $mean, 'None', $time);
               //$this->publishMetrics($deviceUuid, 'Rms', $rms, 'None', $time);
               //$this->publishMetrics($deviceUuid, 'Var', $var, 'None', $time);
               //$this->publishMetrics($deviceUuid, 'Median', $median, 'None', $time);
            }
        } catch (Exception $e) {
            error_log("publishTempHumidImgParams".$e->getMessage());
        }
    }

    public function getMetrics($deviceUuid, $metricsName, $fromString, $now, $function){
        return $this->getMetricsWithGranularity($deviceUuid, $metricsName, $fromString, $now, $function, 600);
    }

    public function getMetricsWithGranularity($deviceUuid, $metricsName, $fromString, $now, $function, $granularity){
        $ts = array();
        try {
            $result = self::$cw->getMetricStatistics(array(
                    'Namespace' => $deviceUuid,
                    'MetricName' => $metricsName,
                    'StartTime' => strtotime($fromString, $now),
                    'EndTime' => $now,
                    'Period' => $granularity,
                    'Statistics' => array($function),
            ));
            foreach ($result['Datapoints'] as $dp){
                $ts[(string)$dp['Timestamp']] = round($dp[$function], 0);
            }
            return $ts;
        } catch (Exception $e) {
            error_log("getMetricsWithGranularity".$e->getMessage());
        }
        return $ts;
    }
    
    
}


