<?php
    session_start();
    if (isset($_GET["ip"])){
       $_SESSION["ip"]=$_GET["ip"];
    }
    else {
       unset($_SERVER['SERVER_ADDR']);
    }
?>
