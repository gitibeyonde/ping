<?php

define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/classes/OneDo.php');


if (isset($_POST['url'])){
    $url=$_POST['url'];
    
    $od = new OneDo();
    $map =  $od->getMap($url);
    
    echo "1do.in/".$map;
}
?>

