<?php

class RecordFile
{
    public $uuid = null;
    public $year = null;
    public $month = null;
    public $day = null;
    public $hour = null;
    public $minute = null;
    public $second = null;
    public $datetime = null;
    public $time = null;
    public $directory = null;
    public $image = null;
    public $rand = null;
    
    //e44eb474/record/2018-04-04-09-08-08/2018/04/04/09_08_08/J6pWYq.jpg
    public function __construct($filename)
    {
        $this->image = $filename;
        $vals=explode('/', $filename);
        $this->uuid=$vals[0];
        $this->year=$vals[3];
        $this->month=$vals[4];
        $this->day=$vals[5];
        $time=$vals[6];
        $this->rand=$vals[7];
        $this->hour=substr($time, 0, 2);
        $this->minute=substr($time, 3, 2);
        $this->second=substr($time, 6, 2);
        
        $this->time=$this->hour."_".$this->minute."_".$this->second;
        $this->datetime=$this->day."/".$this->month."/".$this->year." - ".$this->hour.":".$this->minute.":".$this->second;
        $this->directory=$this->year."/".$this->month."/".$this->day;
    }
    
    
    
}
