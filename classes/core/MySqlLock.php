<?php
require_once (__ROOT__ . '/classes/core/Mysql.php');

class MySqlLock extends Mysql {
    
    public function lock($name){
        return  $this->selectOne (sprintf("SELECT GET_LOCK('%s',10);", $name) );
    }
    
    public function isLocked($name){
        return  $this->selectOne (sprintf("SELECT IS_FREE_LOCK()('%s');", $name) );
    }
    
    
    public function unlock($name){
        return  $this->selectOne (sprintf("SELECT RELEASE_LOCK()('%s');", $name) );
    }
    
    
    public function isUsed($name){
        return  $this->selectOne (sprintf("SELECT IS_USED_LOCK()('%s');", $name) );
    }
}