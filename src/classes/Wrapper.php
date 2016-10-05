<?php
namespace Ace\MagicHome;
/*
 * ################################
 * # Wrapper Class for easy usage #
 * ################################
 */

class Wrapper {
    /**
     * @var Device $controller
     */
    private $controller;

    /**
     * @var double $lastRefreshed
     */
    private $lastRefreshed = 0;

    private $on;
    private $mode;
    private $speed;
    private $r;
    private $g;
    private $b;
    private $w1;

    public function __construct(Device $controller) {
        $this->controller = $controller;
        $this->readControllerStatus();
    }

    public function readControllerStatus() {
        try {
            $data = $this->controller->getStatus();
            $this->on = $data['on'];
            $this->mode = $data['mode'];
            $this->speed = $data['speed'];
            $this->r = $data['r'];
            $this->g = $data['g'];
            $this->b = $data['b'];
            $this->w1 = $data['w1'];
            $this->lastRefreshed = microtime();
            return true;
        }catch (MHException $ex){
            return false;
        }
    }



    /**
     * @return bool
     * @param bool $on
     */
    public function setPower($on)
    {
        try {
            if($on) {
                $this->controller->turnOn();
            }else{
                $this->controller->turnOff();
            }
            $this->on = $on;
            return true;
        }catch (MHException $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @param int $mode
     * @param int $speed
     */
    public function setMode($mode, $speed)
    {
        try {
            $this->controller->sendPresetFunction($mode, $speed);
            $this->mode = $mode;
            $this->speed = $speed;
            return true;
        }catch (MHException $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @param int $r
     */
    public function setR($r)
    {
        try {
            $this->controller->updateDevice($r, $this->g, $this->b, $this->w1);
            $this->r = $r;
            return true;
        }catch (MHException $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @param int $g
     */
    public function setG($g)
    {
        try {
            $this->controller->updateDevice($this->r, $g, $this->b, $this->w1);
            $this->g = $g;
            return true;
        }catch (MHException $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @param int $b
     */
    public function setB($b)
    {
        try {
            $this->controller->updateDevice($this->r, $this->g, $b, $this->w1);
            $this->b = $b;
            return true;
        }catch (MHException $ex) {
            return false;
        }
    }

    /**
     * @return bool
     * @param int $w1
     */
    public function setW1($w1)
    {
        try {
            $this->controller->updateDevice($this->r, $this->g, $this->b, $w1);
            $this->w1 = $w1;
            return true;
        }catch (MHException $ex) {
            return false;
        }
    }



    /**
     * @return Device
     */
    public function getDevice()
    {
        return $this->controller;
    }

    /**
     * @return bool
     */
    public function getPower()
    {
        return $this->on;
    }

    /**
     * @return int
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * @return int
     */
    public function getSpeed()
    {
        return $this->speed;
    }

    /**
     * @return int
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @return int
     */
    public function getG()
    {
        return $this->g;
    }

    /**
     * @return int
     */
    public function getB()
    {
        return $this->b;
    }

    /**
     * @return int
     */
    public function getW1()
    {
        return $this->w1;
    }

    /**
     * @return double
     */
    public function getLastRefreshed()
    {
        return $this->lastRefreshed;
    }

}
