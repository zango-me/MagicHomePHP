<?php
namespace Ace\MagicHome;
/*
 * ##################################
 * # Controller Class for the logic #
 * ##################################
 */

class Device {
    private static $apiPort = 5577;
    private static $delay = 50000; //microseconds

    private $deviceIp;
    private $deviceType;
    private $socket;


    public function __construct($deviceIp, $deviceType) {
        $this->deviceIp = $deviceIp;
        $this->deviceType = $deviceType;
        if(!$this->connectToController()) throw new MHException("Could not connect to controller");
    }

    public function __destruct() {
        socket_close($this->socket);
    }

    public function turnOn() {
        //Turn a device on.
        if($this->deviceType < 4)
            $this->sendBytes([0x71, 0x23, 0x0F, 0xA3]);
        else
            $this->sendBytes([0xCC, 0x23, 0x33]);
    }

    public function turnOff() {
        //Turn a device on.
        if($this->deviceType < 4)
            $this->sendBytes([0x71, 0x24, 0x0F, 0xA4]);
        else
            $this->sendBytes([0xCC, 0x24, 0x33]);
    }

    public function updateDevice($r = 0, $g = 0, $b = 0, $white1 = null, $white2 = null) {
        switch ($this->deviceType){
            case DeviceType::RGB: //Update an RGB or an RGB+WW device
            case DeviceType::RGBWW:
                $message = [0x31,
                    Helper::checkNumberRange($r),
                    Helper::checkNumberRange($g),
                    Helper::checkNumberRange($b),
                    Helper::checkNumberRange($white1),
                    0x00, 0x0f];
                array_push($message, Helper::calculateChecksum($message));
                $this->sendBytes($message);
                break;
            case DeviceType::RGBWWCW: //Update an RGB+WW+CW device
                $message = [0x31,
                    Helper::checkNumberRange($r),
                    Helper::checkNumberRange($g),
                    Helper::checkNumberRange($b),
                    Helper::checkNumberRange($white1),
                    Helper::checkNumberRange($white2),
                    0x0f, 0x0f];
                array_push($message, Helper::calculateChecksum($message));
                $this->sendBytes($message);
                break;
            case DeviceType::Bulb4: //Update the white, or color, of a bulb
                if($white1 !== null){
                    $message = [0x31, 0x00, 0x00, 0x00,
                        Helper::checkNumberRange($white1),
                        0x0f, 0x0f];
                    array_push($message, Helper::calculateChecksum($message));
                    $this->sendBytes($message);
                }else{
                    $message = [0x31,
                        Helper::checkNumberRange($r),
                        Helper::checkNumberRange($g),
                        Helper::checkNumberRange($b),
                        0x00, 0xf0, 0x0f];
                    array_push($message, Helper::calculateChecksum($message));
                    $this->sendBytes($message);
                }
                break;
            case DeviceType::Bulb3: //Update the white, or color, of a legacy bulb
                if($white1 !== null){
                    $message = [0x56, 0x00, 0x00, 0x00,
                        Helper::checkNumberRange($white1),
                        0x0f, 0xaa, 0x56, 0x00, 0x00, 0x00,
                        Helper::checkNumberRange($white1),
                        0x0f, 0xaa];
                    array_push($message, Helper::calculateChecksum($message));
                    $this->sendBytes($message);
                }else{
                    $message = [0x56,
                        Helper::checkNumberRange($r),
                        Helper::checkNumberRange($g),
                        Helper::checkNumberRange($b),
                        0x00, 0xf0, 0xaa];
                    array_push($message, Helper::calculateChecksum($message));
                    $this->sendBytes($message);
                }
                break;
            default:
                throw new MHException("Incompatible device type received");
        }
    }

    public function sendPresetFunction($presetNumber, $speed) {
        if ($presetNumber < 37)
            $presetNumber = 37;
        if ($presetNumber > 56)
            $presetNumber = 56;
        if ($speed < 1)
            $speed = 1;
        if ($speed > 24)
            $speed = 24;

        if ($this->deviceType == 4) {
            $this->sendBytes([0xBB, $presetNumber, $speed, 0x44]);
        } else {
            $message = [0x61, $presetNumber, $speed, 0x0F];
            array_push($message, Helper::calculateChecksum($message));
            $this->sendBytes($message);
        }
    }

    public function getStatus() {
        try {
            if ($this->deviceType == 2) {
                //15 byte answer of RGB+WW+CW  >>> could not implement because of no hardware to debug
                return false;
            } else {
                //14 byte answer
                $message = Helper::packMessage([0x81, 0x8A, 0x8B, 0x96]);
                socket_write($this->socket, $message, strlen($message));
                $buffer = '';
                socket_recv($this->socket, $buffer, 14, MSG_WAITALL);
                $data = Helper::String2HexArray($buffer);
                //var_dump($data);
                $on = $data[2] == "23"; //0x23 == on / 0x24 == off
                $mode = hexdec($data[3]);
                $speed = hexdec($data[5]);
                $r = hexdec($data[6]);
                $g = hexdec($data[7]);
                $b = hexdec($data[8]);
                $w = hexdec($data[9]);
                return array(
                    "on" => $on,
                    "mode" => $mode,
                    "speed" => $speed,
                    "r" => $r,
                    "g" => $g,
                    "b" => $b,
                    "w1" => $w
                );
            }
        }catch (\Exception $ex){
            //error sending command
            if($this->connectToController()) return $this->getStatus();
            else throw new MHException("Could not connect to controller");
        }
    }

    private function sendBytes(Array $bytes) {
        $message = Helper::packMessage($bytes);
        try {
            socket_write($this->socket, $message, strlen($message));
            socket_read($this->socket, 2048);
            usleep($this::$delay);
        } catch (\Exception $ex) {
            //error sending command
            if($this->connectToController()) $this->sendBytes($bytes);
            else throw new MHException("Could not connect to controller");
        }
    }

    private function connectToController(){
        try{
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            socket_connect($this->socket, $this->deviceIp, $this::$apiPort);
            return true;
        }catch (\Exception $ex){
            return false;
        }
    }


    public function test(){
        //Runs an automatic test
        $this->turnOn(); //On
        $this->updateDevice(255,0,0); //Red
        usleep(1000000);
        $this->updateDevice(0,255,0); //Green
        usleep(1000000);
        $this->updateDevice(0,0,255); //Blue
        usleep(1000000);
        $this->updateDevice(10,0,0,255); //WW + 10R
        usleep(1000000);
        $this->updateDevice(10,0,0,0,255); //CW + 10R
        usleep(1000000);
        $this->sendPresetFunction(37, 1); //Fast Fade
        usleep(5000000);
        $this->sendPresetFunction(48, 5); //Disco Strobe
        usleep(5000000);
        $this->updateDevice(255,255,255,255,255); //W+WW+CW
        usleep(2000000);
        $this->updateDevice(0,0,0); //Dark
        $this->turnOff(); //Off
    }

}
