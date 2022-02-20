<?php
// include the config
require_once (__ROOT__ . '/config/config.php');
require_once (__ROOT__ . '/libraries/password_compatibility_library.php');
class Face {
    
    private $db_connection = null;
    
    public function __construct() {
    }
    private function databaseConnection() {
        // if connection already exists
        if ($this->db_connection != null) {
            return true;
        } else {
            try {
                $this->db_connection = new PDO ( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS );
                return true;
            } catch ( PDOException $e ) {
                $this->errors [] = MESSAGE_DATABASE_ERROR . $e->getMessage ();
            }
        }
        return false;
    }
    public function saveFace($uuid, $user_name, $face_name, $alert_id, $url) {
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ( 'insert into named_face(alert_id, face_name, uuid, user_name, image, created)  values(:alert_id, :face_name, :uuid, :user_name, :url, now())' );
            $sth->bindValue ( ':alert_id', $alert_id, PDO::PARAM_STR );
            $sth->bindValue ( ':face_name', $face_name, PDO::PARAM_STR );
            $sth->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $sth->bindValue ( ':user_name', $user_name, PDO::PARAM_STR );
            $sth->bindValue ( ':url', $url, PDO::PARAM_STR );
            $sth->execute ();
            //error_log ( "saveFace Error=" . implode ( ",", $sth->errorInfo () ) );
        }
    }
    public function getFaceNameForAlert($alert_id) {
        $name = null;
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ( 'select face_name from named_face where alert_id = :alert_id' );
            $sth->bindValue ( ':alert_id', $alert_id, PDO::PARAM_STR );
            $sth->execute ();
            $name = $sth->fetch () [0];
            //error_log ( "Face name =" . $name );
        }
        return $name;
    }
    public function getFaces($user_name, $limit) {
        $result = array ();
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ( 'select * from named_face where user_name = :user_name order by time asc limit ' . $limit );
            $sth->bindValue ( ':user_name', $user_name, PDO::PARAM_STR );
            $sth->execute ();
            $result = $sth->fetchAll ();
        }
        return $result;
    }    
    public function deleteTrainedFaces($user_name) {
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ( 'delete from named_face where user_name = :user_name' );
            $sth->bindValue ( ':user_name', $user_name, PDO::PARAM_STR );
            $sth->execute ();
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare ( 'delete from face_recog where user_name = :user_name' );
            $sth->bindValue ( ':user_name', $user_name, PDO::PARAM_STR );
            $sth->execute ();
        }
    }
    
}
?>
