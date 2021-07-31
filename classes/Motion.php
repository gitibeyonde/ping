<?php

class Motion
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
    

    public function __construct($filename)
    {
        $this->image = $filename;
        $vals=explode('/', $filename);
        $this->uuid=$vals[0];
        $this->year=$vals[1];
        $this->month=$vals[2];
        $this->day=$vals[3];
        $time=$vals[4];
        $this->rand=$vals[5];
        $this->hour=substr($time, 0, 2);
        $this->minute=substr($time, 3, 2);
        $this->second=substr($time, 6, 2);

        $this->time=$this->hour."_".$this->minute."_".$this->second;
        $this->datetime=$this->day."/".$this->month."/".$this->year." - ".$this->hour.":".$this->minute.":".$this->second;
        $this->directory=$this->year."/".$this->month."/".$this->day;
    }
    
    
   
}
