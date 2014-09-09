<?php

namespace spec\LaravelOAuth {

use Illuminate\Config\Repository;
use Illuminate\Routing\UrlGenerator;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Http\Client\StreamClient;
use OAuth\Common\Service\ServiceInterface;
use OAuth\ServiceFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OAuthSpec extends ObjectBehavior
{
    function let(Repository $config, UrlGenerator $url, ServiceFactory $serviceFactory)
    {
        $serviceFactory->createService(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Foo);
        $this->beConstructedWith($serviceFactory, $config, $url);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LaravelOAuth\OAuth');
    }

    function it_creates_a_session_storage()
    {
        $storage = $this->createStorageInstance('Session');
        $storage->shouldHaveType('OAuth\Common\Storage\Session');
    }

    function it_creates_a_memory_storage()
    {
        $storage = $this->createStorageInstance('Memory');
        $storage->shouldHaveType('OAuth\Common\Storage\Memory');
    }

    function it_creates_a_symfony_session_storage()
    {
        $storage = $this->createStorageInstance('SymfonySession');
        $storage->shouldHaveType('OAuth\Common\Storage\SymfonySession');
    }

    function it_creates_a_redis_storage()
    {
        $storage = $this->createStorageInstance('Redis');
        $storage->shouldHaveType('OAuth\Common\Storage\Redis');
    }

    function it_throws_a_storage_class_does_not_exist_exception_when_the_given_storage_does_not_exist()
    {
        $this->shouldThrow('LaravelOAuth\\Exceptions\\StorageClassDoesNotExistException')
            ->duringCreateStorageInstance('Foobar');
    }

    function it_sets_the_curl_http_client(ServiceFactory $serviceFactory)
    {
        $serviceFactory->setHttpClient(new CurlClient())->shouldBeCalled();
        $this->setHttpClient('CurlClient');
    }

    function it_sets_the_stream_http_client(ServiceFactory $serviceFactory)
    {
        $serviceFactory->setHttpClient(new StreamClient())->shouldBeCalled();
        $this->setHttpClient('StreamClient');
    }

    function it_throws_an_http_client_does_not_exist_exception_if_the_given_http_client_does_not_exist()
    {
        $this->shouldThrow('LaravelOAuth\\Exceptions\\HttpClientClassDoesNotExistException')
            ->duringSetHttpClient('Foobar');
    }

    function it_creates_a_consumer_service(Repository $config)
    {
        $this->setupConfigurationExpectations($config);
        $foo = $this->make('Foo');
        $foo->shouldHaveType('OAuth\\Common\\Service\\ServiceInterface');
        $foo->shouldHaveType('LaravelOAuth\\Decorators\\AbstractServiceDecorator');
    }

    function it_decorates_with_dedicated_decorator_if_one_is_available(Repository $config)
    {
        $this->setupConfigurationExpectations($config);
        $fenix = $this->make('Foo');
        $fenix->shouldHaveType('OAuth\\Common\\Service\\ServiceInterface');
        $fenix->shouldHaveType('LaravelOAuth\\Decorators\\AbstractServiceDecorator');
        $fenix->shouldHaveType('LaravelOAuth\\Decorators\\OAuth2\\BaseServiceDecorator');
        $fenix->shouldHaveType('LaravelOAuth\\Decorators\\OAuth2\\FooDecorator');
    }

    function setupConfigurationExpectations(Repository $config, $service = 'Foo', array $override = [])
    {
        /*
        $stub = $this->getOAuthConfigurationStub();
        $stub = array_merge($stub, $override);
        $config->has("laravel-oauth.consumers")->willReturn($stub['consumers'] ? true : false);
        $config->get("laravel-oauth.storage", 'Session')->willReturn($stub['storage']);
        $config->get("laravel-oauth.consumers.$service.client_id")->willReturn($stub['consumers'][$service]['client_id']);
        $config->get("laravel-oauth.consumers.$service.client_secret")->willReturn($stub['consumers'][$service]['client_secret']);
        $config->get("laravel-oauth.consumers.$service.scope", array())->willReturn($stub['consumers'][$service]['scope']);
        $config->get("laravel-oauth.consumers.$service.redirect_url", null)->willReturn($stub['consumers'][$service]['redirect_url']);
        $config->get("laravel-oauth.consumers.$service.automatic_refresh", false)->willReturn($stub['consumers'][$service]['automatic_refresh']);
        */
        $config->has(Argument::any())->willReturn(false);
        $config->get(Argument::any(), 'Session')->willReturn('Session');
        $config->get(Argument::any(), Argument::any())->willReturn(null);

    }

    private function getOAuthConfigurationStub()
    {
        return ['storage' => 'Session', 'consumers' => ['Foo' => ['client_id' => 'foo', 'client_secret' => 'bar', 'scope' => [], 'redirect_url' => 'http://example.com', 'automatic_refresh' => false]]];
    }
}

class Foo implements ServiceInterface {

    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
    }

    public function getAuthorizationUri(array $additionalParameters = array())
    {
    }

    public function getAuthorizationEndpoint()
    {
    }

    public function getAccessTokenEndpoint()
    {
    }

    public function getOAuthVersion()
    {
        return 2;
    }

    public function service()
    {
        return 'Foo';
    }

    public function requestAccessToken($code)
    {
    }
}

}

namespace LaravelOAuth\Decorators\OAuth2 {
    class FooDecorator extends BaseServiceDecorator {}
}