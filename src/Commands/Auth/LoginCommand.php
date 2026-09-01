<?php

namespace Pantheon\Terminus\Commands\Auth;

use Pantheon\Terminus\Auth\Auth0Authenticator;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\TerminusModel;
use Symfony\Component\Process\Process;

/**
 * Class LoginCommand.
 *
 * @package Pantheon\Terminus\Commands\Auth
 */
class LoginCommand extends TerminusCommand
{
    /**
     * Logs in a user to Pantheon.
     *
     * @command auth:login
     * @aliases login
     *
     * @option machine-token Grants access for a user and is saved for future logins
     * @option email Uses an existing machine token for this user
     * @option password Log in with email and password via Auth0
     * @option google Log in with Google via Auth0
     *
     * @usage --machine-token=<machine_token> Logs in a user granted the machine token <machine_token>.
     * @usage Logs in a user with a previously saved machine token.
     * @usage --email=<email> Logs in a user with a previously saved machine token belonging to <email>.
     * @usage --password Logs in a user with email and password.
     * @usage --google Logs in a user with Google OAuth.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function logIn(array $options = ['machine-token' => null, 'email' => null, 'password' => false, 'google' => false,]): void
    {
        if (!empty($options['google'])) {
            $this->googleLogIn();
            return;
        }

        if (!empty($options['password'])) {
            $this->passwordLogIn($options['email'] ?? null);
            return;
        }

        $tokens = $this->session()->getTokens();

        if (isset($options['machine-token'])) {
            try {
                $token = $tokens->get($options['machine-token']);
                $this->processLogIn($token);
                return;
            } catch (\Exception $e) {
                $this->log()->notice('Logging in via machine token.');
                $tokens->create($options['machine-token']);
            }
        }

        if (isset($options['email'])) {
            $token = $tokens->get($options['email']);
            $this->processLogIn($token);
            return;
        }

        $all_tokens = $tokens->all();
        switch (count($all_tokens)) {
            case 0:
                throw new TerminusException(
                    "Please visit the dashboard to generate a machine token:\n{url}",
                    ['url' => $this->getMachineTokenCreationURL(),]
                );
            case 1:
                $token = array_shift($all_tokens);
                $this->log()->notice('Found a machine token for {email}.', ['email' => $token->get('email'),]);
                $this->processLogIn($token);
                break;
            default:
                $this->log()->notice(
                    "Tokens were saved for the following email addresses:\n{tokens}\nYou may log in via `terminus"
                    . " auth:login --email=<email>`, or you may visit the dashboard to generate a machine"
                    . " token:\n{url}",
                    ['tokens' => implode("\n", $tokens->ids()), 'url' => $this->getMachineTokenCreationURL(),]
                );
        }
    }

    /**
     * Logs in via email and password through Auth0.
     */
    private function passwordLogIn(?string $email = null): void
    {
        $email = $email ?? $this->io()->ask('Email');
        $password = $this->io()->askHidden('Password');

        $hermesUrl = sprintf(
            '%s://%s',
            $this->config->get('dashboard_protocol'),
            $this->config->get('dashboard_host'),
        );

        $authenticator = new Auth0Authenticator($hermesUrl);
        $result = $authenticator->login($email, $password);

        $userId = $this->extractUserIdFromSession($result['session']);

        $this->session()->setData([
            'session' => $result['session'],
            'access_token' => $result['access_token'],
            'expires_at' => time() + 86400,
            'user_id' => $userId,
        ]);

        $this->log()->notice('Logged in via password.');
    }

    /**
     * Logs in via Google OAuth through Auth0 using PKCE.
     */
    private function googleLogIn(): void
    {
        $hermesUrl = sprintf(
            '%s://%s',
            $this->config->get('dashboard_protocol'),
            $this->config->get('dashboard_host'),
        );

        $authenticator = new Auth0Authenticator($hermesUrl);

        $port = $this->findOAuthPort();
        $callbackUrl = "http://localhost:{$port}/auth/callback";

        $authInfo = $authenticator->getGoogleAuthUrl($callbackUrl);

        [$serverScript, $dataFile] = $this->createOAuthServerScript($authInfo['state']);
        $serverProcess = new Process(['php', '-S', "localhost:{$port}", $serverScript]);
        $serverProcess->start();

        try {
            $this->log()->notice('Opening Google login in your browser...');
            $this->log()->notice('If your browser does not open, visit this URL:');
            $this->log()->notice($authInfo['url']);

            $this->openBrowser($authInfo['url']);

            $code = $this->waitForOAuthCallback($dataFile, 120);

            $tokens = $authenticator->exchangeCodeForTokens(
                $code,
                $authInfo['code_verifier'],
                $callbackUrl,
                $authInfo['client_id'],
                $authInfo['auth0_base'] ?? null,
            );

            $idToken = $tokens['id_token'] ?? null;
            if (empty($idToken)) {
                throw new TerminusException('No id_token in Auth0 response.');
            }

            $userId = $this->extractUserIdFromJwt($idToken);

            $this->session()->setData([
                'access_token' => $tokens['access_token'] ?? null,
                'expires_at' => time() + ($tokens['expires_in'] ?? 86400),
                'user_id' => $userId,
            ]);

            $this->log()->notice('Logged in via Google.');
        } finally {
            $serverProcess->stop(0);
        }
    }

    private function openBrowser(string $url): void
    {
        $cmd = match (php_uname('s')) {
            'Linux' => 'xdg-open',
            'Darwin' => 'open',
            'Windows NT' => 'start',
            default => null,
        };
        if ($cmd === null) {
            $this->log()->warning('Cannot open browser automatically on this OS.');
            return;
        }
        (new Process([$cmd, $url]))->start();
    }

    private function findOAuthPort(): int
    {
        foreach ([3000, 4000] as $port) {
            $socket = @stream_socket_server("tcp://127.0.0.1:{$port}");
            if ($socket !== false) {
                fclose($socket);
                return $port;
            }
        }
        throw new TerminusException('Ports 3000 and 4000 are both in use. Free one to continue.');
    }

    /**
     * Create a PHP server script that captures the OAuth authorization code.
     *
     * @return array{string, string} Server script path and data file path.
     */
    private function createOAuthServerScript(string $expectedState): array
    {
        $token = bin2hex(random_bytes(8));
        $scriptPath = sys_get_temp_dir() . "/terminus_oauth_server_{$token}.php";
        $dataFile = sys_get_temp_dir() . "/terminus_oauth_data_{$token}";

        $php = <<<'SERVERSCRIPT'
<?php
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($uri, '/auth/callback') !== 0) {
    http_response_code(404);
    echo 'Not found';
    exit;
}
parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
$state = $query['state'] ?? '';
$code = $query['code'] ?? '';
$error = $query['error'] ?? '';
$errorDesc = $query['error_description'] ?? '';
if ($state !== 'EXPECTED_STATE') {
    http_response_code(403);
    echo 'Invalid state parameter.';
    exit;
}
if ($error) {
    file_put_contents('DATA_FILE', json_encode(['error' => $error, 'error_description' => $errorDesc]));
} else {
    file_put_contents('DATA_FILE', json_encode(['code' => $code]));
}
header('Content-Type: text/html');
echo '<html><body><h2>Authentication successful.</h2><p>You can close this window and return to the terminal.</p></body></html>';
SERVERSCRIPT;

        $php = str_replace('EXPECTED_STATE', addslashes($expectedState), $php);
        $php = str_replace('DATA_FILE', addslashes($dataFile), $php);

        file_put_contents($scriptPath, $php);
        return [$scriptPath, $dataFile];
    }

    /**
     * Wait for the OAuth callback to write the authorization code.
     */
    private function waitForOAuthCallback(string $dataFile, int $timeoutSeconds): string
    {
        $start = time();
        while (true) {
            if (file_exists($dataFile)) {
                $data = json_decode(file_get_contents($dataFile), true);
                unlink($dataFile);
                if (isset($data['error'])) {
                    throw new TerminusException(
                        'OAuth error: {error} - {description}',
                        ['error' => $data['error'], 'description' => $data['error_description'] ?? '']
                    );
                }
                if (!empty($data['code'])) {
                    return $data['code'];
                }
                throw new TerminusException('OAuth callback did not include an authorization code.');
            }
            if ((time() - $start) > $timeoutSeconds) {
                throw new TerminusException('Google login timed out waiting for browser authentication.');
            }
            usleep(500000);
        }
    }

    /**
     * Extract the Pantheon user ID from an Auth0 JWT access token.
     */
    private function extractUserIdFromJwt(string $jwt): string
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            throw new TerminusException('Access token is not a valid JWT.');
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        $userId = $payload['http://oidc.panth.io/pantheon']['user_id'] ?? null;
        if (empty($userId)) {
            throw new TerminusException('Access token does not contain a Pantheon user ID.');
        }

        return $userId;
    }

    /**
     * Extract the user ID from the session token (everything before the first ':').
     */
    private function extractUserIdFromSession(string $session): string
    {
        $decoded = urldecode($session);
        $colonPos = strpos($decoded, ':');
        if ($colonPos === false) {
            throw new TerminusException('Session token does not contain a user ID.');
        }
        return substr($decoded, 0, $colonPos);
    }

    /**
     * Processes the login.
     *
     * @param TerminusModel $token
     */
    private function processLogIn(TerminusModel $token): void
    {
        /** @var $token \Pantheon\Terminus\Models\SavedToken */
        $token->logIn();
        $this->log()->notice('Logged in via machine token.');
    }

    /**
     * Generates the URL string for where to create a machine token.
     *
     * @return string
     */
    private function getMachineTokenCreationURL()
    {
        return vsprintf(
            '%s://%s/machine-token/create/%s',
            [
                $this->config->get('dashboard_protocol'),
                $this->config->get('dashboard_host'),
                gethostname(),
            ]
        );
    }
}
