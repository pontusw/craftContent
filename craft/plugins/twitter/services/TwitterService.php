<?php

/**
 * Twitter plugin for Craft CMS
 *
 * @package   Twitter
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @link      https://dukt.net/craft/twitter/
 * @license   https://dukt.net/craft/twitter/docs/license
 */

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'twitter/vendor/autoload.php');

use Guzzle\Http\Client;

class TwitterService extends BaseApplicationComponent
{
    private $token;

    public function autoLinkTweet($text, $options = array())
    {
        $twitter = \Twitter\AutoLink::create();

        $aliases = array(
            'urlClass' => 'setURLClass',
            'usernameClass' => 'setUsernameClass',
            'listClass' => 'setListClass',
            'hashtagClass' => 'setHashtagClass',
            'cashtagClass' => 'setCashtagClass',
            'noFollow' => 'setNoFollow',
            'external' => 'setExternal',
            'target' => 'setTarget'
        );

        foreach($options as $k => $v)
        {
            if(isset($aliases[$k]))
            {
                $twitter->{$aliases[$k]}($v);
            }
        }

        $html = $twitter->autoLink($text);

        return $html;
    }
    public function embedTweet($id, $params = array())
    {
        // params

        $opts = array();

        $aliases = array(
            'conversations' => 'data-conversations',
            'cards' => 'data-cards',
            'linkColor' => 'data-link-color',
            'theme' => 'data-theme'
        );

        foreach($aliases as $aliasKey => $alias)
        {
            if(!empty($params[$aliasKey]))
            {
                $opts[$alias] = $params[$aliasKey];
                unset($params[$alias]);
            }
        }

        $params = array_merge($opts, $params);
        $paramsString = '';

        foreach($params as $paramKey => $paramValue)
        {
            $paramsString .= ' '.$paramKey.'="'.$paramValue.'"';
        }


        // oembed

        $response = $this->get('statuses/oembed', array('id' => $id));

        if($response)
        {
            $html = $response['html'];

            $html = str_replace('<blockquote class="twitter-tweet">', '<blockquote class="twitter-tweet"'.$paramsString.'>', $html);


            return $html;
        }
    }

    /**
     * Get OAuth Token
     */
    public function getToken()
    {
        if($this->token)
        {
            return $this->token;
        }
        else
        {
            // get plugin
            $plugin = craft()->plugins->getPlugin('twitter');

            // get settings
            $settings = $plugin->getSettings();

            // get tokenId
            $tokenId = $settings->tokenId;

            // get token
            $token = craft()->oauth->getTokenById($tokenId);

            if($token && $token->token)
            {
                $this->token = $token;
                return $this->token;
            }
        }
    }

    /**
     * Save OAuth Token
     */
    public function saveToken($token)
    {
        // get plugin
        $plugin = craft()->plugins->getPlugin('twitter');

        // get settings
        $settings = $plugin->getSettings();

        // get tokenId
        $tokenId = $settings->tokenId;

        // get token
        $model = craft()->oauth->getTokenById($tokenId);


        // populate token model

        if(!$model)
        {
            $model = new Oauth_TokenModel;
        }

        $model->providerHandle = 'twitter';
        $model->pluginHandle = 'twitter';
        $model->encodedToken = craft()->oauth->encodeToken($token);

        // save token
        craft()->oauth->saveToken($model);

        // set token ID
        $settings->tokenId = $model->id;

        // save plugin settings
        craft()->plugins->savePluginSettings($plugin, $settings);
    }

    /**
     * Set OAuth Token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Performs a get request on the Twitter API
     *
     * @param string $uri
     * @param array $params
     * @param array $headers
     * @param array $postFields
     * @param bool $enableCache
     * @return array|null
     */
    public function get($uri, $params = array(), $headers = array(), $enableCache = false, $cacheExpire = 0)
    {
        // get from cache

        if(craft()->config->get('disableCache', 'twitter') == true)
        {
            $enableCache = false;
        }

        if($enableCache)
        {

            $key = 'twitter.'.md5($uri.serialize(array($this->token, $params)));

            $response = craft()->fileCache->get($key);

            if($response)
            {
                
                return $response;
            }
        }


        // run request

        try
        {
            $response = $this->api('get', $uri, $params, $headers);
            
            // cache response

            if($enableCache)
            {
                craft()->fileCache->set($key, $response, $cacheExpire);
            }
            
            return $response;   
        }
        catch(\Guzzle\Http\Exception\ClientErrorResponseException $e)
        {
            Craft::log(__METHOD__." Couldn't get twitter response", LogLevel::Info, true);
        }
        catch(\Guzzle\Http\Exception\CurlException $e)
        {
            Craft::log(__METHOD__." ".$e->getMessage(), LogLevel::Info, true);
        }
    }

    /**
     * Performs a request on the Twitter API
     *
     * @param string $method
     * @param string $uri
     * @param array $params
     * @param array $headers
     * @param array $postFields
     * @return array|null
     */

    public function api($method = 'get', $uri, $params = null, $headers = null, $postFields = null)
    {
        // client

        $client = new Client('https://api.twitter.com/1.1');

        $provider = craft()->oauth->getProvider('twitter');
        $tokenModel = $this->getToken();

        if(!$tokenModel)
        {
            return null;
        }

        $token = $tokenModel->token;

        if(!$provider || !$token)
        {
            return null;
        }

        $oauth = new \Guzzle\Plugin\Oauth\OauthPlugin(array(
            'consumer_key'    => $provider->clientId,
            'consumer_secret' => $provider->clientSecret,
            'token'           => $token->getAccessToken(),
            'token_secret'    => $token->getAccessTokenSecret()
        ));

        $client->addSubscriber($oauth);


        // request

        $format = 'json';

        $query = '';

        if($params)
        {
            $query = http_build_query($params);

            if($query)
            {
                $query = '?'.$query;
            }
        }

        $url = $uri.'.'.$format.$query;

        $response = $client->get($url, $headers, $postFields)->send();

        $response = $response->json();

        return $response;
    }

    /**
     * Returns a tweet by its ID.
     *
     * @param int $tweetId
     * @param array $params
     * @return array|null
     */
    public function getTweetById($tweetId, $params = array(), $enableCache = false)
    {
        $params = array_merge($params, array('id' => $tweetId));
        return $this->get('statuses/show', $params, array(), $enableCache);
    }

    /**
     * Returns a user by their ID.
     *
     * @param int $userId
     * @param array $params
     * @return array|null
     */
    public function getUserById($userId, $params = array())
    {
        $params = array_merge($params, array('user_id' => $userId));
        return $this->get('users/show', $params);
    }
}