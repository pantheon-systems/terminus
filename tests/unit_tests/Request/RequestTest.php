<?php

namespace Pantheon\Terminus\UnitTests\Request;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Terminus;
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
     * @var array
     */
    protected $client_options;
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
     * @var array
     */
    protected $request_headers;
    /**
     * @var array
     */
    protected $response_data;
    /**
     * @var array
     */
    protected $response_headers;
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

        $terminusVersion = '1.1.1';
        $phpVersion = '7.0.0';
        $script = 'foo/bar/baz.php';
        $platformScript = str_replace('/', DIRECTORY_SEPARATOR, $script);
        $this->client_options = ['base_uri' => 'https://example.com:443', RequestOptions::VERIFY => true,];
        $this->request_headers = $this->response_headers = ['Content-type' => 'application/json',];
        $this->request_headers['User-Agent'] = "Terminus/$terminusVersion (php_version=$phpVersion&script=$platformScript)";
        $this->response_data = ['abc' => '123',];

        $this->config = new TerminusConfig();
        $this->config->set('host', 'example.com');
        $this->config->set('protocol', 'https');
        $this->config->set('port', '443');
        $this->config->set('version', $terminusVersion);
        $this->config->set('script', $script);
        $this->config->set('php_version', $phpVersion);


        $this->config->set('http_retry_delay_ms', 10);
        $this->config->set('http_retry_backoff_multiplier', 2);
        // Remove jitter since it's hard to test randomness.
        $this->config->set('http_retry_jitter_ms', 0);
        $this->config->set('http_max_retries', 3);

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

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $this->request_headers = array_merge($this->request_headers, ['foo' => 'bar',]);
        $request_options = [$method, $uri, $this->request_headers, null,];
        $actual = $this->makeRequest($request_options, 'foo/bar', ['headers' => $this->request_headers,]);
        $expected = [
            'data' => (object)$this->response_data,
            'headers' => $this->response_headers,
            'status_code' => 200,
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testRequestRetryFails()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $this->request_headers = array_merge($this->request_headers);
        $request_options = [$method, $uri, $this->request_headers, null,];

        $this->container->expects($this->at(0))
          ->method('get')
          ->with(Client::class, [$this->client_options,])
          ->willReturn($this->client);
        $this->container->expects($this->at(1))
          ->method('get')
          ->with(HttpRequest::class, $request_options)
          ->willReturn($this->http_request);

        $e = new ServerException('Something bad happened', $this->http_request);

        $this->client->expects($this->exactly(4))
          ->method('send')
          ->with($this->http_request)
          ->will($this->throwException($e));

        foreach ([1 => 10, 2 => 20, 3 => 40] as $at => $sleep) {
            $this->logger->expects($this->at($at))
              ->method('warning')
              ->with(
                  'HTTPS request failed with error {error}. Retrying in {sleep} milliseconds..',
                  [
                  'error' => 'Something bad happened',
                  'sleep' => $sleep
                  ]
              );
        }

        $this->setExpectedException(TerminusException::class, "HTTPS request failed with error Something bad happened. Maximum retry attempts reached.");

        $this->request->request($uri, $request_options);
    }

    public function testRequestDontRetry()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $this->request_headers = array_merge($this->request_headers);
        $request_options = [$method, $uri, $this->request_headers, null,];

        $this->container->expects($this->at(0))
          ->method('get')
          ->with(Client::class, [$this->client_options,])
          ->willReturn($this->client);
        $this->container->expects($this->at(1))
          ->method('get')
          ->with(HttpRequest::class, $request_options)
          ->willReturn($this->http_request);

        $e = new ClientException('Something bad happened. And it is your fault.', $this->http_request);

        $this->client->expects($this->exactly(1))
          ->method('send')
          ->with($this->http_request)
          ->will($this->throwException($e));

        $this->setExpectedException(ClientException::class, "Something bad happened. And it is your fault.");

        $this->request->request($uri, $request_options);
    }


    public function testRequestRetrySucceeds()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $this->request_headers = array_merge($this->request_headers);
        $request_options = [$method, $uri, $this->request_headers, null,];

        $this->container->expects($this->at(0))
          ->method('get')
          ->with(Client::class, [$this->client_options,])
          ->willReturn($this->client);
        $this->container->expects($this->at(1))
          ->method('get')
          ->with(HttpRequest::class, $request_options)
          ->willReturn($this->http_request);

        $e = new ServerException('Something bad happened', $this->http_request);

        $message = $this->getMock(Response::class);
        $body = $this->getMockBuilder(Stream::class)
          ->disableOriginalConstructor()
          ->getMock();
        $body->method('getContents')->willReturn(json_encode($this->response_data));
        $message->expects($this->once())
          ->method('getBody')
          ->willReturn($body);
        $message->expects($this->once())
          ->method('getHeaders')
          ->willReturn($this->response_headers);
        $message->expects($this->once())
          ->method('getStatusCode')
          ->willReturn(200);


        // Fail the first time
        $this->client->expects($this->at(0))
          ->method('send')
          ->with($this->http_request)
          ->will($this->throwException($e));

        // Succeed on retry
        $this->client->expects($this->at(1))
          ->method('send')
          ->with($this->http_request)
          ->willReturn($message);

        $actual = $this->request->request($uri, $request_options);

        $expected = [
          'data' => (object)$this->response_data,
          'headers' => $this->response_headers,
          'status_code' => 200,
        ];
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests Request::request() when there is sensitive information to send to the debug log
     */
    public function testRequestAuth()
    {
        $this->session->method('get')->with('session')->willReturn('abc123');

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $this->request_headers['Authorization'] = 'Bearer abc123';
        $headers = $debug_expected_headers = $this->request_headers;
        $debug_expected_headers['Authorization'] = Request::HIDDEN_VALUE_REPLACEMENT;
        $body = $debug_expected_body = ['machine_token' => 'sometokenhere', 'other_data' => 'hi',];
        $debug_expected_body['machine_token'] = Request::HIDDEN_VALUE_REPLACEMENT;
        $request_options = [$method, $uri, $headers, json_encode($body),];

        $this->logger->expects($this->at(1))
            ->method('debug')
            ->with(
                Request::DEBUG_RESPONSE_STRING,
                [
                    'headers' => json_encode($this->response_headers),
                    'data' => json_encode((object)$this->response_data),
                    'status_code' => 200,
                ]
            );

        $this->makeRequest($request_options, 'foo/bar', ['form_params' => $body,]);
    }

    public function testRequestFullPath()
    {
        $this->config->set('verify_host_cert', false);
        $this->session->method('get')->with('session')->willReturn('abc123');

        $this->client_options[RequestOptions::VERIFY] = false;

        $method = 'GET';
        $uri = 'http://foo.bar/a/b/c';
        $request_options = [$method, $uri, $this->request_headers, null,];
        $this->makeRequest($request_options, 'http://foo.bar/a/b/c');
    }

    public function testRequestNoVerify()
    {
        $this->config->set('verify_host_cert', false);
        $this->session->method('get')->with('session')->willReturn(false);

        $this->client_options[RequestOptions::VERIFY] = false;

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $request_options = [$method, $uri, $this->request_headers, null,];
        $this->makeRequest($request_options, 'foo/bar');
    }

    public function testRequestWithQuery()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar?foo=bar';
        $request_options = [$method, $uri, $this->request_headers, null,];
        $actual = $this->makeRequest($request_options, 'foo/bar', ['query' => ['foo' => 'bar',],]);
        $expected = [
            'data' => (object)$this->response_data,
            'headers' => $this->response_headers,
            'status_code' => 200,
        ];
        $this->assertEquals($expected, $actual);
    }


    /**
     * Test Request::pagedRequest() when the second query's data comes back empty
     */
    public function testPagedRequestWhenSecondQueryEmpty()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $request_options = [$method, $uri, $this->request_headers, null,];

        $prefix = 'abc_';
        $expected_options = $expected_options_2 = $request_options;
        $limit = Request::PAGED_REQUEST_ENTRY_LIMIT;
        $expected_options[1] .= "?limit=$limit";
        $expected_options_2[1] .= "?limit=$limit&start=$prefix" . ($limit - 1);
        $expected_objects = [];
        for ($i = 0; $i < $limit; $i++) {
            $id = $prefix . $i;
            $expected_objects[$id] = (object)compact('id');
        }

        $this->container->expects($this->at(0))
            ->method('get')
            ->with(Client::class, [$this->client_options,])
            ->willReturn($this->client);
        $this->container->expects($this->at(1))
            ->method('get')
            ->with(HttpRequest::class, $expected_options)
            ->willReturn($this->http_request);
        $this->container->expects($this->at(2))
            ->method('get')
            ->with(Client::class, [$this->client_options,])
            ->willReturn($this->client);
        $this->container->expects($this->at(3))
            ->method('get')
            ->with(HttpRequest::class, $expected_options_2)
            ->willReturn($this->http_request);

        $message = $this->getMock(Response::class);
        $body = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $body->expects($this->at(0))
            ->method('getContents')
            ->willReturn(json_encode($expected_objects));
        $body->expects($this->at(1))
            ->method('getContents')
            ->willReturn(json_encode([]));
        $message->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body);
        $message->expects($this->exactly(2))
            ->method('getHeaders')
            ->willReturn($this->response_headers);
        $message->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $this->client->expects($this->exactly(2))
            ->method('send')
            ->with($this->http_request)
            ->willReturn($message);

        $actual = $this->request->pagedRequest($uri, $request_options);
        $this->assertEquals(['data' => $expected_objects,], $actual);
    }

    /**
     * Test Request::pagedRequest() when the second query's data comes back with fewer than the limit of results
     */
    public function testPagedRequestWhenSecondQueryNotFull()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $request_options = [$method, $uri, $this->request_headers, null,];

        $prefix = 'abc_';
        $expected_options = $expected_options_2 = $request_options;
        $limit = Request::PAGED_REQUEST_ENTRY_LIMIT;
        $expected_options[1] .= "?limit=$limit";
        $expected_options_2[1] .= "?limit=$limit&start=$prefix" . ($limit - 1);
        $expected_objects = [];
        for ($i = 0; $i < $limit; $i++) {
            $id = $prefix . $i;
            $expected_objects[$id] = (object)compact('id');
        }
        $expected_objects_2 = [];
        for ($i = $limit; $i < $limit + floor($limit / 2); $i++) {
            $id = $prefix . $i;
            $expected_objects_2[$id] = (object)compact('id');
        }

        $this->container->expects($this->at(0))
            ->method('get')
            ->with(Client::class, [$this->client_options,])
            ->willReturn($this->client);
        $this->container->expects($this->at(1))
            ->method('get')
            ->with(HttpRequest::class, $expected_options)
            ->willReturn($this->http_request);
        $this->container->expects($this->at(2))
            ->method('get')
            ->with(Client::class, [$this->client_options,])
            ->willReturn($this->client);
        $this->container->expects($this->at(3))
            ->method('get')
            ->with(HttpRequest::class, $expected_options_2)
            ->willReturn($this->http_request);

        $message = $this->getMock(Response::class);
        $body = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $body->expects($this->at(0))
            ->method('getContents')
            ->willReturn(json_encode($expected_objects));
        $body->expects($this->at(1))
            ->method('getContents')
            ->willReturn(json_encode($expected_objects_2));
        $message->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body);
        $message->expects($this->exactly(2))
            ->method('getHeaders')
            ->willReturn($this->response_headers);
        $message->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $this->client->expects($this->exactly(2))
            ->method('send')
            ->with($this->http_request)
            ->willReturn($message);

        $actual = $this->request->pagedRequest($uri, $request_options);
        $this->assertEquals(['data' => $expected_objects + $expected_objects_2,], $actual);
    }

    /**
     * Test Request::pagedRequest() when the second query's data comes back with the same ending obj as the first
     */
    public function testPagedRequestWhenSecondQueryRepeats()
    {
        $this->session->method('get')->with('session')->willReturn(false);

        $method = 'GET';
        $uri = 'https://example.com:443/api/foo/bar';
        $request_options = [$method, $uri, $this->request_headers, null,];

        $prefix = 'abc_';
        $expected_options = $expected_options_2 = $request_options;
        $limit = Request::PAGED_REQUEST_ENTRY_LIMIT;
        $expected_options[1] .= "?limit=$limit";
        $expected_options_2[1] .= "?limit=$limit&start=$prefix" . ($limit - 1);
        $expected_objects = [];
        for ($i = 0; $i < $limit; $i++) {
            $id = $prefix . $i;
            $expected_objects[$id] = (object)compact('id');
        }

        $this->container->expects($this->at(0))
            ->method('get')
            ->with(Client::class, [$this->client_options,])
            ->willReturn($this->client);
        $this->container->expects($this->at(1))
            ->method('get')
            ->with(HttpRequest::class, $expected_options)
            ->willReturn($this->http_request);
        $this->container->expects($this->at(2))
            ->method('get')
            ->with(Client::class, [$this->client_options,])
            ->willReturn($this->client);
        $this->container->expects($this->at(3))
            ->method('get')
            ->with(HttpRequest::class, $expected_options_2)
            ->willReturn($this->http_request);

        $message = $this->getMock(Response::class);
        $body = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();

        $body->expects($this->at(0))
            ->method('getContents')
            ->willReturn(json_encode($expected_objects));
        $body->expects($this->at(1))
            ->method('getContents')
            ->willReturn(json_encode($expected_objects));
        $message->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn($body);
        $message->expects($this->exactly(2))
            ->method('getHeaders')
            ->willReturn($this->response_headers);
        $message->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(200);
        $this->client->expects($this->exactly(2))
            ->method('send')
            ->with($this->http_request)
            ->willReturn($message);

        $actual = $this->request->pagedRequest($uri, $request_options);
        $this->assertEquals(['data' => $expected_objects,], $actual);
    }

    /**
     * @param array $request_options
     * @param string $url
     * @param array $options
     * @return array
     */
    private function makeRequest(array $request_options, $url, array $options = [])
    {
        $this->container->expects($this->at(0))
            ->method('get')
            ->with(Client::class, [$this->client_options,])
            ->willReturn($this->client);
        $this->container->expects($this->at(1))
            ->method('get')
            ->with(HttpRequest::class, $request_options)
            ->willReturn($this->http_request);

        $message = $this->getMock(Response::class);
        $body = $this->getMockBuilder(Stream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $body->method('getContents')->willReturn(json_encode($this->response_data));
        $message->expects($this->once())
            ->method('getBody')
            ->willReturn($body);
        $message->expects($this->once())
            ->method('getHeaders')
            ->willReturn($this->response_headers);
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
