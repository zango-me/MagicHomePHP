<?php
namespace Ace\MagicHome;

class Autoloader
{
    private $namespace = "Ace\\MagicHome";

    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    public function loadClass($className)
    {
        if($this->namespace !== null)
        {
            $className = str_replace($this->namespace . '\\', '', $className);
        }

        $className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

        $file = __DIR__. '/classes/'.$className.'.php';

        if(file_exists($file))
        {
            require_once $file;
        }
    }
}