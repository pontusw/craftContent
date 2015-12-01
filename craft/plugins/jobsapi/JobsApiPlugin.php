<?php

/**
 * JobsApi plugin for Craft CMS
 * @author    Pontus Wiehager
 * @copyright Copyright (c) 2015, WOMS
 */

namespace Craft;

class JobsApiPlugin extends BasePlugin
{
    function getName()
    {
         return 'Jobs Api';
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

    public function hasCpSection()
    {
        return true;
    }
}

