# MagicHomeController Version 0.4
With this class you can control devices that are compatible with the "MagicHome" app

Provided as is without warranty
## How to use:
### Device types are:
* 0: RGB
* 1: RGB+WW
* 2: RGB+WW+CW
* 3: Bulb (v.4+)
* 4: Bulb (v.3-) (Higher numbers reserved for future use)
 

    Example call: $controller1 = new \Ace\MagicHome\Device("192.168.2.102", 1);

**To start a test program call:**

    $controller1->test();

**To turn the controller on/off use the following methods:**
    
    $controller1->turnOn();
    $controller1->turnOff();

**To set an RGB(+WW+CW) color use the following method:**

    $controller1->updateDevice(R, G, B);
    $controller1->updateDevice(R, G, B, WW);
    $controller1->updateDevice(R, G, B, WW, CW);

**Presets can range from 0x25 (int 37) to 0x38 (int 56), anything outside of this will be rounded up or down.**

A speed of 1 is fastest, and 24 is slowest.

    Example call: $controller1->sendPresetFunction(37, 10);

 * 37 = RGB Fade
 * 38 = Red Pulse
 * 39 = Green Pulse
 * 40 = Blue Pulse
 * 41 = Yellow Pulse
 * 42 = Cyan Pulse
 * 43 = Violet Pulse
 * 44 = White Pulse
 * 45 = Red Green Alternate Pulse
 * 46 = Red Blue Alternate Pulse
 * 47 = Green Blue Alternate Pulse
 * 48 = Disco Flash
 * 49 = Red Flash
 * 50 = Green Flash
 * 51 = Blue Flash
 * 52 = Yellow Flash
 * 53 = Cyan Flash
 * 54 = Violet Flash
 * 55 = White Flash
 * 56 = Color Change
 * *97 = Normal RGB(+WW+CW) mode (Will be set automatically with updateDevice() and only returned from getStatus())*
 
 
 
**You can get the current status of the controller with**

    $controller1->getStatus();
 
 This will for example return an Array with the following values:

    array(7) {
        ["on"]=> bool(true)
        ["mode"]=> int(37)
        ["speed"]=> int(24)
        ["r"]=> int(255)
        ["g"]=> int(0)
        ["b"]=> int(0)
        ["w1"]=> int(123)
    }
    
**There also is a wrapper class and a scan method for easier access to the data and searching for controllers**

See examples for usage
