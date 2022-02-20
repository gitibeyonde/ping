<?php
class Sqlite  {

    const ROW_HTML_SEPARATOR="<br/>";
    const ROW_TEXT_SEPARATOR="\n";
    const COL_HTML_SEPARATOR="&nbsp;&nbsp;&nbsp;&nbsp;";
    const COL_TEXT_SEPARATOR="  ";

    const FSIZE_MB = 1048576; // bytes in 1MB
    const IMAX     = 2147483647;
    static protected $qpool  = array(
            'get_tables' => "select group_concat(tbl_name, ',') from sqlite_master where type = 'table'",
            'row_count' => "SELECT COUNT(*) as count FROM '%s'",
            'p_insert'   => "insert into %s (%s) values (%s)",
            't_exists'   => "select 1 = (select count(*) from sqlite_master where type = 'table' and name = '%s')",
            't_info'     => "pragma table_info('%s')",
            't_data'     => "select rowid,* from %s",
            't_data_no_row_id'     => "select * from %s",
            't_delete'  => "drop table '%s'",
            't_create'  => "SELECT sql FROM sqlite_master WHERE name ='%s'"
    );

    protected static $DB="db";
    protected static $AUD="aud";
    protected static $UD="ud";

    protected $_sqlite3 = null;
    protected $log=null;
    protected $filename=null;
    public $row_separator=null;
    public $col_seprator=null;


    // User id is the first param
    function __construct($uid, $bid, $ext){
        if (!is_numeric($uid)){
            throw new Exception("Bad user id ".$uid);
        }
        $this->log = isset($_SESSION['log']) ? $_SESSION['log'] : $GLOBALS['log'];
        $dir =  __ROOT__.'/data/'.$uid;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        if ($uid!=null && $ext == self::$AUD){
            $this->filename = $dir.'/db-'.$uid.".".$ext;
        }
        else if ($bid!=null && $uid !=null){
            $this->filename = $dir.'/db-'.$uid.".".$bid.".".$ext;
        }
        else {
            $_SESSION['message'] = "FATAL: Parameter exception ";
            return;
        }

        if (file_exists($this->filename) && filesize( $this->filename) > self::FSIZE_MB){
            $_SESSION['message'] = $bid." DB exceeds 1MB, it will come readonly if the size exceeds 2MB";
        }
        $this->_sqlite3 = new SQLite3($this->filename, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
        $this->_sqlite3->enableExceptions(true);
        $this->_sqlite3->busyTimeout(5000);
    }

    public static function getBotKBFile($user_id, $bot_id){
        $dir =  __ROOT__.'/data/'.$user_id;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = $dir.'/db-'.$user_id.".".$bot_id.".".self::$DB;
        error_log(" KB file = ".$file);
        return $file;
    }

    public static function getUserDBFile($user_id, $bot_id){
        $dir =  __ROOT__.'/data/'.$user_id;
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $file = $dir.'/db-'.$user_id.".".$bot_id.".".self::$UD;
        error_log("Data file = ".$file);
        return $file;
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
    public function t_query ($q, $mode=1) {
        if (!is_null($q)) {
            $this->log->debug("mode = $mode, Sql=".$q ." from ".$this->filename);
            $results = $this->_sqlite3->query($q);
            if (!$results){
                $this->log->debug("Error Message=".$this->_sqlite3->lastErrorMsg());
                return null;
            }
            $data=array();
            while ($res = $results->fetchArray($mode))
            {
                //error_log("Result=".print_r($res, true));
                $data[]=$res;
            }
            return $data;
        }
        else {
            return null;
        }
    }
    public function multiple_rows_cols($q){
        $results = $this->t_query($q, 1);
        $data=array();
        foreach($results as $val){
            $data[] = $val;
        }
        return $data;
    }
    public function multiple_rows($q){
        $results = $this->t_query($q, 1);
        $data=array();
        foreach($results as $val){
            $data[] = $val;
        }
        return $data;
    }
    public function value_list($q){
        $results = $this->t_query($q, 2);
        if (!$results)return null;
        $data = array();
        foreach($results as $val){
            $data[] = $val[0];
        }
        return $data;
    }
    public function single_value($q){
        $results = $this->value_list($q);
        return $results[0];
    }

    public function t_exists($tname) {
        return $this->_sqlite3->querySingle(sprintf(self::$qpool['t_exists'], self::esc($tname)), false);
    }

    public function ls () {
        $tables = $this->_sqlite3->querySingle(self::$qpool['get_tables'], false);
        return $tables ? explode(",", $tables) : array();
    }

    public function row_count ($table) {
        $col_count = $this->_sqlite3->querySingle(sprintf(self::$qpool['row_count'], $table), false);
        return $col_count;
    }
    public function schema ($tname = null) {
        if (!is_null($tname)) {
            $columns = array();
            $results = $this->_sqlite3->query(sprintf(self::$qpool['t_info'], $tname));
            $data= array();
            while ($res= $results->fetchArray(1))
            {
                array_push($data, $res);
            }
            return $data;
        }
        else {
            return null;
        }
    }


    public function t_columns_types ($tname = null) {
        if (!is_null($tname)) {
            $results = $this->_sqlite3->query(sprintf(self::$qpool['t_info'], $tname));
            $columns = array();
            while ($res= $results->fetchArray(1))
            {
                $columns[$res['name']] = $res['type'];
            }
            return $columns;
        }
        else {
            return null;
        }
    }

    public function t_columns ($tname = null) {
        if (!is_null($tname)) {
            $results = $this->_sqlite3->query(sprintf(self::$qpool['t_info'], $tname));
            $data= array();
            while ($res= $results->fetchArray(1))
            {
                array_push($data, $res['name']);
            }
            return $data;
        }
        else {
            return null;
        }
    }

    public function t_data ($tname = null) {
        if (!is_null($tname)) {
            $columns = array();
            $results = $this->_sqlite3->query(sprintf(self::$qpool['t_data'], $tname));
            $data= array();
            while ($res= $results->fetchArray(1))
            {
                array_push($data, $res);
            }
            return $data;
        }
        else {
            return null;
        }
    }
    public function t_data_no_row_id ($tname = null) {
        if (!is_null($tname)) {
            $columns = array();
            $results = $this->_sqlite3->query(sprintf(self::$qpool['t_data_no_row_id'], $tname));
            $data= array();
            while ($res= $results->fetchArray(1))
            {
                array_push($data, $res);
            }
            return $data;
        }
        else {
            return null;
        }
    }

    public function t_delete ($tname = null) {
        if (!is_null($tname)) {
            $columns = array();
            $result = $this->_sqlite3->query(sprintf(self::$qpool['t_delete'], $tname));
            return $result;
        }
        else {
            return null;
        }
    }
    public function t_insert ($tname, array $input = null) { // input(s)[]
        if ($input) {

            $tname = self::esc($tname);

            $colnames = $this->t_columns($tname);
            $this->log->trace(print_r($colnames, true));
            $params   = array();

            error_log("Column Names".print_r($colnames, true));
            foreach ($colnames as $cname)
                $params[(':'. self::rnd_())] = $cname;

                $paramkeys = array_keys($params);
                $inserts   = array_flip($paramkeys);
                $this->log->trace("ParamsKey".print_r($paramkeys, true));
                $this->log->trace("Inserts". print_r($inserts, true));

                $qprep = sprintf(self::$qpool['p_insert'],
                        $tname, implode(',', $colnames), implode(',', $paramkeys));

                $qs = $this->_sqlite3->prepare($qprep);
                $this->log->trace("Prepare".print_r($qs, true));

                foreach ($params as $pname => $cname) {
                    $this->log->trace("Pname".print_r($pname, true));
                    $qs->bindParam(substr($pname, 1), $inserts[$pname]);
                }

                foreach ($input as $data) {

                    foreach ($params as $pname => $cname)
                        $inserts[$pname] = isset($data[$cname]) ? $data[$cname] : null;

                        $qs->execute();
                }

                $qs->close();
        }

        return $this;
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
    public function insert_map($tname, array $nv_pairs = null) {
        error_log("Input values=".print_r($nv_pairs, true));
        if (is_array($nv_pairs)) {
            $tname = self::esc ( $tname );
            $colnames = $this->t_columns ( $tname );
            //$colnames = preg_filter('/^/', "'", $colnames);
            //$colnames = preg_filter('/$/', "'", $colnames);
            error_log("Input values=".print_r($colnames, true));
            $paramvalues = array();
            $paramname = array();
            foreach ( $colnames as $cname ) {
                $val =  $this::esc($nv_pairs[$cname]);
                $paramvalues[$cname] = "'".self::esc($val) ."'";
                $paramname[] = "'".self::esc($cname)."'";
            }
            $q = sprintf ( self::$qpool ['p_insert'], $tname, implode ( ',', $paramname ), implode ( ',', $paramvalues ));
            $this->log->trace ( "Query=".$q);
            return $this->query($q);
        }
        return null;
    }

    public function createTable($table, $col, $type){

        try {
            $this->t_crtinsupd("DROP TABLE IF EXISTS ".$table.";");
            $create_stmt = "CREATE TABLE IF NOT EXISTS ".$table." (";
            for($i=0; $i<count($col); $i++){
                $create_stmt .= "'".$col[$i]."' ". $type[$i] .", ";
            }
            $create_stmt = substr($create_stmt, 0, strlen($create_stmt) - 2);
            $create_stmt .= ");";
            error_log("create stmt=".$create_stmt);
            $this->t_crtinsupd($create_stmt);
        }
        catch(Exception $e){
            throw new Exception("Error encountered while creating schema ".$e->getMessage());
        }
    }

    ////STATIC FUNCTION

    static public function esc ($s) {
        return SQLite3::escapeString((string) $s);
    }
    static protected function rnd_ ($prefix = 'b') {
        return $prefix . base_convert(mt_rand(1, self::IMAX), 10, 36);
    }

}

?>