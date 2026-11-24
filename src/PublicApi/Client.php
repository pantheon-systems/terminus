<?php

namespace Pantheon\Terminus\PublicApi;

use Closure;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * HTTP client for the Pantheon Public API v1 (https://api.pantheon.io/v1).
 *
 * Deliberately separate from Request (the pantheonapi client): it authenticates with the saved
 * token (a personal access token) rather than the session, never refreshes the session on 401,
 * and never maps 409 to TerminusUnsupportedSiteException. Every non-2xx response becomes a
 * PublicApiException. Retries are configured on the Guzzle handler stack by ClientFactory.
 *
 * Responses are decoded as associative arrays.
 */
class Client
{
    public const REDACTED = '**HIDDEN**';

    /**
     * Header names (lowercase) and body keys (case-insensitive pattern) never written to logs.
     */
    private const SENSITIVE_HEADERS = ['authorization', 'cookie'];
    private const SENSITIVE_KEY_PATTERN = '/password|token|secret|session/i';

    private ClientInterface $http;

    private string $baseUrl;

    private Closure $tokenProvider;

    private Closure $headersProvider;

    private LoggerInterface $logger;

    private ?string $token = null;

    /**
     * @param ClientInterface $http Guzzle client (ClientFactory adds retries).
     * @param string $baseUrl e.g. https://api.pantheon.io/v1 (no trailing slash needed).
     * @param Closure $tokenProvider fn(): string — returns the bearer token. Called once, lazily.
     * @param Closure $headersProvider fn(): array — the shared Terminus headers
     *   (User-Agent, X-Pantheon-Trace-Id, X-Pantheon-Terminus-Command, ...).
     * @param LoggerInterface $logger
     */
    public function __construct(
        ClientInterface $http,
        string $baseUrl,
        Closure $tokenProvider,
        Closure $headersProvider,
        LoggerInterface $logger
    ) {
        $this->http = $http;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->tokenProvider = $tokenProvider;
        $this->headersProvider = $headersProvider;
        $this->logger = $logger;
    }

    /**
     * Builds a path from a sprintf-style template, URL-encoding each argument as one path segment.
     *
     * Example: Client::path('/sites/%s/environments/%s/lock', $siteId, $env)
     */
    public static function path(string $template, string ...$segments): string
    {
        return vsprintf($template, array_map('rawurlencode', $segments));
    }

    /**
     * @return mixed Decoded JSON (array, string, ...) or null for an empty body.
     * @throws PublicApiException
     */
    public function get(string $path, array $query = [])
    {
        return $this->request('GET', $path, $query);
    }

    /**
     * @return mixed
     * @throws PublicApiException
     */
    public function post(string $path, array $body = [])
    {
        return $this->request('POST', $path, [], $body);
    }

    /**
     * @return mixed
     * @throws PublicApiException
     */
    public function delete(string $path, array $query = [])
    {
        return $this->request('DELETE', $path, $query);
    }

    /**
     * Sends a request and returns the decoded response body.
     *
     * @param string $method HTTP method.
     * @param string $path Path relative to the base URL, starting with '/'. Use Client::path() for IDs.
     * @param array $query Query parameters.
     * @param array|null $body JSON body, or null for none.
     *
     * @return mixed
     * @throws PublicApiException
     */
    public function request(string $method, string $path, array $query = [], ?array $body = null)
    {
        $method = strtoupper($method);
        $uri = $this->baseUrl . $path;
        if (!empty($query)) {
            $uri .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $headers = ($this->headersProvider)();
        $headers['Accept'] = 'application/json';
        $headers['Authorization'] = 'Bearer ' . $this->getToken();
        $encodedBody = null;
        if ($body !== null) {
            $encodedBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $headers['Content-Type'] = 'application/json';
        }

        $this->logger->debug(
            "#### PUBLIC API REQUEST ####\n{method} {uri}\nHeaders: {headers}\nBody: {body}",
            [
                'method' => $method,
                'uri' => $uri,
                'headers' => json_encode(self::redactHeaders($headers), JSON_UNESCAPED_SLASHES),
                'body' => $body === null ? '' : json_encode(self::redact($body), JSON_UNESCAPED_SLASHES),
            ]
        );

        try {
            $response = $this->http->send(
                new Psr7Request($method, $uri, $headers, $encodedBody),
                [RequestOptions::HTTP_ERRORS => false]
            );
        } catch (GuzzleException $e) {
            throw new PublicApiException(sprintf(
                'Public API request %s %s failed: %s',
                $method,
                $path,
                $e->getMessage()
            ));
        }

        $raw = (string) $response->getBody();
        $decoded = self::decode($raw);

        $this->logger->debug(
            "#### PUBLIC API RESPONSE ####\nStatus: {status}\nData: {data}",
            [
                'status' => $response->getStatusCode(),
                'data' => is_array($decoded)
                    ? json_encode(self::redact($decoded), JSON_UNESCAPED_SLASHES)
                    : $raw,
            ]
        );

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            throw $this->buildException($method, $path, $response, $decoded, $raw);
        }

        return $decoded;
    }

    /**
     * @throws PublicApiException When there is no token to send.
     */
    private function getToken(): string
    {
        if ($this->token === null) {
            $token = ($this->tokenProvider)();
            if (!is_string($token) || $token === '') {
                throw new PublicApiException(
                    'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
                );
            }
            $this->token = $token;
        }
        return $this->token;
    }

    /**
     * @return mixed
     */
    private static function decode(string $raw)
    {
        if ($raw === '') {
            return null;
        }
        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $raw;
        }
    }

    /**
     * Maps an error response to a PublicApiException.
     *
     * v1 error bodies look like:
     *   {"errors":[{"message":"...","extensions":{"code":"UNAUTHORIZED"}}],"traceId":"..."}
     * Some routes (e.g. the gateway's own 401) return {"message":"..."} or plain text instead.
     *
     * @param mixed $decoded
     */
    private function buildException(
        string $method,
        string $path,
        ResponseInterface $response,
        $decoded,
        string $raw
    ): PublicApiException {
        $status = $response->getStatusCode();
        $apiMessage = null;
        $errorCode = null;
        $traceId = null;
        if (is_array($decoded)) {
            $first = $decoded['errors'][0] ?? null;
            if (is_array($first)) {
                $apiMessage = $first['message'] ?? null;
                $errorCode = $first['extensions']['code'] ?? null;
            }
            $apiMessage = $apiMessage ?? ($decoded['message'] ?? null);
            $traceId = $decoded['traceId'] ?? null;
        } elseif (is_string($decoded) && $decoded !== '') {
            $apiMessage = mb_substr($decoded, 0, 500);
        }
        $apiMessage = $apiMessage ?: ($response->getReasonPhrase() ?: 'no error message');

        $message = sprintf(
            'Public API request %s %s failed with HTTP %d%s: %s',
            $method,
            $path,
            $status,
            $errorCode ? " ($errorCode)" : '',
            $apiMessage
        );
        if ($status === 401) {
            $message .= "\nThe Public API requires a personal access token. Log in with one using"
                . ' `terminus auth:login --machine-token=<token>`.';
        }
        if ($traceId) {
            $message .= sprintf("\nTrace ID: %s", $traceId);
        }

        return new PublicApiException($message, $status, $errorCode, $traceId);
    }

    /**
     * Replaces sensitive values in a decoded body, recursively, for logging.
     *
     * v1 responses carry credentials (e.g. environments[].envVariables.databasePassword, the lock
     * password), so response bodies must be redacted, not just request headers.
     */
    public static function redact(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match(self::SENSITIVE_KEY_PATTERN, $key) && $value !== null) {
                $data[$key] = self::REDACTED;
            } elseif (is_array($value)) {
                $data[$key] = self::redact($value);
            }
        }
        return $data;
    }

    private static function redactHeaders(array $headers): array
    {
        foreach ($headers as $name => $value) {
            if (in_array(strtolower($name), self::SENSITIVE_HEADERS, true)) {
                $headers[$name] = self::REDACTED;
            }
        }
        return $headers;
    }
}
