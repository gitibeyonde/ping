<?php

        if (isset($_GET['uuid'])){
                $uuid = $_GET['uuid'];
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
        $sql="select * from sip where uuid='$uuid'";
        $result = mysql_query($sql);
        if ($result){
            $count = mysql_num_rows($result);
         }
         else {
            echo json_encode(array('errno' => 'sql_100', 'msg' => 'Failed to get sip table'));
                        exit;
         }

        if ( $count == 0 ){
            //get group counter next
            mysql_query("update counter set value=last_insert_id(value+1) where name='group';");
            $rows = mysql_fetch_array(mysql_query("select last_insert_id()"));
            $group="group$rows[0]";

            //get next sip number
            mysql_query("update counter set value=last_insert_id(value+1) where name='sip_number';");
            $rows = mysql_fetch_array(mysql_query("select last_insert_id()"));
            $sip_number=$rows[0];
            $secret=randomPassword();
            // generate an entry in sip_user
            $sql="INSERT INTO sip_user (NAME, defaultuser, secret, callerid, context, HOST, nat, qualify, TYPE) VALUES ('$sip_number', '$sip_number', '$secret', '$sip_number', '$group', 'dynamic', 'yes', 'no', 'friend')";
            mysql_query($sql);

            //update sip table
            $sql = "INSERT INTO sip ( uuid, sip, secret, context, type, valid, dev_uuid, created, last_update) values ('$uuid', '$sip_number', '$secret', '$group', 'device', 1, '$uuid', now(), now());";
            mysql_query($sql);

             echo json_encode(array('sipno' => $sip_number, 'key' => $secret, 'context' => $group, 'type' => 'device', 'valid' => 1));

        }
        else {
            while($row = mysql_fetch_array($result, MYSQL_ASSOC))
                {
                if ($row['type'] == "device" ){
                     echo json_encode(array('sipno' => $row['sip'], 'key' => $row['secret'], context => $row['context'], 'type' => $row['type'], 'valid' => $row['valid']));
                    break;
                }
            }
        }
    
        mysql_close($link);
        }
      else {
                echo json_encode(array('errno' => 'param_100', 'msg' => 'Mandatory param missing'));
        }


function randomPassword() {
    $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
    $pass = array(); //remember to declare $pass as an array
    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
    for ($i = 0; $i < 8; $i++) {
        $n = rand(0, $alphaLength);
        $pass[] = $alphabet[$n];
    }
    return implode($pass); //turn the array into a string
}

?>

