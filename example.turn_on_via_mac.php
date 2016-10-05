<?php
use \Ace\MagicHome;
require_once __DIR__.'/src/MagicHome.php';
$loader = new MagicHome\Autoloader();
$loader->register();

$ip = MagicHome\Helper::findIpByMac('5ECF7F224C1F');
if($ip !== false){
    $con = new MagicHome\Wrapper(new MagicHome\Device($ip, MagicHome\DeviceType::RGBWW));
    if(!$con->getPower()) $con->setPower(true);
    $con->getDevice()->updateDevice(255, 50, 4, 150);
}else{
    echo "controller not found";
}
