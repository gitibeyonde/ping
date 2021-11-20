<?php
define ( '__ROOT__', dirname ( dirname ( __FILE__ ) ) );

require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');

const VERSION=5;

if (isset($_GET['veil']) && isset($_GET['uuid'])){
    $veil = $_GET['veil'];
    $uuid = $_GET['uuid'];
    $utils = new Utils();
    $myveil = $utils->token($uuid);
    if ($veil == $myveil){
        echo VERSION;
    }
}
echo 0;
?>
