<?php

namespace Pantheon\Terminus\Tests\Unit\Request;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use League\Container\Container;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Session\Session;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Robo\Config\Config;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Tests for the X-Pantheon-Notices header processing in Request.
 */
class RequestApiNoticesTest extends TestCase
{
    private Request $request;
    private LoggerInterface $logger;
    private BufferedOutput $output;
    private ClientInterface $client;
    private \ReflectionProperty $clientProperty;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset the static displayed notices between tests.
        $ref = new \ReflectionClass(Request::class);
        $prop = $ref->getProperty('displayedNotices');
        $prop->setValue(null, []);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->output = new BufferedOutput();
        $this->client = $this->createMock(ClientInterface::class);

        $this->request = new Request();
        $this->request->setLogger($this->logger);

        // Inject output via the IO trait.
        $input = new ArrayInput([]);
        $this->request->setInput($input);
        $this->request->setOutput($this->output);

        // Set up config.
        $config = new Config();
        $config->set('protocol', 'https');
        $config->set('host', 'localhost');
        $config->set('port', '443');
        $config->set('client_options', []);
        $config->set('version', '4.0.0');
        $config->set('php_version', '8.1');
        $config->set('script', 'terminus');
        $this->request->setConfig($config);

        // Set up a mock session.
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturn('fake-session-token');
        $this->request->setSession($session);

        // Set up container with input.
        $container = new Container();
        $container->add('input', $input);
        $this->request->setContainer($container);

        // Inject the mock client via reflection.
        $this->clientProperty = new \ReflectionProperty(Request::class, 'client');
        $this->clientProperty->setValue($this->request, $this->client);
    }

    public function testNoNoticesHeader(): void
    {
        $response = new Response(200, [], json_encode(['id' => '123']));
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->never())->method('notice');
        $this->logger->expects($this->never())->method('warning');

        $this->request->request('https://example.com/api/test');
    }

    public function testInfoLevelNotice(): void
    {
        $notices = [['message' => 'This is an informational notice.', 'level' => 'info']];
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => json_encode($notices)],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with('This is an informational notice.');

        $this->request->request('https://example.com/api/test');
    }

    public function testWarningLevelNotice(): void
    {
        $notices = [['message' => 'This is a warning.', 'level' => 'warning']];
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => json_encode($notices)],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('This is a warning.');

        $this->request->request('https://example.com/api/test');
    }

    public function testAlertLevelNotice(): void
    {
        $notices = [['message' => 'Critical alert message!', 'level' => 'alert']];
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => json_encode($notices)],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->request->request('https://example.com/api/test');

        $output = $this->output->fetch();
        $this->assertStringContainsString('Critical alert message!', $output);
    }

    public function testDefaultLevelIsInfo(): void
    {
        $notices = [['message' => 'No level specified.']];
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => json_encode($notices)],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with('No level specified.');

        $this->request->request('https://example.com/api/test');
    }

    public function testDuplicateNoticesDisplayedOnce(): void
    {
        $notices = [['message' => 'Duplicate notice.', 'level' => 'info']];
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => json_encode($notices)],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        // The notice logger should be called exactly once across two requests.
        $this->logger->expects($this->once())
            ->method('notice')
            ->with('Duplicate notice.');

        $this->request->request('https://example.com/api/test');
        $this->request->request('https://example.com/api/test');
    }

    public function testMultipleDistinctNotices(): void
    {
        $notices = [
            ['message' => 'First notice.', 'level' => 'info'],
            ['message' => 'Second notice.', 'level' => 'warning'],
        ];
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => json_encode($notices)],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with('First notice.');
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Second notice.');

        $this->request->request('https://example.com/api/test');
    }

    public function testMalformedHeaderIgnored(): void
    {
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => 'not valid json{{{'],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->stringContains('Failed to parse'),
                $this->anything()
            );
        $this->logger->expects($this->never())->method('notice');
        $this->logger->expects($this->never())->method('warning');

        $this->request->request('https://example.com/api/test');
    }

    public function testEmptyArrayNoOutput(): void
    {
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => '[]'],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->never())->method('notice');
        $this->logger->expects($this->never())->method('warning');

        $this->request->request('https://example.com/api/test');
    }

    public function testNoticeWithEmptyMessageSkipped(): void
    {
        $notices = [['message' => '', 'level' => 'info'], ['level' => 'warning']];
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => json_encode($notices)],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->never())->method('notice');
        $this->logger->expects($this->never())->method('warning');

        $this->request->request('https://example.com/api/test');
    }

    public function testNonArrayHeaderValueIgnored(): void
    {
        $response = new Response(
            200,
            [Request::NOTICES_HEADER => '"just a string"'],
            json_encode(['id' => '123'])
        );
        $this->client->method('send')->willReturn($response);

        $this->logger->expects($this->never())->method('notice');
        $this->logger->expects($this->never())->method('warning');

        $this->request->request('https://example.com/api/test');
    }
}
