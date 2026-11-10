<?php

namespace Pantheon\Terminus\Tests\Unit\PublicApi;

use Consolidation\Config\Config;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Pantheon\Terminus\PublicApi\Client;
use Pantheon\Terminus\PublicApi\ClientFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Builds a real Public API Client over a Guzzle MockHandler and records every request sent.
 */
class ClientTestHelper
{
    public const TOKEN = 'pat-secret-value';

    public MockHandler $mock;

    /** @var array<int, array{request: \Psr\Http\Message\RequestInterface}> */
    public array $history = [];

    public Client $client;

    /**
     * @param array $responses Responses/exceptions for the MockHandler, in order.
     */
    public function __construct(array $responses, array $config = [], ?LoggerInterface $logger = null)
    {
        $this->mock = new MockHandler($responses);
        $stack = HandlerStack::create($this->mock);
        // Record what actually reaches the wire (below the retry middleware ClientFactory adds).
        $stack->push(Middleware::history($this->history));
        $this->client = ClientFactory::create(
            new Config($config + ['public_api_url' => 'https://api.example.test/v1', 'http_max_retries' => 3]),
            fn () => self::TOKEN,
            fn () => ['User-Agent' => 'Terminus/test', 'X-Pantheon-Trace-Id' => 'trace-1'],
            $logger ?? new NullLogger(),
            $stack,
            0
        );
    }

    public static function json(int $status, $body): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    /**
     * @return string[] "METHOD path?query" for each request sent.
     */
    public function sent(): array
    {
        return array_map(function ($entry) {
            $uri = $entry['request']->getUri();
            $query = $uri->getQuery();
            return $entry['request']->getMethod() . ' ' . $uri->getPath() . ($query !== '' ? '?' . $query : '');
        }, $this->history);
    }
}
