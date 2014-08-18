<?php namespace LaravelOAuth\Decorators\OAuth1;

use LaravelOAuth\Decorators\AbstractServiceDecorator;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;
use OAuth\Common\Token\TokenInterface;
use OAuth\OAuth1\Service\ServiceInterface;

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
     * @return string
     */
    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
        return $this->service->request($path, $method, $body, $extraHeaders);
    }

    /**
     * Retrieves and stores/returns the OAuth1 request token obtained from the service.
     *
     * @return TokenInterface $token
     *
     * @throws TokenResponseException
     */
    public function requestRequestToken()
    {
        return $this->service->requestRequestToken();
    }

    /**
     * Retrieves and stores/returns the OAuth1 access token after a successful authorization.
     *
     * @param string $token The request token from the callback.
     * @param string $verifier
     * @param string $tokenSecret
     *
     * @return TokenInterface $token
     *
     * @throws TokenResponseException
     */
    public function requestAccessToken($token, $verifier, $tokenSecret)
    {
        return $this->service->requestAccessToken($token, $verifier, $tokenSecret);
    }

    /**
     * @return UriInterface
     */
    public function getRequestTokenEndpoint()
    {
        return $this->service->getRequestTokenEndpoint();
    }
}