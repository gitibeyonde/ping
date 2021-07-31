<?php

class MotionFile
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

    public function __construct($filename)
    {
        $this->image = $filename;
        $this->year=substr($filename, 7, 4);
        $this->month=substr($filename, 11, 2);
        $this->day=substr($filename, 13, 2);
        $this->hour=substr($filename, 15, 2);
        $this->minute=substr($filename, 17, 2);
        $this->second=substr($filename, 19, 2);

        $this->time=$this->hour."_".$this->minute."_".$this->second;
        $this->datetime=$this->day."/".$this->month."/".$this->year." - ".$this->hour.":".$this->minute.":".$this->second;
        $this->directory=$this->year."/".$this->month."/".$this->day;
    }
    
}
