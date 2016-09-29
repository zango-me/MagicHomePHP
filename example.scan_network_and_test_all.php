<?php
require_once 'MagicHome.php';

echo "searching network for controllers\n";
$controllers = \Ace\MagicHome\Helper::scan();
if(count($controllers) >= 1){
    echo "found ".count($controllers)." controllers\n";
    foreach ($controllers as $controller) {
        echo "testing controller with ip:".$controller['ip']."\n";
        $con = new Ace\MagicHome\Wrapper(new Ace\MagicHome\Device($controller['ip'], 1));
        $con->getDevice()->test();
    }
    echo "finished testing controllers\n";
}else{
    echo "no controllers found\n";
}

