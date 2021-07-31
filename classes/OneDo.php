<?php
require_once (__ROOT__ . '/config/config.php');

class OneDo
{

    private static $dv;
    private $db_connection = null;

    public function __construct()
    {
        OneDo::$dv = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-.~_');
    }
    
    private function databaseConnection()
    {
        // if connection already exists
        if ($this->db_connection != null) {
            return true;
        } else {
            try {
                $this->db_connection = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                return true;
            } catch (PDOException $e) {
                $this->errors[] = MESSAGE_DATABASE_ERROR . $e->getMessage();
            }
        }
        return false;
    }
    
    private function getNext($cur)
    {
        $cv = str_split($cur);
        for ($i = count($cv) - 1; $i > - 1; $i --) {
            if ($cv[$i] == "_") {
                if ($i == 0) {
                    $cv = array_fill(0, count($cv) + 1, 0);
                    return implode("", $cv);
                } else {
                    if ($cv[$i - 1] != '_') {
                        $cv[$i - 1] = OneDo::$dv[array_search($cv[$i - 1], OneDo::$dv) + 1];
                        for ($j = $i; $j < count($cv); $j ++) {
                            $cv[$j] = 0;
                        }
                        return implode("", $cv);
                    }
                }
            } else {
                $cv[$i] = OneDo::$dv[array_search($cv[$i], OneDo::$dv) + 1];
                if ($i == 0) {
                    $next = array_fill(0, count($cv), 0);
                    $next[0] = $cv[$i];
                    $cv = $next;
                }
                return implode("", $cv);
            }
        }
    }
    
    
    private function getLastIndex(){
        $result=array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select id from url_map order by id desc limit 1');
            $sth->execute();
            $result = $sth->fetch()[0];
            error_log("logSms Error=".implode(",", $sth->errorInfo()));
            error_log("Result=".$result);
            if ($result==null){
                return "0";
            }
        }
        return $result;
    }
    
    public function getMap($url){
        $li = $this->getLastIndex();
        error_log("Li=".$li);
        $id = $this->getNext($li);
        error_log("Id=".$id);
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into url_map(id, url) values(:id, :url)');
            $sth->bindValue(':id',  $id, PDO::PARAM_STR);
            $sth->bindValue(':url', $url, PDO::PARAM_STR);
            $sth->execute();
            error_log("logSms Error=".implode(",", $sth->errorInfo()));
        }
        return $id;
    }
    
    public function getUrl($id){
        $result=array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select url from url_map where id=:id');
            $sth->bindValue(':id',  $id, PDO::PARAM_STR);
            $sth->execute();
            $result = $sth->fetch()[0];
            error_log("logSms Error=".implode(",", $sth->errorInfo()));
            error_log("Result=".$result);
        }
        return $result;
        
    }
    
}

?>