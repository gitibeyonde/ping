<?php
require_once (__ROOT__ . '/classes/core/Log.php');
include_once(__ROOT__ . '/classes/utils/Mobile_detect.php');


class Utils {


    private $_log = null;

    public function __construct() {
        $this->_log = isset($_SESSION['log']) ? $_SESSION['log'] : $GLOBALS['log'];
    }



    public static function flatten($a){
        return "( ". SmsWfUtils::put_together($a, ", ", "(", ")")  . ")";
    }

    public static function join($a){
        return SmsWfUtils::put_together($a, '', '', '');
    }

}
