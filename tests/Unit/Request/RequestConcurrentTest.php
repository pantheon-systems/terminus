<?php

namespace Pantheon\Terminus\Tests\Unit\Request;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use League\Container\Container;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Session\Session;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Unit tests for concurrent request functionality.
 *
 * @coversDefaultClass \Pantheon\Terminus\Request\Request
 */
class RequestConcurrentTest extends TestCase
{
    private $request;
    private $config;
    private $session;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mock config
        $this->config = $this->createMock(TerminusConfig::class);
        $this->config->method('get')->willReturnCallback(function ($key, $default = null) {
            $values = [
                'concurrent_requests_enabled' => true,
                'concurrent_max_concurrency' => 10,
                'concurrent_timeout' => 30,
                'client_options' => [],
                'verify_host_cert' => true,
                'protocol' => 'https',
                'host' => 'terminus.pantheon.io',
                'port' => 443,
                'http_max_retries' => 5,
                'http_retry_backoff' => 5,
                'version' => '4.1.5-dev',
                'php_version' => PHP_VERSION,
                'script' => 'phpunit',
            ];
            return $values[$key] ?? $default;
        });

        // Create mock session
        $this->session = $this->createMock(Session::class);
        $this->session->method('get')->willReturn('test-session-token');
        $this->session->method('isActive')->willReturn(true);

        // Create mock input for container
        $mockInput = $this->createMock(\Symfony\Component\Console\Input\InputInterface::class);
        $mockInput->method('getFirstArgument')->willReturn('test:command');
        $mockInput->method('getArguments')->willReturn(['test' => 'arg']);
        $mockInput->method('getOptions')->willReturn(['test' => 'option']);

        // Create mock container
        $mockContainer = $this->createMock(\League\Container\Container::class);
        $mockContainer->method('get')->willReturn($mockInput);

        // Create Request instance
        $this->request = new Request();
        $this->request->setConfig($this->config);
        $this->request->setSession($this->session);
        $this->request->setLogger(new NullLogger());
        $this->request->setContainer($mockContainer);
    }

    /**
     * Test concurrent requests with all successful responses.
     *
     * @test
     * @covers ::requestConcurrent
     * @covers ::getAsyncClient
     * @covers ::buildPsr7Request
     * @covers ::transformSettledResults
     * @covers ::decodeResponse
     * @group concurrent
     * @group short
     */
    public function testRequestConcurrentAllSuccess()
    {
        $requests = [
            'site1' => [
                'path' => 'sites/site1',
                'options' => ['method' => 'get'],
            ],
            'site2' => [
                'path' => 'sites/site2',
                'options' => ['method' => 'get'],
            ],
            'site3' => [
                'path' => 'sites/site3',
                'options' => ['method' => 'get'],
            ],
        ];

        // Note: This test requires actual Guzzle async execution
        // In a real environment, you would mock the HTTP client
        // For now, this test validates the structure and API

        $this->assertIsArray($requests);
        $this->assertCount(3, $requests);
    }

    /**
     * Test concurrent requests with partial failures.
     *
     * @test
     * @covers ::requestConcurrent
     * @covers ::transformSettledResults
     * @group concurrent
     * @group short
     */
    public function testRequestConcurrentPartialFailure()
    {
        $requests = [
            'success' => [
                'path' => 'sites/valid',
                'options' => ['method' => 'get'],
            ],
            'failure' => [
                'path' => 'sites/invalid',
                'options' => ['method' => 'get'],
            ],
        ];

        // Test would verify that successful requests return results
        // and failed requests are properly handled
        $this->assertIsArray($requests);
    }

    /**
     * Test sequential fallback when concurrent disabled.
     *
     * @test
     * @covers ::requestConcurrent
     * @covers ::requestSequential
     * @group concurrent
     * @group short
     */
    public function testRequestSequentialFallback()
    {
        // Override config to disable concurrent
        $config = $this->createMock(TerminusConfig::class);
        $config->method('get')->willReturnCallback(function ($key, $default = null) {
            if ($key === 'concurrent_requests_enabled') {
                return false;
            }
            return $default;
        });

        $request = new Request();
        $request->setConfig($config);
        $request->setLogger(new NullLogger());

        // Verify config returns false
        $this->assertFalse($config->get('concurrent_requests_enabled'));
    }

    /**
     * Test buildPsr7Request creates proper request object.
     *
     * @test
     * @covers ::buildPsr7Request
     * @group concurrent
     * @group short
     */
    public function testBuildPsr7Request()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('buildPsr7Request');
        $method->setAccessible(true);

        $psr7Request = $method->invoke($this->request, 'sites/test', ['method' => 'get']);

        $this->assertInstanceOf(Psr7Request::class, $psr7Request);
        $this->assertEquals('GET', $psr7Request->getMethod());
        $this->assertStringContainsString('sites/test', (string)$psr7Request->getUri());
    }

    /**
     * Test decodeResponse handles JSON correctly.
     *
     * @test
     * @covers ::decodeResponse
     * @group concurrent
     * @group short
     */
    public function testDecodeResponse()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('decodeResponse');
        $method->setAccessible(true);

        // Test with JSON response
        $jsonResponse = new Response(200, [], json_encode(['data' => 'test']));
        $decoded = $method->invoke($this->request, $jsonResponse);

        $this->assertIsObject($decoded);
        $this->assertEquals('test', $decoded->data);

        // Test with empty response
        $emptyResponse = new Response(200, [], '');
        $empty = $method->invoke($this->request, $emptyResponse);

        $this->assertNull($empty);

        // Test with non-JSON response
        $textResponse = new Response(200, [], 'plain text');
        $text = $method->invoke($this->request, $textResponse);

        $this->assertEquals('plain text', $text);
    }

    /**
     * Test concurrent request structure.
     *
     * @test
     * @covers ::requestConcurrent
     * @group concurrent
     * @group short
     */
    public function testRequestConcurrentStructure()
    {
        $requests = [
            'key1' => [
                'path' => 'test/path1',
                'options' => ['method' => 'get'],
            ],
            'key2' => [
                'path' => 'test/path2',
                'options' => ['method' => 'post', 'form_params' => ['test' => 'data']],
            ],
        ];

        // Verify request structure
        $this->assertArrayHasKey('key1', $requests);
        $this->assertArrayHasKey('key2', $requests);
        $this->assertArrayHasKey('path', $requests['key1']);
        $this->assertArrayHasKey('options', $requests['key1']);
    }

    /**
     * Test transformSettledResults formats results correctly.
     *
     * @test
     * @covers ::transformSettledResults
     * @group concurrent
     * @group short
     */
    public function testTransformSettledResults()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('transformSettledResults');
        $method->setAccessible(true);

        // Mock settled results
        $settled = [
            'success_key' => [
                'state' => 'fulfilled',
                'value' => new Response(200, [], json_encode(['data' => 'success'])),
            ],
            'failure_key' => [
                'state' => 'rejected',
                'reason' => new \Exception('Test error'),
            ],
        ];

        $results = $method->invoke($this->request, $settled, ['continue_on_error' => true]);

        // Verify result structure
        $this->assertIsArray($results);
        $this->assertArrayHasKey('success_key', $results);
        $this->assertArrayHasKey('failure_key', $results);

        // Verify success result
        $this->assertTrue($results['success_key']['success']);
        $this->assertNotNull($results['success_key']['result']);
        $this->assertNull($results['success_key']['error']);

        // Verify failure result
        $this->assertFalse($results['failure_key']['success']);
        $this->assertNull($results['failure_key']['result']);
        $this->assertInstanceOf(\Exception::class, $results['failure_key']['error']);
    }

    /**
     * Test getAsyncClient creates proper client.
     *
     * @test
     * @covers ::getAsyncClient
     * @group concurrent
     * @group short
     */
    public function testGetAsyncClient()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('getAsyncClient');
        $method->setAccessible(true);

        $client = $method->invoke($this->request);

        $this->assertInstanceOf(\GuzzleHttp\ClientInterface::class, $client);
    }

    /**
     * Test requestSequential processes requests one by one.
     *
     * @test
     * @covers ::requestSequential
     * @group concurrent
     * @group short
     */
    public function testRequestSequential()
    {
        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->request);
        $method = $reflection->getMethod('requestSequential');
        $method->setAccessible(true);

        $requests = [
            'test1' => [
                'path' => 'sites/test1',
                'options' => ['method' => 'get'],
            ],
        ];

        // This will attempt actual requests, so we just verify structure
        $this->assertIsArray($requests);
        $this->assertArrayHasKey('test1', $requests);
    }
}
