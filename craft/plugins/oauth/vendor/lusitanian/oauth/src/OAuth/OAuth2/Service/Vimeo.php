<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;

use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;

class Vimeo extends AbstractService
{
    const SCOPE_PUBLIC   = 'public';
    const SCOPE_PRIVATE  = 'private';
    const SCOPE_CREATE   = 'create';
    const SCOPE_EDIT     = 'edit';
    const SCOPE_DELETE   = 'delete';
    const SCOPE_INTERACT = 'interact';
    const SCOPE_UPLOAD   = 'upload';

    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri, true);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://api.vimeo.com/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://api.vimeo.com/oauth/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://api.vimeo.com/oauth/access_token');
    }

    protected function getExtraOAuthHeaders()
    {
        // return array('Accept' => 'application/json');
        return array('Authorization' => 'basic ' . base64_encode($this->credentials->getConsumerId(). ':' .$this->credentials->getConsumerSecret()));
    }

    public function requestAccessToken($code)
    {
        $bodyParams = array(
            'grant_type'    => 'authorization_code',
            'code'             => $code,
            'redirect_uri'  => $this->credentials->getCallbackUrl(),
        );

        $responseBody = $this->httpClient->retrieveResponse(
            $this->getAccessTokenEndpoint(),
            $bodyParams,
            $this->getExtraOAuthHeaders()
        );
        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }

    /**
     * {@inheritdoc}
     */

    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_BEARER;
    }

    protected function parseAccessTokenResponse($responseBody)
    {

        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error_description'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        // $token->setLifetime($data['expires_in']);

        unset($data['access_token']);
        // unset($data['expires_in']);

        //$token->setExtraParams($data);

        return $token;
    }
}
