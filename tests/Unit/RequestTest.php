<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusUnsupportedSiteException;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Session\Session;
use Consolidation\Config\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;
use League\Container\Container;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\InputInterface;

class RequestTest extends TestCase
{
    /**
     * @param Session|null $session Defaults to a session whose getAuthToken() throws
     *   (i.e. "no saved token to refresh with"), so 401 tests that don't care about
     *   refresh behavior get a harmless, single-call-only default.
     */
    private function createRequest(?Session $session = null): FastRequest
    {
        $request = new FastRequest();
        $request->setConfig(new Config([
            'protocol' => 'https',
            'host' => 'example.com',
            'port' => '443',
            'client_options' => [],
            'http_max_retries' => 5,
            'http_retry_backoff' => 3,
        ]));
        $request->setLogger(new NullLogger());

        $container = new Container();
        $input = $this->createMock(InputInterface::class);
        $input->method('getFirstArgument')->willReturn(null);
        $input->method('getArguments')->willReturn([]);
        $input->method('getOptions')->willReturn([]);
        $container->add('input', $input);
        $request->setContainer($container);

        $request->setSession($session ?? $this->createSessionMockWithNoAuthToken(['fake-session-token']));

        return $request;
    }

    /**
     * Builds a Session mock whose get('session') returns each value in
     * $sessionTokenSequence in order across successive calls, and whose
     * getAuthToken() returns the given SavedToken (e.g. a mock whose logIn()
     * either succeeds or throws).
     */
    private function createSessionMockWithAuthToken(array $sessionTokenSequence, SavedToken $authToken): Session
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnOnConsecutiveCalls(...$sessionTokenSequence);
        $session->method('getAuthToken')->willReturn($authToken);
        return $session;
    }

    /**
     * Builds a Session mock whose get('session') returns each value in
     * $sessionTokenSequence in order across successive calls, and whose
     * getAuthToken() throws (i.e. no saved token available to refresh with).
     */
    private function createSessionMockWithNoAuthToken(array $sessionTokenSequence): Session
    {
        $session = $this->createMock(Session::class);
        $session->method('get')->willReturnOnConsecutiveCalls(...$sessionTokenSequence);
        $session->method('getAuthToken')->willThrowException(
            new TerminusException('You are not logged in. Run `auth:login` to authenticate.')
        );
        return $session;
    }

    /**
     * Wires up the given queue of responses/exceptions behind the same retry
     * decider that production code uses, and attaches it to $request. If
     * $history is given, every request sent through the client is recorded there.
     */
    private function attachMockHandler(FastRequest $request, array $queue, ?array &$history = null): MockHandler
    {
        $mockHandler = new MockHandler($queue);
        $stack = HandlerStack::create($mockHandler);

        if ($history !== null) {
            $stack->push(Middleware::history($history));
        }

        $method = new \ReflectionMethod(Request::class, 'createRetryDecider');
        $method->setAccessible(true);
        $decider = $method->invoke($request);
        $stack->push(Middleware::retry($decider));

        $client = new Client(['handler' => $stack, 'http_errors' => false]);

        $clientProperty = new \ReflectionProperty(Request::class, 'client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($request, $client);

        return $mockHandler;
    }

    public function testSuccessfulResponseIsNotRetried()
    {
        $request = $this->createRequest();
        $mockHandler = $this->attachMockHandler($request, [new Response(200, [], '{}')]);

        $result = $request->request('sites');

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
    }

    public function testNotFoundIsNotRetried()
    {
        $request = $this->createRequest();
        $mockHandler = $this->attachMockHandler($request, [new Response(404, [], '{}')]);

        $result = $request->request('sites/does-not-exist');

        $this->assertSame(404, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
    }

    public function testForbiddenIsNotRetried()
    {
        $request = $this->createRequest();
        $mockHandler = $this->attachMockHandler($request, [new Response(403, [], '{}')]);

        $result = $request->request('sites');

        $this->assertSame(403, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
    }

    public function testUnauthorizedWithNoTokenToRefreshWithIsNotRetried()
    {
        // Default createRequest() session has no saved token: getAuthToken() throws.
        $request = $this->createRequest();
        $mockHandler = $this->attachMockHandler($request, [new Response(401, [], '{}')]);

        $result = $request->request('sites');

        $this->assertSame(401, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
    }

    public function testUnauthorizedWithFailedRefreshIsNotRetried()
    {
        $savedToken = $this->createMock(SavedToken::class);
        $savedToken->method('logIn')->willThrowException(
            new TerminusException('Could not log in with the given machine token.')
        );
        $session = $this->createSessionMockWithAuthToken(['fake-session-token'], $savedToken);

        $request = $this->createRequest($session);
        $mockHandler = $this->attachMockHandler($request, [new Response(401, [], '{}')]);

        $result = $request->request('sites');

        $this->assertSame(401, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
    }

    public function testUnauthorizedRefreshesSessionAndRetriesOnce()
    {
        $savedToken = $this->createMock(SavedToken::class);
        $savedToken->method('logIn')->willReturn(null);
        $session = $this->createSessionMockWithAuthToken(
            ['token-before-refresh', 'token-after-refresh'],
            $savedToken
        );

        $request = $this->createRequest($session);
        $history = [];
        $mockHandler = $this->attachMockHandler($request, [
            new Response(401, [], '{}'),
            new Response(200, [], '{}'),
        ], $history);

        $result = $request->request('sites');

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
        $this->assertCount(2, $history);
        $this->assertNotSame(
            $history[0]['request']->getHeaderLine('Authorization'),
            $history[1]['request']->getHeaderLine('Authorization')
        );
    }

    public function testUnauthorizedRefreshSucceedsButRetryStillFailsIsNotRetriedAgain()
    {
        $savedToken = $this->createMock(SavedToken::class);
        $savedToken->method('logIn')->willReturn(null);
        $session = $this->createSessionMockWithAuthToken(
            ['token-before-refresh', 'token-after-refresh'],
            $savedToken
        );

        $request = $this->createRequest($session);
        $mockHandler = $this->attachMockHandler($request, [
            new Response(401, [], '{}'),
            new Response(401, [], '{}'),
        ]);

        $result = $request->request('sites');

        $this->assertSame(401, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
    }

    public function testConflictThrowsUnsupportedSiteException()
    {
        $request = $this->createRequest();
        $this->attachMockHandler($request, [
            new Response(409, [], json_encode(['message' => 'This is not supported for this site.'])),
        ]);

        $this->expectException(TerminusUnsupportedSiteException::class);
        $request->request('sites/unsupported-action');
    }

    public function testConnectExceptionIsRetriedOnce()
    {
        $request = $this->createRequest();
        $psrRequest = new Psr7Request('GET', 'https://example.com/api/sites');
        $mockHandler = $this->attachMockHandler($request, [
            new ConnectException('Connection refused', $psrRequest),
            new Response(200, [], '{}'),
        ]);

        $result = $request->request('sites');

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
        $this->assertCount(1, $request->sleptSeconds);
    }

    public function testRetryableStatusCodeRetriesUntilSuccess()
    {
        $request = $this->createRequest();
        $mockHandler = $this->attachMockHandler($request, [
            new Response(503, [], '{}'),
            new Response(503, [], '{}'),
            new Response(200, [], '{}'),
        ]);

        $result = $request->request('sites');

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
        // Linear backoff: base(3) * (retry + 1).
        $this->assertSame([3, 6], $request->sleptSeconds);
    }

    public function testRateLimitedStatusCodeIsRetried()
    {
        $request = $this->createRequest();
        $mockHandler = $this->attachMockHandler($request, [
            new Response(429, [], '{}'),
            new Response(429, [], '{}'),
            new Response(200, [], '{}'),
        ]);

        $result = $request->request('sites');

        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame(0, $mockHandler->count());
        // Exponential backoff: base(3) * 2^(retry + 1).
        $this->assertSame([6, 12], $request->sleptSeconds);
    }
}
