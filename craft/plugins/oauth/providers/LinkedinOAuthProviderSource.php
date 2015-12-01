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

class LinkedinOAuthProviderSource extends BaseOAuthProviderSource {

    public $consoleUrl = 'https://www.linkedin.com/secure/developer';

    public function getName()
    {
        return 'Linkedin';
    }

    public function getAccount()
    {
        return array();
        // $response = $this->service->request('/people/~?format=json');
        // $response = json_decode($response, true);

        // $account = array();

        // $account['uid'] = $response['id'];
        // $account['name'] = $response['name'];
        // $account['username'] = $response['login'];
        // $account['email'] = $response['email'];

        // return $account;
    }
}