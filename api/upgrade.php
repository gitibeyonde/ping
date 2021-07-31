<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');

if (!isset($_GET['rel']) || !isset($_GET['git'])){
    return;
}

$rel = $_GET['rel'];
$git = $_GET['git'];
$utils = new Utils();
$sgit = $utils->getConfig("sgit");

if ($git == $sgit){
    return;
}


$sver = explode('.', $utils->getConfig("sver"));

echo ". /root/.bashrc"."\n";

$SCDIR="/root/iot/base/scriptlets";

//echo "set -x"."\n";
echo "nv=`cat /root/iot/version | cut -d'.' -f 2`"."\n";
echo "git --git-dir=/root/iot/.git --work-tree=/root/iot reset --hard HEAD~5"."\n";
echo "git --git-dir=/root/iot/.git --work-tree=/root/iot pull --rebase origin $rel"."\n";
echo "git checkout $sgit"."\n";
echo "ov=`cat /root/iot/version | cut -d'.' -f 2`"."\n";

echo "if [ \$nv -ne \$ov ]; then"."\n";
echo ". $SCDIR/_UPGRADE_"."\n";
echo "fi"."\n";

echo "chown -R www-data /root/iot"."\n";
echo "chgrp -R www-data /root/iot"."\n";


echo ". $SCDIR/_SERVICES_RESET_"."\n";
echo ". $SCDIR/_WWW_INIT_"."\n";
echo "/root/iot/base/action/settings.sh"."\n";



?>
