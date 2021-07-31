<?php
define ( '__ROOT__',  dirname ( __FILE__ ));

require_once(__ROOT__.'/classes/AlertRaised.php');

class AlertRaisedTest
{
    
    public static function generateAlert(){
        $ar = new AlertRaised();
        
        $ar->notifyMotion('4d6711e1', "MOTION", "4d6711e1/2018/12/07/08_04_18/7RfVoj.mp4", "", new DateTime('now'));
        $ar->notifyTempAndHumidity('4d6711e1', 100, 100, new DateTime('now'));
        $ar->notifyTempAndHumidity('31b9bbe7', 100, 100, new DateTime('now'));
        $ar->notifyTempAndHumidity('3729a83d', 100, 100, new DateTime('now'));
        $ar->notifyTempAndHumidity('d97080b2', 100, 100, new DateTime('now'));
        $ar->notifyTempAndHumidity('5128830a', 100, 100, new DateTime('now'));
        $ar->notifyTempAndHumidity('3729a83d', 100, 100, new DateTime('now'));
    }
}

AlertRaisedTest::generateAlert();

?>

