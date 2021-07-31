<?php

header('Access-Control-Allow-Origin: *');

error_log(file_get_contents( 'php://input' ));
error_log(print_r(apache_request_headers(), true));
#error_log(print_r($_SERVER, true));
error_log(print_r($_POST, true));
error_log(print_r($_GET, true));
error_log(print_r($_FILES, true));

echo "Wassup ? I don't know the answer";
?>

