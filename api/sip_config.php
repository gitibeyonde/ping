<?php

        if (isset($_GET['dev_uuid'])){
                $uuid = $_GET['dev_uuid'];
                $cam_ip = $_SERVER['REMOTE_ADDR'];

        $servername = "mysql.ibeyonde.com";
        $username = "admin";
        $password = "1b6y0nd6";
        $database = "ibe";

        $link = mysql_connect($servername,$username,$password);
        if (!$link) {
            die('Not connected : ' . mysql_error());
        }

        $db_selected = mysql_select_db($database, $link);
        if (!$db_selected) {
            die ('Can\'t use foo : ' . mysql_error());
        }

        $count=0;
        $rows;
        //check if the account already exists
        $sql="select * from sip where dev_uuid='$uuid' and type='def_app'";
        $result = mysql_query($sql);
                if ($result){
            $count = mysql_num_rows($result);
                }
                else {
            echo json_encode(array('errno' => 'sql_100', 'msg' => 'Failed to get sip table'));
                        exit;
                }

        if ( $count == 0 ){
            echo json_encode(array('errno' => 'sip_102', 'msg' => 'The default app does not exists'));
        }
        else {
            $row=mysql_fetch_array($result, MYSQL_ASSOC);
             echo json_encode(array('sipno' => $row['sip'], 'type' => $row['type'], 'valid' => $row['valid']));
        }
    
        mysql_close($link);
        }
      else {
                echo json_encode(array('errno' => 'param_100', 'msg' => 'Mandatory param missing'));
        }


?>

