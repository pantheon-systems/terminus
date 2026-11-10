<?php

namespace Pantheon\Terminus\Tests\Unit\PublicApi;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use Pantheon\Terminus\PublicApi\Client;
use Pantheon\Terminus\PublicApi\PublicApiException;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class ClientTest extends TestCase
{
    public function testSendsBearerTokenAndTerminusHeaders(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(200, ['id' => 'abc'])]);

        $result = $h->client->get('/current-user');

        $this->assertSame(['id' => 'abc'], $result);
        $request = $h->history[0]['request'];
        $this->assertSame('https://api.example.test/v1/current-user', (string) $request->getUri());
        $this->assertSame('Bearer ' . ClientTestHelper::TOKEN, $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
        $this->assertSame('Terminus/test', $request->getHeaderLine('User-Agent'));
        $this->assertSame('trace-1', $request->getHeaderLine('X-Pantheon-Trace-Id'));
    }

    public function testTokenProviderIsCalledOnceAndLazily(): void
    {
        $calls = 0;
        $client = new Client(
            new \GuzzleHttp\Client(['handler' => new \GuzzleHttp\Handler\MockHandler([
                new Response(200, [], '{}'),
                new Response(200, [], '{}'),
            ])]),
            'https://api.example.test/v1',
            function () use (&$calls) {
                $calls++;
                return 'tok';
            },
            fn () => [],
            new \Psr\Log\NullLogger()
        );
        $this->assertSame(0, $calls);
        $client->get('/a');
        $client->get('/b');
        $this->assertSame(1, $calls);
    }

    public function testMissingTokenThrows(): void
    {
        $client = new Client(
            new \GuzzleHttp\Client(),
            'https://api.example.test/v1',
            fn () => null,
            fn () => [],
            new \Psr\Log\NullLogger()
        );
        $this->expectException(PublicApiException::class);
        $this->expectExceptionMessage('You are not logged in');
        $client->get('/current-user');
    }

    public function testPostSendsJsonBody(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(200, ['id' => 'wf'])]);

        $h->client->post('/sites/s1/backups', ['environment' => 'dev', 'ttl' => 86400]);

        $request = $h->history[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(['environment' => 'dev', 'ttl' => 86400], json_decode((string) $request->getBody(), true));
    }

    public function testQueryParametersAreEncoded(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(200, [])]);

        $h->client->get('/sites/s1/workflows', ['first' => 2, 'after' => 'a b+c']);

        $this->assertSame(['GET /v1/sites/s1/workflows?first=2&after=a%20b%2Bc'], $h->sent());
    }

    public function testPathEncodesSegments(): void
    {
        $this->assertSame('/sites/by-name/a%2Fb%3Fc', Client::path('/sites/by-name/%s', 'a/b?c'));
    }

    public function testJsonStringBodyIsDecoded(): void
    {
        // GET /sites/by-name/{name} returns a bare JSON string.
        $h = new ClientTestHelper([new Response(200, [], '"7d6c59e4-304a-41e9-97ab-f348eedc3996"')]);
        $this->assertSame('7d6c59e4-304a-41e9-97ab-f348eedc3996', $h->client->get('/sites/by-name/x'));
    }

    public function testErrorBodyIsMappedToException(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(403, [
            'errors' => [[
                'message' => 'You do not have access to this resource, or it does not exist.',
                'extensions' => ['code' => 'UNAUTHORIZED'],
            ]],
            'traceId' => 'abc123',
        ])]);

        try {
            $h->client->get('/sites/by-name/nope');
            $this->fail('Expected PublicApiException');
        } catch (PublicApiException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('UNAUTHORIZED', $e->getErrorCode());
            $this->assertSame('abc123', $e->getTraceId());
            $this->assertStringContainsString(
                'GET /sites/by-name/nope failed with HTTP 403 (UNAUTHORIZED)',
                $e->getMessage()
            );
            $this->assertStringContainsString('You do not have access', $e->getMessage());
            $this->assertStringContainsString('Trace ID: abc123', $e->getMessage());
        }
        $this->assertCount(1, $h->history, '403 must not be retried');
    }

    public function testBracesInApiMessagesAreNotInterpolated(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(400, ['message' => 'bad {field}'])]);
        $this->expectExceptionMessage('bad {field}');
        $h->client->get('/x');
    }

    public function test401IsNotRetriedAndExplainsTokenType(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(401, ['message' => 'Unauthenticated'])]);
        try {
            $h->client->get('/current-user');
            $this->fail('Expected PublicApiException');
        } catch (PublicApiException $e) {
            $this->assertSame(401, $e->getStatusCode());
            $this->assertStringContainsString('personal access token', $e->getMessage());
        }
        $this->assertCount(1, $h->history);
    }

    public function test409IsAPlainPublicApiException(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(409, ['message' => 'conflict'])]);
        try {
            $h->client->post('/x');
            $this->fail('Expected PublicApiException');
        } catch (\Throwable $e) {
            $this->assertSame(PublicApiException::class, get_class($e));
            $this->assertSame(409, $e->getStatusCode());
        }
    }

    public function testGetRetriesOn5xxAnd429ThenSucceeds(): void
    {
        $h = new ClientTestHelper([
            new Response(503),
            new Response(429),
            ClientTestHelper::json(200, ['ok' => true]),
        ]);
        $this->assertSame(['ok' => true], $h->client->get('/x'));
        $this->assertCount(3, $h->history);
    }

    public function testRetriesOnConnectException(): void
    {
        $h = new ClientTestHelper([
            new ConnectException('Connection refused', new Psr7Request('GET', '/x')),
            ClientTestHelper::json(200, ['ok' => true]),
        ]);
        $this->assertSame(['ok' => true], $h->client->get('/x'));
        $this->assertCount(2, $h->history);
    }

    public function testGivesUpAfterMaxRetriesWithStatus(): void
    {
        $h = new ClientTestHelper(array_fill(0, 4, new Response(502)), ['http_max_retries' => 3]);
        try {
            $h->client->get('/x');
            $this->fail('Expected PublicApiException');
        } catch (PublicApiException $e) {
            $this->assertSame(502, $e->getStatusCode());
        }
        $this->assertCount(4, $h->history, 'one attempt plus three retries');
    }

    public function testConnectFailureAfterRetriesIsPublicApiException(): void
    {
        $h = new ClientTestHelper(
            array_fill(0, 2, new ConnectException('Connection refused', new Psr7Request('GET', '/x'))),
            ['http_max_retries' => 1]
        );
        $this->expectException(PublicApiException::class);
        $this->expectExceptionMessage('Connection refused');
        $h->client->get('/x');
    }

    public function testPostIsNotRetriedOn5xxButIsOn429(): void
    {
        $h = new ClientTestHelper([new Response(500)]);
        try {
            $h->client->post('/x');
            $this->fail('Expected PublicApiException');
        } catch (PublicApiException $e) {
            $this->assertSame(500, $e->getStatusCode());
        }
        $this->assertCount(1, $h->history);

        $h = new ClientTestHelper([new Response(429), ClientTestHelper::json(200, ['id' => 'wf'])]);
        $this->assertSame(['id' => 'wf'], $h->client->post('/x'));
        $this->assertCount(2, $h->history);
    }

    public function testDebugLogRedactsAuthorizationAndSecrets(): void
    {
        $logger = new class extends AbstractLogger {
            public array $lines = [];

            public function log($level, $message, array $context = []): void
            {
                $replace = [];
                foreach ($context as $k => $v) {
                    $replace['{' . $k . '}'] = is_scalar($v) ? (string) $v : json_encode($v);
                }
                $this->lines[] = strtr((string) $message, $replace);
            }
        };
        $h = new ClientTestHelper(
            [ClientTestHelper::json(200, ['edges' => [['node' => [
                'id' => 'dev',
                'password' => 'lock-pass',
                'envVariables' => ['databasePassword' => 'db-pass'],
            ]]]])],
            [],
            $logger
        );

        $h->client->post('/sites/s/environments/dev/lock', ['username' => 'u', 'password' => 'lock-pass']);

        $all = implode("\n", $logger->lines);
        $this->assertStringNotContainsString(ClientTestHelper::TOKEN, $all);
        $this->assertStringNotContainsString('lock-pass', $all);
        $this->assertStringNotContainsString('db-pass', $all);
        $this->assertStringContainsString(Client::REDACTED, $all);
        $this->assertStringContainsString('"id":"dev"', $all);
    }
}
