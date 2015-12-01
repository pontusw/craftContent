<?php
namespace Craft;

class Xml_mixVariable
{
    public function getXml_mix($uri, $limit = "")
    {
        return craft()->xml_mix->get_mix($uri, $limit);
    }
}