<?php
namespace Ace\MagicHome;
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
