<?php
define ( '__ROOT__', dirname ( dirname ( __FILE__ ) ) );

require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');

const VERSION=6;

if (isset($_GET['uuid'])){
    $uuid = $_GET['uuid'];
    $utils = new Utils();
    $veil = $utils->token($uuid);
    echo VERSION."-".$veil;
}
?>
