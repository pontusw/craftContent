<?php
namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'lj_mobiledetect/vendor/Mobile-Detect/Mobile_Detect.php');

class Lj_mobiledetectVariable
{
    function type()
    {
		// instantiate Mobile_Detect Object
        $detect = new \Mobile_Detect;

        // get the device type
        $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'none');

        // return string value of device type
        return $deviceType;
    }

    function isiOS()
    {
        // instantiate Mobile_Detect Object
        $detect = new \Mobile_Detect;
        
        // determine if the device is iOS
        $isiOS = ($detect->isiOS() ? true : false);

        // return true or false
        return $isiOS;

    }

    function isAndroidOS()
    {
        // instantiate Mobile_Detect Object
        $detect = new \Mobile_Detect;
        
        // determine if the device is Android
        $isAndroidOS = ($detect->isAndroidOS() ? true : false);

        // return true or false
        return $isAndroidOS;

    }
	
	function version()
	{
		// instantiate Mobile_Detect Object
		$detect = new \Mobile_Detect;
		
		// get version of the Mobile Detector library
        $scriptVersion = $detect->getScriptVersion();
		
		// return string value of version
        return $scriptVersion;
    }
}
