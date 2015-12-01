<?php

/**
 * Craft OAuth by Dukt
 *
 * @package   Craft OAuth
 * @author    Benjamin David
 * @copyright Copyright (c) 2014, Dukt
 * @license   https://dukt.net/craft/oauth/docs/license
 * @link      https://dukt.net/craft/oauth/
 */

namespace OAuthProviderSources;

class FacebookOAuthProviderSource extends BaseOAuthProviderSource {

	public $consoleUrl = 'https://developers.facebook.com/apps';

	public function getName()
	{
		return 'Facebook';
	}

    public function getAccount()
    {
        $response = $this->service->request('/me');
        $response = json_decode($response, true);

        $account = array();

        $account['uid'] = $response['id'];

        if(!empty($response['name']))
        {
            $account['name'] = $response['name'];
        }

        if(!empty($response['username']))
        {
            $account['username'] = $response['username'];
        }

        if(!empty($response['email']))
        {
            $account['email'] = $response['email'];
        }

        return $account;
    }
}