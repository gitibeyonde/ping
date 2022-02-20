<?php
define ( '__ROOT__',  dirname(dirname(dirname ( __FILE__ ))));
require_once(__ROOT__ . '/classes/core/SqliteCrud.php');
require_once(__ROOT__ . '/classes/core/Encryption.php');
require_once(__ROOT__ . '/classes/core/Log.php');

$GLOBALS['log'] = new Log("info");

$crypt = new Encryption();

$str = "Hellow WOrld lrkwueq987341984hfskjh0-13-2k/.,.cz[pdp[wf[wep[fp][!#@#@%%^#^&%*&^&*(";
        
error_log($str);
        
$enc = $crypt->encrypt($str);

print_r($enc);

$str = $crypt->decrypt($enc[0], $enc[1]);

error_log($str);

?>