<?php
use \Ace\MagicHome;
require_once __DIR__.'/src/MagicHome.php';
$loader = new MagicHome\Autoloader();
$loader->register();

echo "searching network for controllers\n";
$controllers = MagicHome\Helper::scan();
if(count($controllers) >= 1){
    echo "found ".count($controllers)." controllers\n";
    foreach ($controllers as $controller) {
        echo "testing controller with ip:".$controller['ip']."\n";
        $con = new MagicHome\Wrapper(new MagicHome\Device($controller['ip'], MagicHome\DeviceType::RGBWW));
        $con->getDevice()->test();
    }
    echo "finished testing controllers";
}else{
    echo "no controllers found";
}
