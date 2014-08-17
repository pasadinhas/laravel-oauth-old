<?php

namespace spec\LaravelOAuth;

use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Http\Client\StreamClient;
use OAuth\ServiceFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OAuthSpec extends ObjectBehavior
{
    function let(ServiceFactory $serviceFactory)
    {
        $this->beConstructedWith($serviceFactory);
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
    
}
