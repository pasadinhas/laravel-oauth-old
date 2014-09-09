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

class OAuth
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;
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

    private $_decorator;
    private $_decorator_namespace;

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
    public function loadConfiguration($service)
    {
        $prefix = $this->getConfigurationPrefix();

        $this->_storage_name        = $this->config->get("{$prefix}storage", 'Session');
        $this->_decorator_namespace = $this->config->get("{$prefix}decorators_namespace", null);
        $this->_client_id           = $this->config->get("{$prefix}consumers.$service.client_id");
        $this->_client_secret       = $this->config->get("{$prefix}consumers.$service.client_secret");
        $this->_scope               = $this->config->get("{$prefix}consumers.$service.scope", array());
        $this->_url                 = $this->config->get("{$prefix}consumers.$service.redirect_url", null);
        $this->_refresh             = $this->config->get("{$prefix}consumers.$service.automatic_refresh", false);
        $this->_decorator           = $this->config->get("{$prefix}consumers.$service.decorator", null);
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
            $prefix = $this->getConfigurationPrefix();
            $storageName = $this->config->get("{$prefix}storage", 'Session');
        }

        $storageClass = "\\OAuth\\Common\\Storage\\$storageName";

        if (!class_exists($storageClass)) throw new StorageClassDoesNotExistException();

        return new $storageClass();
    }

    /**
     * @param $url
     *
     * @return Credentials
     */
    private function makeCredentials($url)
    {
        $url = $url ?: $this->_url ?: $this->url->current();
        return new Credentials($this->_client_id, $this->_client_secret, $url);
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

        if (!class_exists($httpClientClass)) throw new HttpClientClassDoesNotExistException;

        $this->serviceFactory->setHttpClient(new $httpClientClass());
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
     * @param $scope
     *
     * @return ServiceInterface
     */
    private function createService($serviceName, $url, $scope)
    {
        list($storage, $credentials, $scope) = $this->createServiceDependencies($url, $scope);

        return $this->serviceFactory->createService($serviceName, $credentials, $storage, $scope);
    }

    /**
     * @param $url
     * @param $scope
     *
     * @return array
     */
    private function createServiceDependencies($url, $scope)
    {
        $storage = $this->makeStorage($this->_storage_name);

        $credentials = $this->makeCredentials($url);

        $scope = $scope ? : $this->_scope;

        return array($storage, $credentials, $scope);
    }

    /**
     * @param ServiceInterface $service
     * @param                  $decorator
     *
     * @throws DecoratorClassDoesNotExistException
     * @return AbsractServiceDecorator
     */
    private function decorateService(ServiceInterface $service, $decorator)
    {
        if (!empty($decorator))
        { var_dump("\n\n\nWE HAVE A PARAM DECORATOR \n\n\n");
            $this->validateDecoratorClass($decorator);
        }
        elseif ($this->_decorator) { var_dump("\n\n\nWE HAVE A CONFIG DECORATOR \n\n\n");
            $decorator = $this->_decorator;
            $this->validateDecoratorClass($decorator);
        }
        else
        {
            if ($this->_decorator_namespace)
            {
                $decorator = $this->getUserDecoratorClassNameForService($this->_decorator_namespace, $service) ?:
                    $this->getDefaultDecoratorClassNameForService($service);
            }
            else
            {
                $decorator = $this->getDefaultDecoratorClassNameForService($service);
            }
        }

        return new $decorator($service, $this->_refresh);
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

    private function getUserDecoratorClassNameForService($path, $service)
    {
        $name = $service->service();
        $class = "{$path}\\{$name}Decorator";

        return class_exists($class) ? $class : false;
    }

    /**
     * @return string
     */
    private function getConfigurationPrefix()
    {
        if ($this->config->has('laravel-oauth.consumers')) {
            $prefix = 'laravel-oauth.';
            return $prefix;
        } else {
            $prefix = 'laravel-oauth::';
            return $prefix;
        }
    }
}
