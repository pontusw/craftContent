<?php
namespace Craft;

class XmlVariable
{
    public function getXml($uri, $limit = "")
    {
        return craft()->xml->get($uri, $limit);
    }
}