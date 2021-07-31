<?php

require_once(__ROOT__.'/libraries/aws.phar');
use Aws\Common\Enum\Region;
use Aws\Ses\SesClient;


class EmailUtils
{
    private $db_connection            = null;
    public  $errors                   = array();
    public  $messages                 = array();
    private $client                   = null;

    public function __construct()
    { 
        $this->client = SesClient::factory(array(
                'version' => SES_VERSION,
                'key'    => SES_KEY,
                'secret' => SES_SECRET,
                'region' => SES_REGION
        ));
    }
    
    private function databaseConnection()
    {
        // connection already opened
        if ($this->db_connection != null) {
            return true;
        } else {
            // create a database connection, using the constants from config/config.php
            try {
                $this->db_connection = new PDO('mysql:host='. DB_HOST .';dbname='. DB_NAME . ';charset=utf8', DB_USER, DB_PASS);
                return true;
            } catch (PDOException $e) {
                $this->errors[] = MESSAGE_DATABASE_ERROR;
                return false;
            }
        }
    }

    public function sendEmailAlert($id, $destination, $subject, $body)
    {
         error_log("Email ".$destination.",".$subject. " Dest=". $body);
         $msg_id=0;
         $msg = array();
         $msg['Source'] = "no_reply@ibeyonde.com";
         $msg['Destination']['ToAddresses'][] = $destination;
         $msg['Message']['Subject']['Data'] = $subject;
         $msg['Message']['Subject']['Charset'] = "UTF-8";
         $msg['Message']['Body']['Html']['Data'] = $body;
         $msg['Message']['Body']['Html']['Charset'] = "UTF-8";
         try{
            $result = $this->client->sendEmail($msg);
            $msg_id = $result->get('MessageId');
         } catch (Exception $e) {
            error_log($e->getMessage());
         } 
         return $msg_id;
    }

    public function emailAlert($userid, $uuid, $email, $alert_url)
    {
    }
}
?>