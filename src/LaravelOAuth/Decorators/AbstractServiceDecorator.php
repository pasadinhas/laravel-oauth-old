<?php namespace LaravelOAuth\Decorators;

use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Service\ServiceInterface;

abstract class AbstractServiceDecorator implements ServiceInterface
{
    /**
     * @var \OAuth\Common\Service\ServiceInterface
     */
    protected $service;
    /**
     * @var bool
     */
    protected $refresh;

    /**
     * Construct the Service decorator.
     *
     * @param ServiceInterface $service
     * @param bool             $refresh
     */
    public function __construct(ServiceInterface $service, $refresh = false)
    {
        $this->service = $service;
        $this->refresh = $refresh;

        if (method_exists($this, 'bootstrap'))
        {
            $this->bootstrap();
        }
    }

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
    abstract public function request($path, $method = 'GET', $body = null, array $extraHeaders = array());

    /**
     * Returns the url to redirect to for authorization purposes.
     *
     * @param array $additionalParameters
     *
     * @return UriInterface
     */
    public function getAuthorizationUri(array $additionalParameters = array())
    {
        return $this->service->getAuthorizationUri($additionalParameters);
    }

    /**
     * Returns the authorization API endpoint.
     *
     * @return UriInterface
     */
    public function getAuthorizationEndpoint()
    {
        return $this->service->getAuthorizationEndpoint();
    }

    /**
     * Returns the access token API endpoint.
     *
     * @return UriInterface
     */
    public function getAccessTokenEndpoint()
    {
        return $this->service->getAccessTokenEndpoint();
    }

    /**
     * Returns the OAuth API version of the Service. (1 or 2)
     *
     * @return integer
     */
    public function getOAuthVersion()
    {
        return $this->service->getOAuthVersion();
    }

    /**
     * Alias to perform a request and json decode the result.
     *
     * @param        $path
     * @param string $method
     * @param null   $body
     * @param array  $extraHeaders
     *
     * @return mixed
     */
    public function requestJSON($path, $method = 'GET', $body = null, array $extraHeaders = [])
    {
        return json_decode($this->request($path, $method, $body, $extraHeaders));
    }

    /**
     * Give the developer a chance to bootstrap their custom decorators without
     * the need of overriding the constructor.
     */
    protected function bootstrap()
    {

    }
}