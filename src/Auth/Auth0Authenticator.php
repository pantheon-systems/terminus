<?php

namespace Pantheon\Terminus\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Http\Message\ResponseInterface;

class Auth0Authenticator
{
    private const AUTH0_CONNECTION = 'Pantheon';
    private const TARGET_COOKIES = ['X-Pantheon-Access-Token', 'X-Pantheon-Session'];
    private const MAX_HOPS = 20;
    private const AUTH0_DOMAINS = [
        'dashboard.pantheon.io' => 'pantheon.auth0.com',
    ];
    private const GOOGLE_AUTH_DOMAIN = 'pantheon-prodmirror.us.auth0.com';
    private const GOOGLE_AUTH_SPA_CLIENT_ID = 'eKOPHHW7lv1t7YuCiNk0BOpT2uIQLIBQ';
    private const GOOGLE_AUTH_SRC = 'hermes-admin.sandbox-devx.sbx04.pantheon.io';
    private const USER_AGENT = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) '
        . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    private string $hermesUrl;
    private string $auth0Base;
    private Client $client;
    private CookieJar $cookieJar;

    public function __construct(string $hermesUrl)
    {
        $this->hermesUrl = rtrim($hermesUrl, '/');

        $host = parse_url($this->hermesUrl, PHP_URL_HOST);
        $auth0Domain = self::AUTH0_DOMAINS[$host] ?? null;
        if ($auth0Domain === null) {
            throw new TerminusException(
                'No Auth0 domain mapping for dashboard host {host}.',
                ['host' => $host]
            );
        }
        $this->auth0Base = "https://{$auth0Domain}";

        $this->cookieJar = new CookieJar();
        $this->client = new Client([
            'cookies' => $this->cookieJar,
            'allow_redirects' => false,
            'timeout' => 30,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => self::USER_AGENT,
            ],
        ]);
    }

    /**
     * Authenticate with email and password via Auth0.
     *
     * @return array{access_token: string, session: string}
     *
     * @throws TerminusException
     */
    public function login(string $email, string $password): array
    {
        $authorizeUrl = $this->getAuthorizeUrl();
        if ($authorizeUrl === null) {
            throw new TerminusException('Could not find Auth0 /authorize redirect.');
        }

        parse_str(parse_url($authorizeUrl, PHP_URL_QUERY) ?? '', $authParams);
        $clientId = $authParams['client_id'] ?? null;

        $loginTicket = $this->tryCrossOriginAuth($clientId, $email, $password);

        if ($loginTicket !== null) {
            $ticketUrl = $authorizeUrl
                . '&login_ticket=' . urlencode($loginTicket)
                . '&realm=' . urlencode(self::AUTH0_CONNECTION);
            $response = $this->client->get($ticketUrl);
            $this->followFullChain($response, $ticketUrl);
        } else {
            $response = $this->client->get($authorizeUrl);
            [$loginPage, $loginPageUrl] = $this->followRedirects($response, $authorizeUrl);

            $loginResponse = $this->tryUniversalLogin($loginPage, $loginPageUrl, $email, $password);
            if ($loginResponse === null) {
                throw new TerminusException('All login strategies failed.');
            }
            $this->followFullChain($loginResponse[0], $loginResponse[1]);
        }

        $cookies = $this->collectTargetCookies();
        if (count($cookies) < count(self::TARGET_COOKIES)) {
            $missing = array_diff(self::TARGET_COOKIES, array_keys($cookies));
            throw new TerminusException(
                'Authentication failed. Missing cookies: {missing}',
                ['missing' => implode(', ', $missing)]
            );
        }

        return [
            'access_token' => $cookies['X-Pantheon-Access-Token'],
            'session' => $cookies['X-Pantheon-Session'],
        ];
    }

    /**
     * Build an Auth0 authorize URL for Google OAuth with PKCE.
     *
     * @return array{url: string, code_verifier: string, state: string, client_id: string}
     */
    public function getGoogleAuthUrl(string $callbackUrl): array
    {
        $clientId = self::GOOGLE_AUTH_SPA_CLIENT_ID;
        $auth0Base = 'https://' . self::GOOGLE_AUTH_DOMAIN;

        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');
        $state = http_build_query(['src' => self::GOOGLE_AUTH_SRC, 'nonce' => bin2hex(random_bytes(16))]);

        $authUrl = $auth0Base . '/authorize?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $callbackUrl,
            'response_type' => 'code',
            'connection' => 'google-oauth2',
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

        return [
            'url' => $authUrl,
            'code_verifier' => $codeVerifier,
            'state' => $state,
            'client_id' => $clientId,
            'auth0_base' => $auth0Base,
        ];
    }

    /**
     * Exchange an authorization code for tokens using PKCE.
     *
     * @return array Auth0 token response (access_token, id_token, etc.)
     */
    public function exchangeCodeForTokens(
        string $code,
        string $codeVerifier,
        string $redirectUri,
        string $clientId,
        ?string $auth0Base = null,
    ): array {
        $tokenUrl = ($auth0Base ?? $this->auth0Base) . '/oauth/token';
        $response = $this->client->post($tokenUrl, [
            'json' => [
                'grant_type' => 'authorization_code',
                'client_id' => $clientId,
                'code_verifier' => $codeVerifier,
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            $body = $response->getBody()->getContents();
            throw new TerminusException(
                'Token exchange failed ({status}): {body}',
                ['status' => $response->getStatusCode(), 'body' => $body]
            );
        }

        return json_decode($response->getBody()->getContents(), true);
    }

    private function collectTargetCookies(): array
    {
        $found = [];
        foreach ($this->cookieJar as $cookie) {
            if (in_array($cookie->getName(), self::TARGET_COOKIES, true)) {
                $found[$cookie->getName()] = $cookie->getValue();
            }
        }
        return $found;
    }

    /**
     * Follow Hermes login redirect to capture the Auth0 /authorize URL.
     */
    private function getAuthorizeUrl(): ?string
    {
        $url = "{$this->hermesUrl}/auth/providers/any/login?destination=%2Fworkspace";
        $response = $this->client->get($url);

        for ($i = 0; $i < 10; $i++) {
            $status = $response->getStatusCode();
            if (!in_array($status, [301, 302, 303, 307, 308], true)) {
                break;
            }
            $location = $response->getHeaderLine('Location');
            $nextUrl = $this->resolveUrl($url, $location);
            if (str_contains(parse_url($nextUrl, PHP_URL_PATH) ?? '', '/authorize')) {
                return $nextUrl;
            }
            $url = $nextUrl;
            $response = $this->client->get($url);
        }

        return null;
    }

    /**
     * Strategy 1: Auth0 cross-origin authentication.
     */
    private function tryCrossOriginAuth(
        ?string $clientId,
        string $email,
        string $password,
    ): ?string {
        if ($clientId === null) {
            return null;
        }

        $response = $this->client->post("{$this->auth0Base}/co/authenticate", [
            'json' => [
                'client_id' => $clientId,
                'credential_type' => 'http://auth0.com/oauth/grant-type/password-realm',
                'username' => $email,
                'password' => $password,
                'realm' => self::AUTH0_CONNECTION,
            ],
            'headers' => [
                'Origin' => $this->hermesUrl,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $data = json_decode($response->getBody()->getContents(), true);
        return $data['login_ticket'] ?? null;
    }

    /**
     * Strategy 2: Auth0 New Universal Login form submission.
     *
     * @return array{ResponseInterface, string}|null Response and its URL, or null on failure.
     */
    private function tryUniversalLogin(
        ResponseInterface $loginPage,
        string $loginPageUrl,
        string $email,
        string $password,
    ): ?array {
        $state = $this->getQueryParam($loginPageUrl, 'state');
        $pagePath = parse_url($loginPageUrl, PHP_URL_PATH) ?? '';

        if (str_contains($pagePath, '/u/login/identifier')) {
            $postUrl = "{$this->auth0Base}/u/login/identifier";
            $idResponse = $this->client->post($postUrl, [
                'form_params' => [
                    'state' => $state,
                    'username' => $email,
                    'action' => 'default',
                ],
                'headers' => [
                    'Origin' => $this->auth0Base,
                    'Referer' => $loginPageUrl,
                ],
            ]);

            if (!in_array($idResponse->getStatusCode(), [302, 303], true)) {
                return null;
            }

            [$pwPage, $pwPageUrl] = $this->followRedirects($idResponse, $postUrl);
            $pwPath = parse_url($pwPageUrl, PHP_URL_PATH) ?? '';

            if (str_contains($pwPath, '/u/login/identifier')) {
                return null;
            }

            $pwState = $this->getQueryParam($pwPageUrl, 'state') ?? $state;

            $loginUrl = "{$this->auth0Base}/u/login";
            $loginResp = $this->client->post($loginUrl, [
                'form_params' => [
                    'state' => $pwState,
                    'username' => $email,
                    'password' => $password,
                    'action' => 'default',
                ],
                'headers' => [
                    'Origin' => $this->auth0Base,
                    'Referer' => $pwPageUrl,
                ],
            ]);

            return [$loginResp, $loginUrl];
        }

        $loginUrl = "{$this->auth0Base}/u/login";
        $loginResp = $this->client->post($loginUrl, [
            'form_params' => [
                'state' => $state,
                'username' => $email,
                'password' => $password,
                'action' => 'default',
            ],
            'headers' => [
                'Origin' => $this->auth0Base,
                'Referer' => $loginPageUrl,
            ],
        ]);

        return [$loginResp, $loginUrl];
    }

    /**
     * Follow redirects and auto-submit HTML forms until settled or cookies found.
     */
    private function followFullChain(ResponseInterface $response, string $currentUrl): ResponseInterface
    {
        for ($i = 0; $i < self::MAX_HOPS; $i++) {
            if (count($this->collectTargetCookies()) === count(self::TARGET_COOKIES)) {
                return $response;
            }

            $status = $response->getStatusCode();

            if (in_array($status, [301, 302, 303, 307, 308], true)) {
                $location = $response->getHeaderLine('Location');
                if (empty($location)) {
                    return $response;
                }
                $currentUrl = $this->resolveUrl($currentUrl, $location);
                $response = $this->client->get($currentUrl);
                continue;
            }

            if ($status === 200 && str_contains($response->getHeaderLine('Content-Type'), 'text/html')) {
                $form = $this->parseFirstForm($response->getBody()->getContents(), $currentUrl);
                if ($form !== null) {
                    $currentUrl = $form['action'];
                    $response = $this->client->post($currentUrl, [
                        'form_params' => $form['fields'],
                    ]);
                    continue;
                }
                return $response;
            }

            return $response;
        }

        return $response;
    }

    /**
     * Follow redirect responses (no form submission).
     *
     * @return array{ResponseInterface, string} The final response and its URL.
     */
    private function followRedirects(ResponseInterface $response, string $currentUrl, int $maxHops = 5): array
    {
        for ($i = 0; $i < $maxHops; $i++) {
            if (!in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true)) {
                break;
            }
            $location = $response->getHeaderLine('Location');
            $currentUrl = $this->resolveUrl($currentUrl, $location);
            $response = $this->client->get($currentUrl);
        }
        return [$response, $currentUrl];
    }

    /**
     * Parse the first HTML form in the body.
     *
     * @return array{action: string, fields: array<string, string>}|null
     */
    private function parseFirstForm(string $html, string $baseUrl): ?array
    {
        $doc = new \DOMDocument();
        @$doc->loadHTML($html, LIBXML_NOERROR);
        $forms = $doc->getElementsByTagName('form');
        if ($forms->length === 0) {
            return null;
        }

        $form = $forms->item(0);
        $action = $form->getAttribute('action');
        if (empty($action)) {
            return null;
        }

        if (!str_starts_with($action, 'http')) {
            $action = $this->resolveUrl($baseUrl, $action);
        }

        $fields = [];
        $inputs = $form->getElementsByTagName('input');
        for ($i = 0; $i < $inputs->length; $i++) {
            $input = $inputs->item($i);
            $name = $input->getAttribute('name');
            if (!empty($name)) {
                $fields[$name] = $input->getAttribute('value') ?? '';
            }
        }

        return ['action' => $action, 'fields' => $fields];
    }

    /**
     * Extract a query parameter from a URL.
     */
    private function getQueryParam(string $url, string $param): ?string
    {
        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $params);
        return $params[$param] ?? null;
    }

    /**
     * Resolve a possibly-relative URL against a base URL.
     */
    private function resolveUrl(string $baseUrl, string $relativeUrl): string
    {
        if (str_starts_with($relativeUrl, 'http')) {
            return $relativeUrl;
        }

        $parsed = parse_url($baseUrl);
        $base = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');

        if (str_starts_with($relativeUrl, '/')) {
            return $base . $relativeUrl;
        }

        $basePath = $parsed['path'] ?? '/';
        $dir = substr($basePath, 0, (int)strrpos($basePath, '/'));
        return $base . $dir . '/' . $relativeUrl;
    }
}
