<?php

require_once(__ROOT__.'/classes/Aws.php');



class Record {
    
    public $uuid=-1;
    public $videoMode=-1;
    public $aws;
    
    public function __construct($uuid, $videoMode)
    {
        $this->uuid = $uuid;
        $this->videoMode = $videoMode;
        $this->aws = new Aws();
    }
    
    public function getRecording(){
        return $this->aws->loadRecordings();
    }
    
    public function listRecordings(){
        return $this->aws->listRecordings($this->uuid);
    }
    
}

?>