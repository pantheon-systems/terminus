<?php

namespace Pantheon\Terminus\Traits;

use Symfony\Component\Process\Process;
use Pantheon\Terminus\Helpers\LocalMachineHelper;

trait GithubInstallTrait
{
    /**
     * Start temporary server to handle app installation redirects.
     * Returns array of (url, flag_file).
     */
    protected function startTemporaryServer(): array
    {
        $this->log()->debug('Starting temporary server to handle VCS installation redirects if needed...');

        $token = bin2hex(random_bytes(16));
        $port = $this->findAvailablePort();
        $url = "http://localhost:{$port}/callback?token={$token}";

        list($server_script, $flag_file) = $this->createServerScript($token, self::REDIRECT_URL);
        $process = new Process(['php', '-S', "localhost:{$port}", $server_script]);
        $process->start();

        $this->serverProcess = $process;

        $this->log()->debug("Temporary server started at: {$url}");
        if (getenv('TESTING_MODE')) {
            // Write url to a file for testing purposes.
            file_put_contents('/tmp/terminus_test_server_url', $url);
        }

        return [$url, $flag_file, $process];
    }

    /**
     * Find an available port on localhost.
     */
    private function findAvailablePort(): int
    {
        $socket = stream_socket_server("tcp://127.0.0.1:0");
        if ($socket === false) {
            throw new \RuntimeException("Cannot find open port");
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        return (int)substr(strrchr($address, ':'), 1);
    }

    /**
     * Create a temporary PHP script to handle server requests.
     */
    private function createServerScript(string $token, $redirect_url): array
    {
        $scriptPath = sys_get_temp_dir() . "/notify_server_$token.php";
        $flagFile = sys_get_temp_dir() . "/terminus_notify_$token";

        $php = <<<PHP
<?php
parse_str(\$_SERVER['QUERY_STRING'] ?? '', \$query);
\$request_uri = \$_SERVER['REQUEST_URI'] ?? '';
if (strpos(\$request_uri, '/callback') === 0 && (\$query['token'] ?? '') === '$token') {
    echo "Authorization successful. You can close this window.\n";
    file_put_contents('$flagFile', 'done');
    header('Location: ' . '$redirect_url');
    exit;
} else {
    echo "ERROR: Invalid request.\n";
    http_response_code(403);
    echo "Forbidden";
}
PHP;
        file_put_contents($scriptPath, $php);
        return [$scriptPath, $flagFile];
    }

    /**
     * Handle Github new installation browser flow.
     * Adapted from RepositorySiteCreateCommand::handleGithubNewInstallation
     */
    protected function handleGithubNewInstallation(string $auth_url, string $flag_file, int $timeout): bool
    {
        // Ensure auth_url is unquoted for opening in the browser.
        $url_to_open = trim($auth_url, '"');

        $this->log()->notice("Opening GitHub App authorization link in your browser...");
        $this->log()->notice("If your browser does not open, please manually visit this URL:");
        $this->log()->notice($url_to_open);

        try {
            $this->getContainer()
                ->get(LocalMachineHelper::class)
                ->openUrl($url_to_open);
        } catch (\Exception $e) {
            $this->log()->warning("Could not automatically open browser: " . $e->getMessage());
            $this->log()->warning("Please open the URL manually: " . $url_to_open);
        }

        $minutes = (int) ($timeout / 60);

        $this->log()->notice(sprintf(
            "Waiting for authorization to complete in browser (up to %d minutes)...",
            $minutes
        ));

        // A server should be running by now and will eventually (if succeeded) write 'done' to the flag file.
        $start_time = time();
        $success = false;
        while (true) {
            if (file_exists($flag_file)) {
                $this->log()->notice('Authorization confirmed via browser.');
                // Cleanup flag file
                unlink($flag_file);
                $success = true;
                break;
            }
            if ((time() - $start_time) > $timeout) {
                // Timeout
                $this->log()->error('Authorization timed out.');
                break;
            }
            // Sleep for a short interval before checking again
            usleep(500000); // 0.5 seconds
        }

        return $success;
    }
}
