<?php

class NetUtils
{

    const CMDCHUNK=64;
    const PORT_START=20000;
    const PORT_END=65534;
    const IMGCHUNK=1450;
    const broker_port=5020;
    private $broker_address;
    private $sock;
    private $my_address;
    private $my_port;
    private $my_username;
    private $my_uuid;
    
    public $err_img;
    
    public function __construct($username, $uuid, $port)
    {
        if (!isset($uuid)){
            throw new Exception(" Invalid uuid ".$uuid);
        }
        $this->my_username = $username;
        $this->my_uuid = $uuid;
        $this->broker_address = gethostbyname('broker.ibeyonde.com');
        //error_log("Broker address=".$this->broker_address);
        $this->my_address = $_SERVER['SERVER_ADDR'];
        
        //$bignum = hexdec( substr(md5($username.$uuid), 0, 15) );
        //$smallnum = $bignum % 45534;
        //$this->my_port = abs($smallnum) + NetUtils::PORT_START;
        //error_log("Port=".$this->my_port);
        $this->my_port = $port;
        //error_log("Port=".$this->my_port);
        
        $this->err_img = file_get_contents($_SERVER["DOCUMENT_ROOT"] ."/img/no_update_error.png");
    }
    
    
    public function getSocket(){
        if ($this->sock != null){
            socket_close($this->sock);
        }
        if (($this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP)) === false) {
            throw new Exception("socket_create() failed: reason: " . socket_strerror(socket_last_error()));
        }
        
        if (socket_set_option($this->sock, SOL_SOCKET, SO_REUSEADDR, 1) == false) {
            throw new Exception("socket_set_option() failed: reason: " . socket_strerror(socket_last_error($this->sock)));
        }
        
        if (socket_bind($this->sock, 0, intval($this->my_port)) === false) {
            throw new Exception("socket_bind() failed: reason: " . socket_strerror(socket_last_error($this->sock)));
        }
        socket_set_option($this->sock, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 4, 'usec' => 0));
        socket_set_option($this->sock, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 2, 'usec' => 0));
    }
    
    public function register(){
        $this->getSocket();
        //error_log("Registering ". substr($this->my_username.$this->my_uuid, 0, 30));
        $this->sendCommandBroker("REGISTER:". substr($this->my_username.$this->my_uuid, 0, 30) . ":".$this->addr2bytes($this->my_address, $this->my_port));
        list($cmd, $data) = $this->recvCommandBroker();
        //list($address, $port) = $this->bytes2address($data);
        //error_log('Register address='.$address.' and port='.$port.' cmd='.$cmd);
    }

    public function initiate($quality){
        if ($quality == Quality::A){
           $this->sendCommandBroker(Quality::A.":".$this->my_uuid.":");
        }
        else if ($quality == Quality::B){
          $this->sendCommandBroker(Quality::B.":".$this->my_uuid.":");
        }
        else if ($quality == Quality::C) {
           $this->sendCommandBroker(Quality::C.":".$this->my_uuid.":");
        }
        else if ($quality == Quality::D) {
           $this->sendCommandBroker(Quality::D.":".$this->my_uuid.":");
        }
        else {
           $this->sendCommandBroker(Quality::E.":".$this->my_uuid.":");
        }
    }
    
    public function addr2bytes($address, $port){
        return pack('Nn', ip2long($address), intval($port));
    }
    
    public function bytes2address($data){
        $array = unpack('N1address/n1port', $data);
        return array(long2ip($array['address']), $array['port'] );
    }
    
    public function sendCommandBroker($cmdbuff){
        //error_log('sendCommandBroker Sending '.$cmdbuff." broker address".$this->broker_address. "broker port=".NetUtils::broker_port);
        $len = strlen($cmdbuff);
        if ($len > NetUtils::CMDCHUNK){
            error_log("sendCommandBroker undesired size ".$len." buff=".$cmdbuff);
        }
        if (socket_sendto( $this->sock , $cmdbuff ,$len , MSG_EOF, $this->broker_address , NetUtils::broker_port ) == false){
            throw new Exception("socket_sendto() failed: reason: " . socket_strerror(socket_last_error($this->sock)));
        }
    }
    
    public function recvCommandBroker(){
        $buf='';
        if (socket_recvfrom($this->sock, $buf, NetUtils::CMDCHUNK, MSG_WAITALL, $this->broker_address, $this->my_port) == false){
            throw new Exception("socket_recvfrom() failed: reason: " . socket_strerror(socket_last_error($this->sock)));
        }
        //error_log('Receiving '.$buf);
        return explode(":", $buf);
    }
    
    public function recvCommandPeer($from){
        $buf='';
        if (socket_recvfrom($this->sock, $buf, NetUtils::CMDCHUNK, MSG_WAITALL, $from, $this->my_port) == false){
            throw new Exception("socket_recvfrom() failed: reason: " . socket_strerror(socket_last_error($this->sock)));
        }
        //error_log('Receiving '.$buf);
        return explode(":", $buf);
    }
    
    public function recvAllPeer($size, $from){
        $img='';
        $remaining = $size;
        while ($remaining > 0){
            if (socket_recvfrom($this->sock, $buf, $remaining, MSG_WAITALL, $from, $this->my_port) == false){
                throw new Exception("socket_recvfrom() failed: reason: " . socket_strerror(socket_last_error($this->sock)));
            }
            $img .= $buf;
            $remaining -= strlen($buf);
        }
        //error_log('Received All remaining '.$remaining);
        return $img;
    }
    
    public function sendActionBroker($uuid, $action, $data){
        $this->getSocket();
        $this->sendCommandBroker("ACTION:".$uuid.":".base64_encode($action.":".$data));
    }
    
    public function getTimeDeltaForLastPing(){
        $this->sendCommandBroker("TIME:".$this->my_uuid .":");
        list($cmd, $hour, $minute, $second) = $this->recvCommandBroker();
        //error_log("Time is $hour, $minute, $second");
        if ($hour==-1) {
            return PHP_INT_MAX ;
        }
        else {
           return (int)($hour*3600 + $minute*60 + $second);
        }
    }
    
    public function close(){
        socket_close($this->sock);
    }
    
    
}

     
