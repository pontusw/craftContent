<?php

/**
 * XML plugin for Craft CMS
 * @author    Pontus Wiehager
 * @copyright Copyright (c) 2015, WOMS
 */

namespace Craft;

class XmlPlugin extends BasePlugin
{
    function getName()
    {
         return 'Xml';
    }

    function getVersion()
    {
        return '0.1';
    }

    function getDeveloper()
    {
        return 'WOMS';
    }

    function getDeveloperUrl()
    {
        return 'http://woms.se';
    }
}

