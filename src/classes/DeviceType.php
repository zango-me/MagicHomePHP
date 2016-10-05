<?php
namespace Ace\MagicHome;
/*
* ################################
* # "Enum" for Wifi Device Types #
* ################################
*/

class DeviceType{
    const RGB = 0; //RGB
    const RGBWW = 1; //RGB+WW
    const RGBWWCW = 2; //RGB+WW+CW
    const Bulb4 = 3; //Bulb (v.4+)
    const Bulb3 = 4; //Bulb (v.3-) (Higher numbers reserved for future use)
}
