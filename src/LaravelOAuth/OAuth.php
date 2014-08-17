<?php
/**
 * @author     Miguel Pasadinhas <miguel.pasadinhas@tecnico.ulisboa.pt>
 * @copyright  Copyright (c) 2014
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 */

namespace LaravelOAuth;

use Config;
use LaravelOAuth\Decorators\AbstractServiceDecorator;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Service\ServiceInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\ServiceFactory;
use URL;

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
     * Constructor
     *
     * @param ServiceFactory $serviceFactory - (Dependency injection) If not provided, a ServiceFactory instance will be constructed.
     */
    public function __construct(ServiceFactory $serviceFactory = null)
    {
        if (null === $serviceFactory) {
            // Create the service factory
            $serviceFactory = new ServiceFactory();
        }
        $this->_serviceFactory = $serviceFactory;
    }

    /**
     * Detect config and set data from it
     *
     * @param string $service
     */
    public function setConfig($service)
    {
        // if config/laravel-oauth.php exists use this one
        if (Config::get('laravel-oauth.consumers') != null) {
            $this->_storage_name  = Config::get('laravel-oauth.storage', 'Session');
            $this->_client_id     = Config::get("laravel-oauth.consumers.$service.client_id");
            $this->_client_secret = Config::get("laravel-oauth.consumers.$service.client_secret");
            $this->_scope         = Config::get("laravel-oauth.consumers.$service.scope", array());
            $this->_url           = Config::get("laravel-oauth.consumers.$service.redirect_url", null);
            $this->_refresh       = Config::get("laravel-oauth.consumers.$service.automatic_refresh", false);
            // else try to find config in packages configs
        } else {
            $this->_storage_name  = Config::get('laravel-oauth::storage', 'Session');
            $this->_client_id     = Config::get("laravel-oauth::consumers.$service.client_id");
            $this->_client_secret = Config::get("laravel-oauth::consumers.$service.client_secret");
            $this->_scope         = Config::get("laravel-oauth::consumers.$service.scope", array());
            $this->_url           = Config::get("laravel-oauth::consumers.$service.redirect_url", null);
            $this->_refresh       = Config::get("laravel-oauth::consumers.$service.automatic_refresh", false);
        }
    }

    /**
     * Create storage instance
     *
     * @param string $storageName
     *
     * @return TokenStorageInterface
     */
    public function createStorageInstance($storageName)
    {
        $storageClass = "\\OAuth\\Common\\Storage\\$storageName";
        $storage      = new $storageClass();

        return $storage;
    }

    /**
     * Set the http client object
     *
     * @param string $httpClientName
     *
     * @return void
     */
    public function setHttpClient($httpClientName)
    {
        $httpClientClass = "\\OAuth\\Common\\Http\\Client\\$httpClientName";
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
        $credentials = new Credentials($this->_client_id, $this->_client_secret, $url ? : $this->_url ? : URL::current());

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
        $oauth   = "OAuth{$version}"; // will be "OAuth1" or "OAuth2"

        // We will check if there is a dedicated decorator for this service
        $class = "LaravelOAuth\\Decorators\\{$oauth}\\{$name}Decorator";

        if (class_exists($class)) {
            return $class;
        } else {
            return "LaravelOAuth\\Decorators\\{$oauth}\\BaseServiceDecorator";
        }
    }
}
