<?php
/**
 * @author     Miguel Pasadinhas <miguel.pasadinhas@tecnico.ulisboa.pt>
 * @copyright  Copyright (c) 2014
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 */

namespace LaravelOAuth;

use Illuminate\Config\Repository;
use Illuminate\Routing\UrlGenerator;
use LaravelOAuth\Decorators\AbsractServiceDecorator;
use LaravelOAuth\Decorators\AbstractServiceDecorator;
use LaravelOAuth\Decorators\Exceptions\DecoratorClassDoesNotExistException;
use LaravelOAuth\Exceptions\HttpClientClassDoesNotExistException;
use LaravelOAuth\Exceptions\StorageClassDoesNotExistException;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Service\ServiceInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\ServiceFactory;

class Factory
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var \Illuminate\Config\Repository
     */
    private $config;

    /**
     * @var \Illuminate\Routing\UrlGenerator
     */
    private $url;

    private $storage = 'Session';
    private $client = 'StreamClient';
    private $client_id;
    private $client_secret;
    private $scopes = [];
    private $redirect_url;
    private $refresh;
    private $decorator;
    private $decorators_namespace;
    private $services_are_registered = false;

    /**
     * Constructor
     *
     * @param ServiceFactory $serviceFactory
     * @param Repository     $config
     * @param UrlGenerator   $url
     */
    public function __construct(ServiceFactory $serviceFactory, Repository $config, UrlGenerator $url)
    {
        $this->serviceFactory = $serviceFactory;
        $this->config          = $config;
        $this->url             = $url;
    }

    /**
     * Detect config and set data from it
     *
     * @param string $service
     */
    private function loadConfiguration($service)
    {
        $this->storage              = $this->config->get("laravel-oauth::storage", 'Session');
        $this->client               = $this->config->get("laravel-oauth::client", 'StreamClient');
        $this->decorators_namespace = $this->config->get("laravel-oauth::decorators");
        $this->client_id            = $this->config->get("laravel-oauth::consumers.$service.client.id");
        $this->client_secret        = $this->config->get("laravel-oauth::consumers.$service.client.secret");
        $this->scopes               = $this->config->get("laravel-oauth::consumers.$service.scopes", []);
        $this->redirect_url         = $this->config->get("laravel-oauth::consumers.$service.redirect");
        $this->refresh              = $this->config->get("laravel-oauth::consumers.$service.refresh", false);
        $this->decorator            = $this->config->get("laravel-oauth::consumers.$service.decorator");
    }

    /**
     * Create storage instance
     *
     * @param string $storageName
     *
     * @throws Exceptions\StorageClassDoesNotExistException
     * @return TokenStorageInterface
     */
    public function makeStorage($storageName = null)
    {
        if ( ! $storageName )
        {
            $storageName = $this->config->get("laravel-oauth::storage", 'Session');
        }

        $storageClass = "\\OAuth\\Common\\Storage\\$storageName";

        if ( ! class_exists($storageClass))
            throw new StorageClassDoesNotExistException();

        return new $storageClass();
    }

    /**
     * @param $url
     *
     * @return Credentials
     */
    private function makeCredentials($url)
    {
        $url = $url ?: $this->redirect_url ?: $this->url->current();
        return new Credentials($this->client_id, $this->client_secret, $url);
    }

    /**
     * Set the http client object
     *
     * @param string $httpClientName
     *
     * @throws Exceptions\HttpClientClassDoesNotExistException
     * @return void
     */
    private function setHttpClient($httpClientName = null)
    {
        if ( ! $httpClientName)
        {
            $httpClientName = $this->config->get('laravel-oauth::client', 'StreamClient');
        }

        $httpClientClass = "\\OAuth\\Common\\Http\\Client\\$httpClientName";

        if ( ! class_exists($httpClientClass))
            throw new HttpClientClassDoesNotExistException;

        $this->serviceFactory->setHttpClient(new $httpClientClass());

        return $this;
    }

    /**
     * Registers the services from configuration
     */
    public function registerServices()
    {
        if ($this->services_are_registered) return;

        $services = $this->config->get('laravel-oauth::services');

        if ($services)
        {
            foreach ($services as $service)
            {
                $this->register($service);
            }
        }

        $this->services_are_registered = true;
    }

    public function register($class)
    {
        // gets class short name
        $name = preg_replace('/^.*\\\\/', '', $class);

        $this->serviceFactory->registerService($name, $class);
    }

    /**
     * @param  string $serviceName
     * @param  string $url
     * @param  array  $scope
     *
     * @param null    $decorator
     *
     * @return AbstractServiceDecorator
     */
    public function make($serviceName, $url = null, $scope = null, $decorator = null)
    {
        $this->loadConfiguration($serviceName);

        $service = $this->createService($serviceName, $url, $scope);

        return $this->decorateService($service, $decorator);
    }

    /**
     * @param $serviceName
     * @param $url
     * @param $scopes
     *
     * @return ServiceInterface
     */
    private function createService($serviceName, $url, $scopes)
    {
        $storage = $this->makeStorage($this->storage);
        $credentials = $this->makeCredentials($url);
        $scopes = $scopes ?: $this->scopes;

        $this->setHttpClient();

        return $this->serviceFactory->createService($serviceName, $credentials, $storage, $scopes);
    }

    /**
     * @param ServiceInterface $service
     * @param                  $decorator
     *
     * @throws DecoratorClassDoesNotExistException
     * @return AbstractServiceDecorator
     */
    private function decorateService(ServiceInterface $service, $decorator)
    {
        if (!empty($decorator))
        {
            $this->validateDecoratorClass($decorator);
            return $this->makeDecorator($service, $decorator);
        }

        if ($this->decorator) {
            $this->validateDecoratorClass($this->decorator);
            return $this->makeDecorator($service, $this->decorator);
        }

        if ($this->decorators_namespace)
        {
            $namespace = $this->decorators_namespace;
            $class = $this->getUserDecoratorClassNameForService($namespace, $service);
            if ($class)
            {
                return $this->makeDecorator($service, $class);
            }
        }

        $class = $this->getDefaultDecoratorClassNameForService($service);
        return $this->makeDecorator($service, $class);
    }

    /**
     * @param $decorator
     *
     * @throws Decorators\Exceptions\DecoratorClassDoesNotExistException
     */
    private function validateDecoratorClass($decorator)
    {
        if (!class_exists($decorator))
            throw new DecoratorClassDoesNotExistException("Decorator Class [$decorator] does not exist.");
    }

    private function getDefaultDecoratorClassNameForService(ServiceInterface $service)
    {
        $version = $service->getOAuthVersion();
        $name = $service->service();

        $class = "LaravelOAuth\\Decorators\\OAuth{$version}\\{$name}Decorator";

        if (class_exists($class))
        {
            return $class;
        }

        return "LaravelOAuth\\Decorators\\OAuth{$version}\\BaseServiceDecorator";
    }

    private function getUserDecoratorClassNameForService($path, $service)
    {
        $name = $service->service();
        $class = "{$path}\\{$name}Decorator";

        return class_exists($class) ? $class : false;
    }

    /**
     * @param ServiceInterface $service
     * @param                  $class
     *
     * @return mixed
     */
    private function makeDecorator(ServiceInterface $service, $class)
    {
        return new $class($service, $this->refresh);
    }
}
