<?php
// include the config
require_once (__ROOT__ . '/config/config.php');
require_once (__ROOT__ . '/libraries/password_compatibility_library.php');
require_once (__ROOT__ . '/libraries/aws.phar');
require_once(__ROOT__.'/config/config.php');

class SmsUtils
{

    private static $s3 = null;
    const bucket = 'data.simonline';

    private $db_connection = null;
    
    public $chatbot_nv = array("name", "callback_number", "address", "info", "appointment", "email", "help", "hello", "help_link", "map");
    
    private static $credentials = array (
            'version' => S3_VERSION,
            'key'    => S3_KEY,
            'secret' => S3_SECRET,
            'region' => "ap-south-1"
    );
    public function __construct()
    {
        if (self::$s3 == null) {
            self::$s3 = Aws\S3\S3Client::factory ( self::$credentials  );
        }
    }
    
 
    public function createChatbot($user_id, $name, $description, $personality)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into sms_chatbot(user_id, name, description, personality)  values (:user_id, :name, :description, :personality)');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':description', $description, PDO::PARAM_STR);
            $sth->bindValue(':type', $personality, PDO::PARAM_STR);
            //$sth->bindValue(':changedOn', date(DateTime::ATOM), PDO::PARAM_STR);
            $sth->execute();
            error_log("createChatbot Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    public function getChatbot($user_id)
    {
        $smst = array();
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select * from sms_chatbot where user_id=:user_id');
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("getChatbot Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
            while($obj =  $sth->fetch()){
                $smst[]=$obj;
            }
            return $smst;
        }
    }
    
    public function deleteChatbot($user_id, $chat_bot_id )
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from sms_chatbot where id=:id and user_id=:user_id');
            $sth->bindValue(':id', $chat_bot_id, PDO::PARAM_STR);
            $sth->bindValue(':user_id', $user_id, PDO::PARAM_STR);
            $sth->execute();
            error_log("deleteChatbot Error=" . implode(",", $sth->errorInfo()).$sms_template_id.$user_id);
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    ////////////////////////CHATBOT_NV//////////////////////////////////
    
    public function createChatbotNV($chat_bot_id, $name, $value)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('insert into chatbot_nv(chat_bot_id, name, value)  values (:chat_bot_id, :name, :value)');
            $sth->bindValue(':chat_bot_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->bindValue(':value', $value, PDO::PARAM_STR);
            $sth->execute();
            error_log("createChatbot Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
    public function getChatbotV($chat_bot_id, $name)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('select value from chatbot_nv where id=:chat_bot_id and name=:name');
            $sth->bindValue(':chat_bot_id', $user_id, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->execute();
            error_log("createChatbot Error=" . implode(",", $sth->errorInfo()));
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
            return $sth->fetch()[0];
        }
    }
    
    
    public function deleteChatbotNV($chat_bot_id, $name)
    {
        if ($this->databaseConnection()) {
            // database query, getting all the info of the selected user
            $sth = $this->db_connection->prepare('delete from chatbot_nv where id=:chat_bot_id and name=:name');
            $sth->bindValue(':chat_bot_id', $chat_bot_id, PDO::PARAM_STR);
            $sth->bindValue(':name', $name, PDO::PARAM_STR);
            $sth->execute();
            error_log("deleteChatbotNV Error=" . implode(",", $sth->errorInfo()).$sms_template_id.$user_id);
            if ( $sth->errorInfo()[0] != "0000"){
                $_SESSION['message'] = print_r($sth->errorInfo(), true);
            }
        }
    }
    
}
    
?>