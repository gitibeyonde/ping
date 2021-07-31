<?php

        if (isset($_GET['uuid']) && isset($_GET['dev_uuid'])){
                $uuid = $_GET['uuid'];
                $dev_uuid = $_GET['dev_uuid'];
                $app_ip = $_SERVER['REMOTE_ADDR'];

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
        if ( $count != 0 ){
            $row = mysql_fetch_array($result, MYSQL_ASSOC);
            echo json_encode(array('devuuid' => $row['dev_uuid'], 'sipno' => $row['sip'], 'key' => $row['secret'], 'context' => $row['context'], 'type' => $row['type'], 'valid' => $row['valid'], 'msg' => "app already registered"));
            exit;
        }

        $sql="select * from sip where dev_uuid='$dev_uuid'";
        $result = mysql_query($sql);
                if ($result){
            $count = mysql_num_rows($result);
                }
                else {
            echo json_encode(array('errno' => 'sql_100', 'msg' => 'Failed to get sip table'));
                        exit;
                }

        if ( $count == 0 ){
             echo json_encode(array('errno' => 'sip_100', 'msg' => 'Device account does not exists, Please, register the sip account from the device first'));
        }
        else {
            while($row = mysql_fetch_array($result, MYSQL_ASSOC))
            {
                if ($row['type'] == "device" ){
                    $context=$row['context'];

                     //get next sip number
                    mysql_query("update counter set value=last_insert_id(value+1) where name='sip_number';");
                    $rows = mysql_fetch_array(mysql_query("select last_insert_id()"));
                    $sip_number=$rows[0];
                    $secret=randomPassword();
                    // generate an entry in sip_user
                    $sql="INSERT INTO sip_user (NAME, defaultuser, callerid, secret, context, HOST, nat, qualify, TYPE) VALUES ('$sip_number', '$sip_number', '$sip_number', '$secret', '$context', 'dynamic', 'yes', 'no', 'friend')";
                    //echo $sql."<br/>";
                    mysql_query($sql);

                    //check if the context already there
                    $context_count=0;
                    $sql="select * from dialplan where context='$context'";
                    $context_result = mysql_query($sql);
                    if ($context_result){
                        $context_count = mysql_num_rows($context_result);
                    }
                    if ( $context_count==0 ){
                        //update sip table
                        $sql = "INSERT INTO sip ( uuid, sip, secret, context, type, valid, dev_uuid, created, last_update) values ('$uuid', '$sip_number', '$secret', '$context', 'def_app', 1, '$dev_uuid', now(), now());";
                        //echo $sql."<br/>";
                        mysql_query($sql);

                        //insert group
                        $sql = "INSERT INTO dialplan (context, exten, priority, app, appdata) VALUES ('$context', '$sip_number', 1, 'Dial', 'SIP/$sip_number,60');";
                        mysql_query($sql);
                    }
                    else {
                        //update sip table
                        $sql = "INSERT INTO sip ( uuid, sip, secret, context, type, valid, dev_uuid, created, last_update) values ('$uuid', '$sip_number', '$secret', '$context', 'app', 1, '$dev_uuid', now(), now());";
                        //echo $sql."<br/>";
                        mysql_query($sql);

                        $context_row=mysql_fetch_array($context_result, MYSQL_ASSOC);
                        //echo $context_row['appdata']."<br/>";
                        $sipnums = explode(',', $context_row['appdata']);
                        $new_appdata = $sipnums[0]."&SIP/".$sip_number.",60";
                        //echo $new_appdata."<br/>";
                        //group exists update
                        $sql = "update dialplan set appdata='$new_appdata' where context='$context';";
                        //echo $sql."<br/>";
                        mysql_query($sql);
                    }

                                        echo json_encode(array('sipno' => $sip_number, 'key' => $secret, 'context' => $context, 'type' => 'app', 'device_sipno' => $row['sip'], 'device_valid' => $row['valid']));
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

