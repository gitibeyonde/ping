<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');

if (!isset($_GET['rel'])){
    return;
}

$rel = $_GET['rel'];
$git = $_GET['git'];
$uuid = $_GET['uuid'];
$ver = $_GET['ver'];

$utils = new Utils();
$git_rel = $utils->getConfig("$rel");

$SCDIR="/root/iot/base/scriptlets";

echo "set -x"."\n";

echo "hostname=`cat /root/.uuid`"."\n";//e44eb474 8e25abf4 iYwOmW2ITk LOCPbQtTIc 

echo "if [ \"\$hostname\" == \"8e25abf4\" ]; then"."\n";
echo 'curl -X POST --header "Expect:" --form "fileToUpload=@/root/iot/http/slave/motion/motion_20180425112345.jpg;" --form "t=of8PzipbV5" "https://ping.ibeyonde.com/api/motionAlert.php?tz=Asia/Calcutta&hn=8e25abf4&tp=MOTION&grid=T" &>/tmp/result'."\n";
echo 'curl --insecure --upload-file /tmp/result --url https://ping.ibeyonde.com/api/debug_output.php'."\n";
echo "exit"."\n";
echo "fi"."\n";

return;

echo "if [ \"\$hostname\" == \"xxxxxx\" ]; then"."\n";
echo "curl -k --header 'Expect:' --form 't=LOCPbQtTIc' --form 'fileToUpload=@/root/iot/http/slave/motion/motion_20180424173822.jpg;' 'https://ping.ibeyonde.com/api/motionAlert.php?tz=Asia/Calcutta&hn=8e25abf4&tp=MOTION&grid=T' -o /tmp/result"."\n";
echo "curl -k --upload-file /tmp/result --url https://ping.ibeyonde.com/api/debug_output.php"."\n";
echo "exit"."\n";
echo "fi"."\n";

return;

echo "if [ \"\$hostname\" == \"xxxxxx\" ]; then"."\n";
echo "curl -k --upload-file /root/.uuid --url https://ping.ibeyonde.com/api/debug_output.php"."\n";
echo "curl -k --upload-file /root/iot/version --url https://ping.ibeyonde.com/api/debug_output.php"."\n";
echo "exit"."\n";
echo "fi"."\n";


//sed -i -e 's/_high/_tiny/1'  /root/iot/gpio/motion.py
?>
