<?php
class DeviceCert {
    public $uuid = null;
    public $public = null;
    public $private = null;
    public $passphrase = null;
    public $valid_till = null;
    public $created = null;
    private $db_connection = null;
    public $errors = array ();
    public $messages = array ();
    public function __construct() {
        $created = date ( "Y-m-d h:i:sa" );
    }
    private function databaseConnection() {
        // connection already opened
        if ($this->db_connection != null) {
            return true;
        } else {
            // create a database connection, using the constants from config/config.php
            try {
                $this->db_connection = new PDO ( 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS );
                return true;
            } catch ( PDOException $e ) {
                $this->errors [] = MESSAGE_DATABASE_ERROR;
                return false;
            }
        }
    }
    public function loadDeviceCert($uuid) {
        $cert = null;
        // if database connection opened
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare ( 'SELECT * FROM device_cert WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->setFetchMode ( PDO::FETCH_CLASS, 'DeviceCert' );
            $query_device->execute ();
            $cert = $query_device->fetch ();
        }
        return $cert;
    }
    public function deleteDeviceCert($uuid) {
        if ($this->databaseConnection ()) {
            // database query, getting all the info of the selected user
            $query_device = $this->db_connection->prepare ( 'delete FROM device_cert WHERE uuid = :uuid' );
            $query_device->bindValue ( ':uuid', $uuid, PDO::PARAM_STR );
            $query_device->execute ();
        }
    }
    function saveDeviceCert($uuid, $public, $private, $passphrase) {
        if ($this->databaseConnection ()) {
            $valid_till = date ( 'Y-m-d', strtotime ( "+365 days" ) );
            $sql = "insert into device_cert (uuid, public, private, passphrase, valid_till, created) values
                     ( '$uuid', '$public', '$private', '$passphrase', '$valid_till', now()) on duplicate key 
                         update public=VALUES(public), private=VALUES(private), passphrase=VALUES(passphrase), valid_till=VALUES(valid_till), created=VALUES(created);";
            $this->db_connection->exec ( $sql );
            $cert = new DeviceCert ();
            $this->uuid = $uuid;
            $this->public = $public;
            $this->private = $private;
            $this->passphrase = $passphrase;
            $this->created = date ( 'Y-m-d');
            $this->valid_till = $valid_till;
        }
    }
}
