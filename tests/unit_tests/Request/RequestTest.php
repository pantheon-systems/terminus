<?php

namespace Pantheon\Terminus\UnitTests\Request;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Session\Session;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class RequestTest
 * Testing class for Pantheon\Terminus\Request\Request
 * @package Pantheon\Terminus\UnitTests\Request
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $client;
    /**
     * @var TerminusConfig
     */
    protected $config;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var HttpRequest
     */
    protected $http_request;
    /**
     * @var LocalMachineHelper
     */
    protected $local_machine_helper;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var Session
     */
    protected $session;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->request = new Request();
        $this->http_request = $this->getMockBuilder(HttpRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->client = $this->getMock(Client::class);
        $this->local_machine_helper = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = new TerminusConfig();
        $this->config->set('host', 'example.com');
        $this->config->set('protocol', 'https');
        $this->config->set('port', '443');
        $this->config->set('version', '1.1.1');
        $this->config->set('script', 'foo/bar/baz.php');
        $this->config->set('php_version', '7.0.0');

        $this->container = $this->getMock(Container::class);
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMock(LoggerInterface::class);

        $this->request->setContainer($this->container);
        $this->request->setConfig($this->config);
        $this->request->setSession($this->session);
        $this->request->setLogger($this->logger);
    }

    /**
     * Tests a successful download
     */
    public function testDownload()
    {
        $domain = 'pantheon.io';
        $url = "http://$domain/somefile.tar.gz";
        $target = 'some local path';

        $this->container->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo(LocalMachineHelper::class))
            ->willReturn($this->local_machine_helper);
        $this->local_machine_helper->expects($this->once())
            ->method('getFilesystem')
            ->with()
            ->willReturn($this->filesystem);
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($target)
            ->willReturn(false);
        $this->container->expects($this->at(1))
            ->method('get')
            ->with(
                $this->equalTo(Client::class),
                $this->equalTo([['base_uri' => $domain, RequestOptions::VERIFY => true,],])
            )
            ->willReturn($this->client);
        $this->client->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('GET'),
                $this->equalTo($url),
                $this->equalTo(['sink' => $target,])
            );

        $out = $this->request->download($url, $target);
        $this->assertNull($out);
    }

    /**
     * Tests an unsuccessful download because the target file already exists
     */
    public function testDownloadPathExists()
    {
        $domain = 'pantheon.io';
        $url = "http://$domain/somefile.tar.gz";
        $target = 'some local path';

        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo(LocalMachineHelper::class))
            ->willReturn($this->local_machine_helper);
        $this->local_machine_helper->expects($this->once())
            ->method('getFilesystem')
            ->with()
            ->willReturn($this->filesystem);
        $this->filesystem->expects($this->once())
            ->method('exists')
            ->with($target)
            ->willReturn(true);
        $this->client->expects($this->never())
            ->method('request');

        $this->setExpectedException(TerminusException::class, "Target file $target already exists.");

        $out = $this->request->download($url, $target);
        $this->assertNull($out);
    }

    public function testRequest()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $client_options = ['base_uri' => 'https://example.com:443', RequestOptions::VERIFY => true];

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $headers = [
            'foo' => 'bar',
            'Content-type' => 'application/json',
            'User-Agent' => 'Terminus/1.1.1 (php_version=7.0.0&script=foo/bar/baz.php)'
        ];
        $body = '';
        $request_options = [$method, $uri, $headers, $body];
        $actual = $this->makeRequest($client_options, $request_options, 'foo/bar', ['headers' => ['foo' => 'bar']]);
        $expected = [
            'data' => (object)['abc' => '123'],
            'headers' => ['Content-type' => 'application/json'],
            'status_code' => 200,
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testRequestAuth()
    {
        $this->session->method('get')->with('session')->willReturn('abc123');

        $client_options = ['base_uri' => 'https://example.com:443', RequestOptions::VERIFY => true];
        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $headers = [
            'Content-type' => 'application/json',
            'User-Agent' => 'Terminus/1.1.1 (php_version=7.0.0&script=foo/bar/baz.php)',
            'Authorization' => 'Bearer abc123'
        ];
        $body = '';
        $request_options = [$method, $uri, $headers, $body];
        $this->makeRequest($client_options, $request_options, 'foo/bar');
    }

    public function testRequestFullPath()
    {
        $this->config->set('verify_host_cert', false);
        $this->session->method('get')->with('session')->willReturn('abc123');

        $client_options = ['base_uri' => 'https://example.com:443', RequestOptions::VERIFY => false];

        $method = 'GET';
        $uri = 'http://foo.bar/a/b/c';
        $headers = [
            'Content-type' => 'application/json',
            'User-Agent' => 'Terminus/1.1.1 (php_version=7.0.0&script=foo/bar/baz.php)'
        ];
        $body = '';
        $request_options = [$method, $uri, $headers, $body];
        $this->makeRequest($client_options, $request_options, 'http://foo.bar/a/b/c');
    }

    public function testRequestWithQuery()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $client_options = ['base_uri' => 'https://example.com:443', RequestOptions::VERIFY => true];

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar?foo=bar';
        $headers = [
          'Content-type' => 'application/json',
          'User-Agent' => 'Terminus/1.1.1 (php_version=7.0.0&script=foo/bar/baz.php)'
        ];
        $body = '';
        $request_options = [$method, $uri, $headers, $body];
        $actual = $this->makeRequest($client_options, $request_options, 'foo/bar', ['query' => ['foo' => 'bar']]);
        $expected = [
          'data' => (object)['abc' => '123'],
          'headers' => ['Content-type' => 'application/json'],
          'status_code' => 200,
        ];
        $this->assertEquals($expected, $actual);
    }


    public function testRequestNoVerify()
    {
        $this->config->set('verify_host_cert', false);
        $this->session->method('get')->with('session')->willReturn(false);

        $client_options = ['base_uri' => 'https://example.com:443', RequestOptions::VERIFY => false];

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $headers = [
            'Content-type' => 'application/json',
            'User-Agent' => 'Terminus/1.1.1 (php_version=7.0.0&script=foo/bar/baz.php)'
        ];
        $body = '';
        $request_options = [$method, $uri, $headers, $body];
        $this->makeRequest($client_options, $request_options, 'foo/bar');
    }

    public function testPagedRequest()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $client_options = ['base_uri' => 'https://example.com:443', RequestOptions::VERIFY => true];

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $headers = [
            'Content-type' => 'application/json',
            'User-Agent' => 'Terminus/1.1.1 (php_version=7.0.0&script=foo/bar/baz.php)',
        ];
        $body = '';
        $request_options = [$method, $uri, $headers, $body];

        $expected_options = $request_options;
        $expected_options[1] .= '?limit=' . Request::PAGED_REQUEST_ENTRY_LIMIT;

        $this->container->expects($this->at(0))
            ->method('get')
            ->with(Client::class, [$client_options])
            ->willReturn($this->client);
        $this->container->expects($this->at(1))
            ->method('get')
            ->with(HttpRequest::class, $expected_options)
            ->willReturn($this->http_request);

        $message = $this->getMock(Response::class);
        $body = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $body->method('getContents')->willReturn(json_encode(['abc' => (object)['id' => 'abc123',],]));
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $message->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Content-type' => 'application/json']);
        $message->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $this->client->expects($this->once())
            ->method('send')
            ->with($this->http_request)
            ->willReturn($message);

        $actual = $this->request->pagedRequest($uri, $request_options);
        $expected = [
            'data' => [(object)['id' => 'abc123',],],
        ];
        $this->assertEquals($expected, $actual);
    }

    private function makeRequest($client_options, $request_options, $url, $options = [])
    {
        $this->container->expects($this->at(0))
            ->method('get')
            ->with(Client::class, [$client_options])
            ->willReturn($this->client);
        $this->container->expects($this->at(1))
            ->method('get')
            ->with(HttpRequest::class, $request_options)
            ->willReturn($this->http_request);

        $message = $this->getMock(Response::class);
        $body = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $body->method('getContents')->willReturn(json_encode(['abc' => '123']));
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $message->expects($this->once())
            ->method('getHeaders')
            ->willReturn(['Content-type' => 'application/json']);
        $message->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $this->client->expects($this->once())
            ->method('send')
            ->with($this->http_request)
            ->willReturn($message);

        return $this->request->request($url, $options);
    }
}
