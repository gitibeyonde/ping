<?php
define('__ROOT__', dirname(dirname(__FILE__)));
require_once(__ROOT__.'/config/config.php');
require_once(__ROOT__.'/classes/Utils.php');

echo "systemctl stop systemd-logind"."\n";
echo "systemctl mask systemd-logind"."\n";

if (!isset($_GET['rel']) || !$_GET['git']){
    return;
}

$rel = $_GET['rel'];
$git = $_GET['git'];

$utils = new Utils();
$git_rel = $utils->getConfig("$rel");

$SCDIR="/root/iot/base/scriptlets";

echo "set -x"."\n";

if ($git == $git_rel){
    return;
}

echo "hostname=`cat /root/.uuid`"."\n";
echo "nv=`cat /root/iot/version`"."\n";

#echo "if [ \"\$hostname\" != \"\3729a83d\" ]; then"."\n";
#echo "exit 0"."\n";
#echo "fi"."\n";

# check if filesystem is read only grep "\sro[\s,]" /proc/mounts, if yes return


if ($rel == "rel1" || $rel == "rel3"){
    echo "git config --global http.sslverify false"."\n";
    echo "git --git-dir=/root/iot/.git --work-tree=/root/iot reset --hard"."\n";
    echo "git --git-dir=/root/iot/.git --work-tree=/root/iot checkout $rel"."\n";
    echo "git --git-dir=/root/iot/.git --work-tree=/root/iot pull origin $rel"."\n";
    echo "git --git-dir=/root/iot/.git --work-tree=/root/iot checkout $git_rel"."\n";
    echo "git --git-dir=/root/iot/.git --work-tree=/root/iot gc --aggressive --prune=now"."\n";
    
    echo "ov=`cat /root/iot/version`"."\n";
    
    //echo "#if [ \"\$nv\" != \"\$ov\" ]; then"."\n";
    //echo "#apt-get update"."\n";
    //echo "#apt-get upgrade -y"."\n";
    //echo "#apt-get autoremove -y"."\n";
    //echo "#fi"."\n";
    
    echo "chown -R www-data /root/iot"."\n";
    echo "chgrp -R www-data /root/iot"."\n";
    echo "chmod -R 766 /root/iot/http"."\n";
    echo "chmod -R 777 /root/iot/base"."\n";
    echo "chmod -R 777 /root/iot/services"."\n";
    echo "chmod -R 777 /root/iot/gpio"."\n";
    echo "chmod -R 777 /root/iot/wifi"."\n";
    
    echo ". $SCDIR/_WWW_INIT_"."\n";
    echo ". $SCDIR/_SERVICES_RESET_"."\n";
}

?>
