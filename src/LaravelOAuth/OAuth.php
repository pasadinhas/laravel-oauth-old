<?php
/**
 * @author     Miguel Pasadinhas <miguel.pasadinhas@tecnico.ulisboa.pt>
 * @copyright  Copyright (c) 2014
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 */

namespace LaravelOAuth;

use Illuminate\Config\Repository as Configuration;
use Illuminate\Routing\UrlGenerator as URL;
use LaravelOAuth\Decorators\AbstractServiceDecorator;
use LaravelOAuth\Exceptions\HttpClientClassDoesNotExistException;
use LaravelOAuth\Exceptions\StorageClassDoesNotExistException;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Service\ServiceInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\ServiceFactory;

class OAuth
{
    /**
     * @var ServiceFactory
     */
    private $_serviceFactory;
    /**
     * Storage name from config
     * @var string
     */
    private $_storage_name = 'Session';
    /**
     * Client ID from config
     * @var string
     */
    private $_client_id;
    /**
     * Client secret from config
     * @var string
     */
    private $_client_secret;
    /**
     * Scope from config
     * @var array
     */
    private $_scope = array();
    /**
     * Redirect URL from config
     * @var string
     */
    private $_url;
    /**
     * Weather the token should be automagically refreshed from config
     * @var boolean
     */
    private $_refresh;
    /**
     * @var \Illuminate\Config\Repository
     */
    private $config;
    /**
     * @var \Illuminate\Routing\UrlGenerator
     */
    private $url;

    /**
     * Constructor
     *
     * @param \Illuminate\Config\Repository    $config
     * @param \Illuminate\Routing\UrlGenerator $url
     * @param ServiceFactory                   $serviceFactory - (Dependency injection) If not provided, a ServiceFactory instance will be constructed.
     */
    public function __construct(Configuration $config, URL $url, ServiceFactory $serviceFactory = null)
    {
        if (null === $serviceFactory) {
            // Create the service factory
            $serviceFactory = new ServiceFactory();
        }
        $this->_serviceFactory = $serviceFactory;
        $this->config = $config;
        $this->url = $url;
    }

    /**
     * Detect config and set data from it
     *
     * @param string $service
     */
    public function setConfig($service)
    {
        // if config/laravel-oauth.php exists use this one
        if ($this->config->get('laravel-oauth.consumers') != null) {
            $this->_storage_name  = $this->config->get('laravel-oauth.storage', 'Session');
            $this->_client_id     = $this->config->get("laravel-oauth.consumers.$service.client_id");
            $this->_client_secret = $this->config->get("laravel-oauth.consumers.$service.client_secret");
            $this->_scope         = $this->config->get("laravel-oauth.consumers.$service.scope", array());
            $this->_url           = $this->config->get("laravel-oauth.consumers.$service.redirect_url", null);
            $this->_refresh       = $this->config->get("laravel-oauth.consumers.$service.automatic_refresh", false);
            // else try to find config in packages configs
        } else {
            $this->_storage_name  = $this->config->get('laravel-oauth::storage', 'Session');
            $this->_client_id     = $this->config->get("laravel-oauth::consumers.$service.client_id");
            $this->_client_secret = $this->config->get("laravel-oauth::consumers.$service.client_secret");
            $this->_scope         = $this->config->get("laravel-oauth::consumers.$service.scope", array());
            $this->_url           = $this->config->get("laravel-oauth::consumers.$service.redirect_url", null);
            $this->_refresh       = $this->config->get("laravel-oauth::consumers.$service.automatic_refresh", false);
        }
    }

    /**
     * Create storage instance
     *
     * @param string $storageName
     *
     * @throws Exceptions\StorageClassDoesNotExistException
     * @return TokenStorageInterface
     */
    public function createStorageInstance($storageName)
    {
        $storageClass = "\\OAuth\\Common\\Storage\\$storageName";

        if ( ! class_exists($storageClass))
            throw new StorageClassDoesNotExistException();

        return new $storageClass();
    }

    /**
     * Set the http client object
     *
     * @param string $httpClientName
     *
     * @throws Exceptions\HttpClientClassDoesNotExistException
     * @return void
     */
    public function setHttpClient($httpClientName)
    {
        $httpClientClass = "\\OAuth\\Common\\Http\\Client\\$httpClientName";

        if ( ! class_exists($httpClientClass))
            throw new HttpClientClassDoesNotExistException;

        $this->_serviceFactory->setHttpClient(new $httpClientClass());
    }

    /**
     * @param  string                             $serviceName
     * @param  string                             $url
     * @param  array                              $scope
     *
     * @param Decorators\AbstractServiceDecorator $decorator
     *
     * @return AbstractServiceDecorator
     */
    public function consumer($serviceName, $url = null, $scope = null, AbstractServiceDecorator $decorator = null)
    {
        // get config
        $this->setConfig($serviceName);

        // get storage object
        $storage = $this->createStorageInstance($this->_storage_name);

        // create credentials object
        $credentials = new Credentials($this->_client_id, $this->_client_secret, $url ? : $this->_url ? : $this->url->current());

        // check if scopes were provided
        if (is_null($scope)) {
            // get scope from config (default to empty array)
            $scope = $this->_scope;
        }

        // return the service consumer object
        $service = $this->_serviceFactory->createService($serviceName, $credentials, $storage, $scope);

        return $this->decorateService($service, $serviceName, $decorator);
    }

    private function decorateService(ServiceInterface $service, $serviceName, AbstractServiceDecorator $decorator = null)
    {
        $class = $decorator ? : $this->getDecoratorClassNameForService($service, $serviceName);
        return new $class($service, $this->_refresh);
    }

    private function getDecoratorClassNameForService(ServiceInterface $service, $name)
    {
        $version = $service->getOAuthVersion();

        $class = $this->getDedicatedDecoratorClassNameForService($name, $version);

        if (class_exists($class)) {
            return $class;
        } else {
            return "LaravelOAuth\\Decorators\\OAuth{$version}\\BaseServiceDecorator";
        }
    }

    /**
     * @param $name
     * @param $version
     *
     * @return string
     */
    private function getDedicatedDecoratorClassNameForService($name, $version)
    {
        return "LaravelOAuth\\Decorators\\OAuth{$version}\\{$name}Decorator";
    }
}
