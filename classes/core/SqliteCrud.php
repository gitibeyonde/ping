<?php
require_once (__ROOT__ . '/classes/wf/SmsWfUtils.php');
class SqliteCrud {
    protected $_sqlite3 = null;
    protected $log = null;
    protected $filename = null;
    const IMAX = 2147483647;
    protected static $qpool = array ('get_tables' => "select group_concat(tbl_name, ',') from sqlite_master where type = 'table'",
            'row_count' => "SELECT COUNT(*) as count FROM '%s'",
            'p_insert' => "insert into %s (%s) values (%s)",
            't_exists' => "select 1 = (select count(*) from sqlite_master where type = 'table' and name = '%s')",
            't_info' => "pragma table_info('%s')",
            't_data' => "select rowid,* from %s",
            't_data_no_row_id' => "select * from %s",
            't_drop' => "drop table '%s'",
            't_delete' => "delete from %s where rowid='%s'",
            't_create' => "SELECT sql FROM sqlite_master WHERE name ='%s'" 
    );
    
    // User id is the first param
    function __construct($uid) {
        if (!is_numeric($uid)){
            throw new Exception("Bad user id ".$uid);
        }
        $this->log = isset($_SESSION['log']) ? $_SESSION['log'] : $GLOBALS['log'];
        $dir =  __ROOT__.'/data/'.$uid;
        
        if (! file_exists ( $dir )) {
            mkdir ( $dir, 0777, true );
        }
        $this->filename = $dir.'/db-'.$uid.".sqlitemgr.db";
        $this->_sqlite3 = new SQLite3 ( $this->filename, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
        $this->_sqlite3->enableExceptions ( false );
        $this->log->debug ( "File =" . $this->filename );
    }
    public function ls() {
        $tables = $this->_sqlite3->querySingle ( self::$qpool ['get_tables'], false );
        return $tables ? explode ( ",", $tables ) : array ();
    }
    public function row_count($table) {
        $col_count = $this->_sqlite3->querySingle ( sprintf ( self::$qpool ['row_count'], $table ), false );
        return $col_count;
    }
    public function schema($tname = null) {
        if (! is_null ( $tname )) {
            $columns = array ();
            $results = $this->_sqlite3->query ( sprintf ( self::$qpool ['t_info'], $tname ) );
            $data = array ();
            while ( $res = $results->fetchArray ( 1 ) ) {
                array_push ( $data, $res );
            }
            return $data;
        } else {
            return null;
        }
    }
    public function data($tname = null) {
        if (! is_null ( $tname )) {
            $columns = array ();
            $results = $this->_sqlite3->query ( sprintf ( self::$qpool ['t_data'], $tname ) );
            $data = array ();
            while ( $res = $results->fetchArray ( 1 ) ) {
                array_push ( $data, $res );
            }
            return $data;
        } else {
            return null;
        }
    }
    public function query($q) {
        if ($q != null) {
            $q = trim ( $q );
            $select = (strpos ( strtolower ( $q ), "select" ) !== false) || (strpos ( strtolower ( $q ), "pragma" ) !== false);
            if ($select) {
                error_log ( "Select query" );
            }
            $this->log->trace ( "\n------------Sql=" . $q );
            $results = $this->_sqlite3->query ( $q );
            if ($results == null) {
                $this->log->trace ( "NULL Error Message=" . $this->_sqlite3->lastErrorMsg () );
                return false;
            } else if ($results == false) {
                $this->log->trace ( "False Error Message=" . $this->_sqlite3->lastErrorMsg () );
                return false;
            } else if ($select && $results instanceof SQLite3Result) { // fetchArray when not select re-executes the query !!!
                $data = array ();
                while ( $res = $results->fetchArray ( 1 ) ) {
                    $data [] = $res;
                }
                return $data;
            } else {
                return true;
            }
        } else {
            return null;
        }
    }
    public function insert($tname, array $input = null) { // input(s)[]
        //error_log("Input values=".print_r($input, true));
        if (is_array($input)) {
            $tname = self::esc ( $tname );
            $colnames = $this->t_columns ( $tname );
            $paramvalues = array();
            foreach ( $input as $data ) {
                foreach ( $colnames as $cname ) {
                    $paramvalues[$cname] = "'". $this::esc($data[$cname])."'";
                }
                $q = sprintf ( self::$qpool ['p_insert'], $tname, implode ( ',', $colnames ), implode ( ',', $paramvalues ));
                $this->log->trace ( "Query=".$q);
                $this->query($q);
            }
            return true;
        }
        return null;
    }
    
    public function insert_map($tname, array $nv_pairs = null) { 
        error_log("Input values=".print_r($nv_pairs, true));
        if (is_array($nv_pairs)) {
            $tname = self::esc ( $tname );
            $colnames = $this->t_columns ( $tname );
            error_log("Input values=".print_r($colnames, true));
            $paramvalues = array();
            foreach ( $colnames as $cname ) {
                $val =  $this::esc($nv_pairs[$cname]);
                $paramvalues[$cname] = "'".$val ."'";
            }
            $q = sprintf ( self::$qpool ['p_insert'], $tname, implode ( ',', $colnames ), implode ( ',', $paramvalues ));
            $this->log->trace ( "Query=".$q);
            return $this->query($q);
        }
        return null;
    }
    
    public function delete($tname, $rowid){
        if (! is_null ( $tname )) {
            $results = $this->_sqlite3->query ( sprintf ( self::$qpool ['t_delete'], $tname, $rowid ) );
            return $results;
        } else {
            return null;
        }
    }
    public function t_columns($tname = null) {
        if (! is_null ( $tname )) {
            $results = $this->_sqlite3->query ( sprintf ( self::$qpool ['t_info'], $tname ) );
            $data = array ();
            while ( $res = $results->fetchArray ( 1 ) ) {
                array_push ( $data, $res ['name'] );
            }
            return $data;
        } else {
            return null;
        }
    }
    public function t_crtinsupd ($q = null) {
        if (!is_null($q)) {
            $this->log->debug("Sql=".$q);
            return $this->_sqlite3->query($q);
        }
        else {
            return false;
        }
    }
    
    public function loadWFData($table, $filename){
        //$fkey= $this->user_id . "/" . $name . "/" .basename(basename($_FILES["fileToUpload"]["name"]));
        //$filename = "/Users/aprateek/Desktop/sms_cat_healthcare_speciality.csv";
        error_log("Filenaem=".$filename);
        $pd = array_map('str_getcsv', file($filename));
        $headers=array();
        foreach ($pd[0] as $head){
            $headers[]=trim($head);
        }
        if (count($headers) == 0){
            throw new Exception("The csv file has missing or malformed header");
        }
        error_log("Head=".SmsWfUtils::flatten($headers));
        
        try {
            $this->t_crtinsupd("DROP TABLE IF EXISTS ".$table.";");
            $create_stmt = "CREATE TABLE IF NOT EXISTS ".$table." (";
            foreach($headers as $head){
                $create_stmt .= self::esc($head)." TEXT ,";
            }
            $create_stmt = substr($create_stmt, 0, strlen($create_stmt) -1);
            $create_stmt .= ");";
            error_log("create stmt=".$create_stmt);
            $this->t_crtinsupd($create_stmt);
        }
        catch(Exception $e){
            throw new Exception("Error encountered while creating schema ".$e->getMessage());
        }
        
        try {
            foreach(array_splice($pd, 1) as $row){
                $insert_stmt="insert into ".$table." values (";
                $data = array();
                for ($i=0; $i<count($pd[0]);$i++){
                    $header = self::esc(trim($pd[0][$i]));
                    $data[$header] = self::esc(trim($row[$i]));
                    $insert_stmt .= "'".self::esc(trim($row[$i]))."',";
                }
                $insert_stmt = substr($insert_stmt, 0, strlen($insert_stmt) -1);
                $insert_stmt .= ");";
                error_log("insert_stmt=".$insert_stmt);
                $this->t_crtinsupd($insert_stmt);
            }
        }
        catch(Exception $e){
            throw new Exception("Error encountered while saving data ".$e->getMessage());
        }
    }
    
    // //STATIC FUNCTION
    static public function esc($s) {
        return SQLite3::escapeString ( ( string ) $s );
    }
    static protected function rnd_($prefix = 'b') {
        return $prefix . base_convert ( mt_rand ( 1, self::IMAX ), 10, 36 );
    }
}

?>