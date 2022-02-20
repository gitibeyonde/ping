<?php
define ( '__ROOT__',  dirname(dirname(dirname ( __FILE__ ))));
require_once(__ROOT__ . '/classes/core/SqliteCrud.php');
require_once(__ROOT__ . '/classes/core/Log.php');

$GLOBALS['log'] = new Log("info");

$token = "91911111111";
$Sql = new SqliteCrud($token);

function process($content){
    if ($content == null){
        echo "<br/>Returned nothing";
    }
    else if ($content == false){
        echo "<br/>Execution Failed";
    }
    else {
        echo json_encode($content, true);
    }
    echo "---------------------------";
}

$content = $Sql->query("drop table IF EXISTS user_data ;");
process($content);

$content = $Sql->query("CREATE TABLE IF NOT EXISTS user_data ( number text, name text, value text, changedOn text, PRIMARY KEY(number, name)) ;");
process($content);

$content = $Sql->query("select * from  user_data;");
process($content);

$content = $Sql->query(sprintf ( "insert into user_data(number, name, value, changedOn) values( '%s', '%s', '%s', datetime('now'));", 
        "676127678", "name", "value" ));
process($content);

$content = $Sql->query(sprintf ( "insert into user_data(number, name, value, changedOn) values( '%s', '%s', '%s', datetime('now'));",
        "67612uiuo78", "name1", "value1" ));
process($content);

$content = $Sql->query("select * from  user_data;");
process($content);

$content = $Sql->query("delete from  user_data  where number='67612uiuo78';");
process($content);


$Sql->insert("user_data", array(array( "p" => "5lgZGM1g8r",   "tabella" => "user_data",  "number" => "9701188011",   "name" => "name8",
        "value" => "eight",    "changedOn" => "2020-07-14 08:17:01")));

$content = $Sql->query("select * from  user_data;");
process($content);

$Sql->insert("user_data", array(array(   "number" => "9701199011",   "name" => "name9",
        "value" => "nine",    "changedOn" => "2020-07-14 08:17:01")));

$content = $Sql->query("select * from  user_data;");
process($content);
?>