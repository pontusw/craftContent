<?php
namespace Craft;

class XmlService extends BaseApplicationComponent
{
    public function get($uri, $limit)
    {
		$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
		$items = array();

		foreach($uri as $url){

			$url = str_replace("/@","/feed/@",$url);
			$xml = file_get_contents($url, false, $context);
			$xml = simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);

			$pubImage = $xml->channel->image->url;
			$pubTitle = $xml->channel->image->title;

			foreach($xml->channel->item as $item){


				$pubDate = $item->pubDate;
				$item->pubImage = $pubImage;
				$item->pubTitle = $pubTitle;
				$item->date = date("M j, Y", strtotime($pubDate));
				array_push($items,$item);

			}
		}

		usort($items, array($this,"compareItems"));

		if($limit){
			return array_slice($items, 0, $limit);
		}
		else{
			return $items;
		}

		
    }

	private static function compareItems($a,$b)
	{
	    $a = strtotime($a->pubDate);
	    $b = strtotime($b->pubDate);

	    if($a == $b)
	        return 0;
	    elseif($a > $b)
	        return -1;
	    else
	        return 1;
	}
  
}