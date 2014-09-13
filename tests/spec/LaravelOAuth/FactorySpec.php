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

class FactorySpec extends ObjectBehavior
{
    function let(Repository $config, UrlGenerator $url, ServiceFactory $serviceFactory)
    {
        $serviceFactory->createService(Argument::any(), Argument::any(), Argument::any(), Argument::any())
            ->willReturn(new Foo);
        $serviceFactory->setHttpClient(Argument::any())->willReturn(true);
        $this->beConstructedWith($serviceFactory, $config, $url);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LaravelOAuth\Factory');
    }

    function it_creates_a_session_storage()
    {
        $storage = $this->makeStorage('Session');
        $storage->shouldHaveType('OAuth\Common\Storage\Session');
    }

    function it_creates_a_memory_storage()
    {
        $storage = $this->makeStorage('Memory');
        $storage->shouldHaveType('OAuth\Common\Storage\Memory');
    }

    function it_throws_a_storage_class_does_not_exist_exception_when_the_given_storage_does_not_exist()
    {
        $this->shouldThrow('LaravelOAuth\\Exceptions\\StorageClassDoesNotExistException')
            ->duringMakeStorage('Foobar');
    }

    function it_sets_the_curl_http_client(ServiceFactory $serviceFactory, Repository $config)
    {
        $serviceFactory->setHttpClient(new CurlClient())->shouldBeCalled();

        $this->mockConfig($config, ['client' => 'CurlClient']);
        $this->make('Foo');
    }

    function it_sets_the_stream_http_client(ServiceFactory $serviceFactory, Repository $config)
    {
        $serviceFactory->setHttpClient(new StreamClient())->shouldBeCalled();

        $this->mockConfig($config, ['client' => 'StreamClient']);
        $this->make('Foo');
    }

    function it_throws_an_http_client_does_not_exist_exception_if_the_given_http_client_does_not_exist(Repository $config)
    {
        $this->mockConfig($config, ['client' => 'Foobar']);

        $this->shouldThrow('LaravelOAuth\\Exceptions\\HttpClientClassDoesNotExistException')
            ->duringMake('Foo');
    }

    function it_creates_a_consumer_service(Repository $config)
    {
        $this->mockConfig($config);
        $foo = $this->make('Foo');

        $foo->shouldHaveType('OAuth\\Common\\Service\\ServiceInterface');
        $foo->shouldHaveType('LaravelOAuth\\Decorators\\AbstractServiceDecorator');
    }

    function it_decorates_with_a_decorator_class_passed_by_argument(Repository $config)
    {
        $this->mockConfig($config);
        $foo = $this->make('Foo', null, null, 'Acme\Decorators\FooBarBaz');
        $foo->shouldHaveType('OAuth\\Common\\Service\\ServiceInterface');
        $foo->shouldHaveType('Acme\\Decorators\\FooBarBaz');
    }

    function it_decorates_with_a_custom_decorator_set_in_configuration(Repository $config)
    {
        $this->mockConfig($config, ['consumers' => ['Foo' => ['decorator' => 'Acme\Decorators\FooBarBaz']]]);
        $foo = $this->make('Foo');
        $foo->shouldHaveType('OAuth\\Common\\Service\\ServiceInterface');
        $foo->shouldHaveType('Acme\\Decorators\\FooBarBaz');
    }

    function it_finds_decorators_in_the_specified_decorators_namespace(Repository $config)
    {
        $this->mockConfig($config, ['decorators' => 'Acme\\Decorators']);
        $foo = $this->make('Foo');
        $foo->shouldHaveType('OAuth\\Common\\Service\\ServiceInterface');
        $foo->shouldHaveType('Acme\\Decorators\\FooDecorator');
    }

    function it_decorates_with_dedicated_package_decorator_if_one_is_available(Repository $config)
    {
        $this->mockConfig($config);
        $foo = $this->make('Foo');
        $foo->shouldHaveType('OAuth\\Common\\Service\\ServiceInterface');
        $foo->shouldHaveType('LaravelOAuth\\Decorators\\OAuth2\\FooDecorator');
    }

    function it_registers_services_from_configuration(Repository $config, ServiceFactory $serviceFactory)
    {
        $serviceFactory->registerService('FooBarBaz', 'Acme\\FooBarBaz')->shouldBeCalled();
        $serviceFactory->registerService('Example', 'Acme\\Example')->shouldBeCalled();

        $this->mockConfig($config, ['services' => [
            'Acme\\FooBarBaz',
            'Acme\\Example'
        ]]);

        $this->registerServices();
    }
    
    function mockConfig(Repository $config, array $override = [], $service = 'Foo')
    {
        $stub = array_merge($this->getConfigStub($service), $override);

        $config->get('laravel-oauth::storage', Argument::cetera())->willReturn(isset($stub['storage'] ) ? $stub['storage']  : null);
        $config->get('laravel-oauth::client', Argument::cetera())->willReturn(isset($stub['client'] ) ? $stub['client']  : null);
        $config->get('laravel-oauth::services', Argument::cetera())->willReturn(isset($stub['services'] ) ? $stub['services']  : null);
        $config->get('laravel-oauth::decorators', Argument::cetera())->willReturn(isset($stub['decorators'] ) ? $stub['decorators']  : null);
        $config->get('laravel-oauth::consumers.'.$service.'.client.id', Argument::cetera())->willReturn(isset($stub['consumers'][$service]['client.id'] ) ? $stub['consumers'][$service]['client.id']  : null);
        $config->get('laravel-oauth::consumers.'.$service.'.client.secret', Argument::cetera())->willReturn(isset($stub['consumers'][$service]['client.secret'] ) ? $stub['consumers'][$service]['client.secret']  : null);
        $config->get('laravel-oauth::consumers.'.$service.'.scopes', Argument::cetera())->willReturn(isset($stub['consumers'][$service]['scopes'] ) ? $stub['consumers'][$service]['scopes']  : null);
        $config->get('laravel-oauth::consumers.'.$service.'.redirect', Argument::cetera())->willReturn(isset($stub['consumers'][$service]['redirect'] ) ? $stub['consumers'][$service]['redirect']  : null);
        $config->get('laravel-oauth::consumers.'.$service.'.refresh', Argument::cetera())->willReturn(isset($stub['consumers'][$service]['refresh'] ) ? $stub['consumers'][$service]['refresh']  : null);
        $config->get('laravel-oauth::consumers.'.$service.'.decorator', Argument::cetera())->willReturn(isset($stub['consumers'][$service]['decorator'] ) ? $stub['consumers'][$service]['decorator']  : null);
    }

    private function getConfigStub($service)
    {

        return [
            'storage' => 'Session',
            'client' => 'StreamClient',

    //        'services' => [
    //            'Fully\Qualified\Service\ExampleClass',
    //        ],
    //
    //        'decorators' => 'Decorators\Namespace',

            'consumers' => [

                $service => [
                    'client.id' => 'foo',
                    'client.secret' => 'bar',
                    'redirect' => 'http://localhost:8000/login',
                    'scopes' => [],
                ],

            ]
        ];
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

namespace Acme\Decorators {
    use LaravelOAuth\Decorators\OAuth2\BaseServiceDecorator;
    class FooBarBaz extends BaseServiceDecorator {}
    class FooDecorator extends BaseServiceDecorator {}
}