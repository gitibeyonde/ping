<?php 


class Encryption {
    protected static $CRYPT_PASSWORD = '5e2d6556a91e54811970723b83f96fff8887129f6dccb2d0';
    protected static $KEY_SIZE = 10;
    
    public static function encrypt($str){
        $ciphertext = openssl_encrypt($str, "AES-128-ECB", Encryption::$CRYPT_PASSWORD);
        $encoded = base64_encode($ciphertext);
        return array(substr($encoded, 0, Encryption::$KEY_SIZE), substr($encoded, Encryption::$KEY_SIZE));
    }
    
    public static function decrypt($key, $str){
        $str = $key.$str;
        $ciphertext = base64_decode($str);
        $plaintext = openssl_decrypt($ciphertext, "AES-128-ECB", Encryption::$CRYPT_PASSWORD);
        return $plaintext;
    }
}

?>