<?php
require_once 'MagicHomeController.php';

echo "searching network for controllers\n";
$controllers = \MagicHome\MagicHomeController::scan();
if(sizeof($controllers) >= 1){
    echo "found ".sizeof($controllers)." controllers\n";
    foreach ($controllers as $controller) {
        echo "testing controller with ip:".$controller['ip']."\n";
        $con = new \MagicHome\MagicHomeWrapper(new \MagicHome\MagicHomeController($controller['ip'], 1));
        $con->getController()->test();
    }
    echo "finished testing controllers\n";
}else{
    echo "no controllers found\n";
}

