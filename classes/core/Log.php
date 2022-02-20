<?php 

class Log {
    
    private $_level="2";
    private static $_trace=0;
    private static $_debug=1;
    private static $_info=2;
    private static $_warn=3;
    private static $_error=4;
    private static $_fatal=5;
    
    function __construct($level){
        if ($level == "trace"){
            $this->_level=0;
        }
        else if ($level == "debug"){
            $this->_level=1;
        }
        else if ($level == "info"){
            $this->_level=2;
        }
        else if ($level == "warn"){
            $this->_level=3;
        }
        else if ($level == "error"){
            $this->_level=4;
        }
        else if ($level == "fatal"){
            $this->_level=5;
        }
        else {
            throw new Exception("Unknow error level ".$level);
        }   
    }
    
    public function trace($s){
        if ($this->_level <= Log::$_trace){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            error_log("TRACE: ".$s."---".$caller['line']."::".basename($caller['file']));
        }
    }
    public function debug($s){
        if ($this->_level <= Log::$_debug){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            error_log("DEBUG: ".$s."---".$caller['line']."::".basename($caller['file']));
        }
    }
    public function info($s){
        if ($this->_level <= Log::$_info){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            error_log("INFO: ".$s."---".$caller['line']."::".basename($caller['file']));
        }
    }
    public function warn($s){
        if ($this->_level <= Log::$_warn){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            error_log("WARN: ".$s."---".$caller['line']."::".basename($caller['file']));
        }
    }
    public function error($s){
        if ($this->_level <= Log::$_error){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            error_log("ERROR: ".$s."---".$caller['line']."::".basename($caller['file']));
        }
    }
    public function fatal($s){
        if ($this->_level <= Log::$_fatal){
            $bt = debug_backtrace();
            $caller = array_shift($bt);
            error_log("FATAL: ".$s."---".$caller['line']."::".basename($caller['file']));
        }
    }
    
}

?>