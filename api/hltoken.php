<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/classes/Utils.php');

    
$utils = new Utils();
$hltoken=$utils->getConfig('hltoken');

echo $hltoken;
    
?>

