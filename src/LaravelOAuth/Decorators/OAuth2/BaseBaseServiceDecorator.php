<?php namespace LaravelOAuth\Decorators\OAuth2;

use LaravelOAuth\Decorators\AbstractServiceDecorator;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;
use OAuth\Common\Token\TokenInterface;
use OAuth\OAuth2\Service\ServiceInterface;

class BaseServiceDecorator extends AbstractServiceDecorator implements ServiceInterface
{
    /**
     * Sends an authenticated API request to the path provided.
     * If the path provided is not an absolute URI, the base API Uri (service-specific) will be used.
     *
     * @param string|UriInterface $path
     * @param string              $method       HTTP method
     * @param array               $body         Request body if applicable (an associative array will
     *                                          automatically be converted into a urlencoded body)
     * @param array               $extraHeaders Extra headers if applicable. These will override service-specific
     *                                          any defaults.
     *
     * @throws \Exception
     * @throws \OAuth\Common\Token\Exception\ExpiredTokenException
     * @return string
     */
    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        try {
            $this->service->request($path, $method, $body, $extraHeaders);
        } catch(ExpiredTokenException $e) {
            if ($this->refresh) {
                // FIXME refresh access token
            } else {
                throw $e;
            }
        }
    }

    /**
     * Retrieves and stores/returns the OAuth2 access token after a successful authorization.
     *
     * @param string $code The access code from the callback.
     *
     * @return TokenInterface $token
     *
     * @throws TokenResponseException
     */
    public function requestAccessToken($code)
    {
        return $this->service->requestAccessToken($code);
    }

}