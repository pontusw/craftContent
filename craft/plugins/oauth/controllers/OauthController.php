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

namespace Craft;

class OauthController extends BaseController
{
    protected $allowAnonymous = array('actionConnect');

    private $handle;
    private $namespace;
    private $scopes;
    private $params;
    private $redirect;
    private $referer;
    private $errorRedirect;

    public function actionDeleteToken(array $variables = array())
    {
        $this->requirePostRequest();

        $id = craft()->request->getRequiredPost('id');

        $token = craft()->oauth->getTokenById($id);

        if (craft()->oauth->deleteToken($token))
        {
            if (craft()->request->isAjaxRequest())
            {
                $this->returnJson(array('success' => true));
            }
            else
            {
                craft()->userSession->setNotice(Craft::t('Token deleted.'));
                $this->redirectToPostedUrl($token);
            }
        }
        else
        {
            if (craft()->request->isAjaxRequest())
            {
                $this->returnJson(array('success' => false));
            }
            else
            {
                craft()->userSession->setError(Craft::t('Couldn’t delete token.'));

                // Send the token back to the template
                craft()->urlManager->setRouteVariables(array(
                    'token' => $token
                ));
            }
        }
    }

    /**
     * Save Provider
     */
    public function actionConnect()
    {
        $error = false;
        $success = false;
        $token = false;
        $errorMsg = false;

        try
        {
            // handle
            $this->handle = craft()->httpSession->get('oauth.handle');

            if(!$this->handle)
            {
                $this->handle = craft()->request->getParam('provider');
                craft()->httpSession->add('oauth.handle', $this->handle);
            }



            // session vars

            $this->redirect = craft()->httpSession->get('oauth.redirect');
            $this->errorRedirect = craft()->httpSession->get('oauth.errorRedirect');
            $this->scopes = craft()->httpSession->get('oauth.scopes');
            $this->params = craft()->httpSession->get('oauth.params');
            $this->referer = craft()->httpSession->get('oauth.referer');


            // google cancel

            if(craft()->request->getParam('error'))
            {
                throw new Exception("An error occured: ".craft()->request->getParam('error'));
            }


            // twitter cancel

            if(craft()->request->getParam('denied'))
            {
                throw new Exception("An error occured: ".craft()->request->getParam('denied'));
            }


            // provider

            $provider = craft()->oauth->getProvider($this->handle);


            // init service

            $provider->source->initializeService($this->scopes);

            $classname = get_class($provider->source->service);

            switch($classname::OAUTH_VERSION)
            {
                case 2:
                    // oauth 2

                    $code = craft()->request->getParam('code');

                    if (!$code)
                    {
                        // redirect to authorization url if we don't have a code yet

                        $authorizationUrl = $provider->source->service->getAuthorizationUri($this->params);

                        $this->redirect($authorizationUrl);
                    }
                    else
                    {
                        // get token from code
                        $token = $provider->source->service->requestAccessToken($code);
                    }

                    break;

                case 1:

                    // oauth 1

                    $oauth_token = craft()->request->getParam('oauth_token');
                    $oauth_verifier = craft()->request->getParam('oauth_verifier');

                    if (!$oauth_token)
                    {
                        // redirect to authorization url if we don't have a oauth_token yet

                        $token = $provider->source->service->requestRequestToken();
                        $authorizationUrl = $provider->source->service->getAuthorizationUri(array('oauth_token' => $token->getRequestToken()));
                        $this->redirect($authorizationUrl);
                    }
                    else
                    {
                        // get token from oauth_token
                        $token = $provider->source->storage->retrieveAccessToken($provider->source->getClass());

                        // This was a callback request, now get the token
                        $token = $provider->source->service->requestAccessToken(
                            $oauth_token,
                            $oauth_verifier,
                            $token->getRequestTokenSecret()
                        );

                        if(!$token->getAccessToken())
                        {
                            throw new Exception("Couldn't retrieve token");
                        }
                    }

                break;

                default:
                    throw new Exception("Couldn't handle connect for this provider");
            }

            $success = true;
        }
        catch(\Exception $e)
        {
            $error = true;
            $errorMsg = $e->getMessage();
        }


        // we now have $token, build up response

        $tokenArray = null;

        if($token)
        {
            $tokenArray = craft()->oauth->tokenToArray($token);
        }

        $response = array(
            'error'         => $error,
            'errorMsg'      => $errorMsg,
            'errorRedirect' => $this->errorRedirect,
            'redirect'      => $this->redirect,
            'success'       => $success,
            'token'         => $tokenArray
        );

        craft()->httpSession->add('oauth.response', $response);


        // redirect
        $this->redirect($this->referer);
    }

    /**
     * Save Provider
     */
    public function actionProviderSave()
    {
        $handle = craft()->request->getParam('providerHandle');
        $attributes = craft()->request->getPost('provider');

        $provider = craft()->oauth->getProvider($handle, false);
        $provider->setAttributes($attributes);

        if (craft()->oauth->providerSave($provider))
        {
            craft()->userSession->setNotice(Craft::t('Service saved.'));
            $redirect = craft()->request->getPost('redirect');
            $this->redirect($redirect);
        }
        else
        {
            craft()->userSession->setError(Craft::t("Couldn't save service."));
            craft()->urlManager->setRouteVariables(array('service' => $provider));
        }
    }

    /**
     * Test Connect
     */
    public function actionTestConnect()
    {
        $handle = craft()->request->getParam('provider');

        $provider = craft()->oauth->getProvider($handle);

        $scopes = $provider->source->getScopes();
        $params = $provider->source->getParams();

        if($response = craft()->oauth->connect(array(
            'plugin' => 'oauth',
            'provider' => $handle,
            'scopes' => $scopes,
            'params' => $params
        )))
        {
            if($response['success'])
            {
                // token
                $token = $response['token'];

                // save token
                craft()->oauth->saveToken($handle, $token);

                // session notice
                craft()->userSession->setNotice(Craft::t("Connected."));
            }
            else
            {
                craft()->userSession->setError(Craft::t($response['errorMsg']));
            }

            $this->redirect($response['redirect']);
        }
    }

    /**
     * Test Disconnect
     */
    public function actionTestDisconnect()
    {
        $handle = craft()->request->getParam('provider');

        // reset token
        craft()->oauth->saveToken($handle, null);

        // set notice
        craft()->userSession->setNotice(Craft::t("Disconnected."));

        // redirect
        $redirect = craft()->request->getUrlReferrer();
        $this->redirect($redirect);
    }
}