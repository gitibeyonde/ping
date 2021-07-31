<?php


require_once(__ROOT__.'/classes/UserContext.php');

class UserFactory 
{

    private static $userRegistry = array();
    

    private function __construct()
    {
    }
    
    public static function getUser($user_name, $user_email)
    {
        if (isset(UserFactory::$userRegistry[$user_name] )){
            $user = UserFactory::$userRegistry[$user_name];
        }
        else {
            $user = new UserContext($user_name, $user_email);
            UserFactory::$userRegistry[$user_name] = $user;
            return $user;
        }
    }
    
}