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
            case 0: //Update an RGB or an RGB+WW device
            case 1:
                $message = [0x31,
                    Helper::checkNumberRange($r),
                    Helper::checkNumberRange($g),
                    Helper::checkNumberRange($b),
                    Helper::checkNumberRange($white1),
                    0x00, 0x0f];
                array_push($message, Helper::calculateChecksum($message));
                $this->sendBytes($message);
                break;
            case 2: //Update an RGB+WW+CW device
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
            case 3: //Update the white, or color, of a bulb
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
            case 4: //Update the white, or color, of a legacy bulb
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





/*
 * ########################################
 * # MHException Class for own Exceptions #
 * #        Noting special at all         #
 * ########################################
 */
class MHException extends \Exception {}





/*
 * ######################################################
 * # Helper Class for Static functions and network scan #
 * ######################################################
 */
class Helper extends Device {

    protected static function checkNumberRange($number) {
        if($number < 0)
            return 0;
        elseif ($number > 255)
            return 255;
        else
            return $number;
    }

    protected static function calculateChecksum(Array $bytes) {
        return array_sum($bytes) & 0xFF; //Try with PHP array_sum for Python sum
    }

    protected static function packMessage($bytes){
        $message_length = count($bytes);
        return pack("C".$message_length, ...$bytes);
    }

    protected static function String2HexArray($string){
        $hex=[];
        for ($i=0; $i < strlen($string); $i++){
            $hex[] = dechex(ord($string[$i]));
        }
        return $hex;
    }

    public static function scan($broadcast = "255.255.255.255", $timeout = 5) {
        $discoveryPort = 48899;
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
        $message = self::packMessage([0x48, 0x46, 0x2d, 0x41, 0x31, 0x31, 0x41, 0x53, 0x53, 0x49, 0x53, 0x54, 0x48, 0x52, 0x45, 0x41, 0x44]);
        $quitTime = time() + $timeout;
        $responseList = [];
        while (true){
            if (time() > $quitTime) break;
            socket_sendto($sock, $message, strlen($message), MSG_DONTROUTE, $broadcast, $discoveryPort);
            while (true){
                if (time() > $quitTime) break;
                socket_set_option($sock,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>0, "usec"=>200000));
                $data = @socket_read($sock, 64);
                if($data == ''){
                    $data = null;
                    break;
                }
                if($data != null && $data != $message){
                    $data = explode(",", $data);
                    $tmp = array(
                        "ip" => $data[0],
                        "mac" => $data[1],
                        "model" => $data[2]
                    );
                    if(!in_array($tmp, $responseList))
                        array_push($responseList, $tmp);
                }

            }
        }
        return $responseList;
    }

    public static function findIpByMac($mac = "", $broadcast = "255.255.255.255", $timeout = 5) {
        if(strlen($mac) == 12){
            $discoveryPort = 48899;
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
            $message = self::packMessage([0x48, 0x46, 0x2d, 0x41, 0x31, 0x31, 0x41, 0x53, 0x53, 0x49, 0x53, 0x54, 0x48, 0x52, 0x45, 0x41, 0x44]);
            $quitTime = time() + $timeout;
            while (true){
                if (time() > $quitTime) break;
                socket_sendto($sock, $message, strlen($message), MSG_DONTROUTE, $broadcast, $discoveryPort);
                while (true){
                    if (time() > $quitTime) break;
                    socket_set_option($sock,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>0, "usec"=>200000));
                    $data = @socket_read($sock, 64);
                    if($data == ''){
                        $data = null;
                        break;
                    }
                    if($data != null && $data != $message){
                        $data = explode(",", $data);
                        if($data[1] == strtoupper($mac)) return (string)$data[0];
                    }
                }
            }
            return false;
        }else{
            throw new MHException("Not a valid MAC");
        }
    }

}





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
