<?php
require_once 'MagicHome.php';

$ip = \Ace\MagicHome\Helper::findIpByMac('5ECF7F224C1F');
if($ip !== false){
    $con = new \Ace\MagicHome\Wrapper(new \Ace\MagicHome\Device($ip, 1));
    if(!$con->getPower()) $con->setPower(true);
    $con->getDevice()->updateDevice(255, 50, 4, 150);
}else{
    echo "controller not found";
}

