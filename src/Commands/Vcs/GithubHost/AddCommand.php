<?php

namespace Pantheon\Terminus\Commands\Vcs\GithubHost;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;
use Symfony\Component\Process\Process;

/**
 * Registers a GitHub Enterprise Server instance with Pantheon EVCS
 * via the GitHub App manifest flow.
 */
class AddCommand extends TerminusCommand implements RequestAwareInterface
{
    use VcsClientAwareTrait;

    protected const CALLBACK_TIMEOUT = 300;

    protected ?Process $serverProcess = null;

    public function __destruct()
    {
        $this->stopServer();
    }

    /**
     * Register a GitHub Enterprise Server instance with Pantheon.
     *
     * Creates a GitHub App on the GHES instance via the manifest flow
     * and provisions it with the Pantheon EVCS service.
     *
     * @authorize
     *
     * @command vcs:github-host:add
     * @aliases vcs-github-host-add
     *
     * @param string $hostname GitHub Enterprise Server hostname (e.g. ghes.example.com)
     *
     * @option credentials-file Path to save/read the JSON credentials file (default: creds.json)
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage ghes.example.com Registers a GHES instance with Pantheon.
     * @usage ghes.example.com --credentials-file=/tmp/ghes-creds.json Registers using a custom credentials file path.
     */
    public function add(string $hostname, array $options = ['credentials-file' => 'creds.json'])
    {
        $credentials_file = $options['credentials-file'];

        $hostname = $this->sanitizeHostname($hostname);
        $ghesBaseUrl = "https://{$hostname}";

        $this->log()->notice('Fetching app manifest for {hostname}...', ['hostname' => $hostname]);
        $manifestResponse = $this->getVcsClient()->getProvisionManifest($hostname);
        $manifest = (array) ($manifestResponse['data'] ?? $manifestResponse);

        $port = $this->findAvailablePort();
        $localBaseUrl = "http://127.0.0.1:{$port}";
        $manifest['redirect_url'] = "{$localBaseUrl}/callback";

        $manifestJson = json_encode($manifest, JSON_UNESCAPED_SLASHES);
        $token = bin2hex(random_bytes(16));
        $flagFile = sys_get_temp_dir() . "/terminus_ghes_register_{$token}";

        $serverScript = $this->createServerScript($ghesBaseUrl, $manifestJson, $flagFile);
        $this->startServer($port, $serverScript);

        $this->log()->notice('Opening browser to start GitHub App registration...');
        $this->log()->notice('If your browser does not open, visit: {url}', ['url' => $localBaseUrl]);

        try {
            $this->getContainer()
                ->get(LocalMachineHelper::class)
                ->openUrl($localBaseUrl);
        } catch (\Exception $e) {
            $this->log()->warning('Could not open browser automatically: ' . $e->getMessage());
        }

        $this->log()->notice('Waiting for GHES callback (up to {minutes} minutes)...', [
            'minutes' => (int) (self::CALLBACK_TIMEOUT / 60),
        ]);

        if (!$this->waitForFlagFile($flagFile, self::CALLBACK_TIMEOUT)) {
            throw new TerminusException(
                'GHES callback was not received within {timeout} seconds. Please try again.',
                ['timeout' => self::CALLBACK_TIMEOUT]
            );
        }

        $this->log()->notice('Callback received from GHES.');
        $this->log()->notice('');
        $this->log()->notice('A JSON response should now be visible in your browser.');
        $this->log()->notice('Save the entire JSON to: {file}', ['file' => realpath('.') . '/' . $credentials_file]);
        $this->log()->notice('Then press Enter to continue, or wait 5 minutes to auto-proceed.');
        $this->log()->notice('');

        $this->waitForConfirmation(self::CALLBACK_TIMEOUT);

        if (!file_exists($credentials_file)) {
            throw new TerminusException(
                'Credentials file not found: {file}',
                ['file' => $credentials_file]
            );
        }

        $credentialsJson = file_get_contents($credentials_file);
        if ($credentialsJson === false) {
            throw new TerminusException(
                'Unable to read credentials file: {file}',
                ['file' => $credentials_file]
            );
        }

        $credentials = json_decode($credentialsJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TerminusException('Invalid JSON in {file}: ' . json_last_error_msg(), ['file' => $credentials_file]);
        }

        $this->validateCredentials($credentials);

        $provisionPayload = [
            'hostname' => $hostname,
            'app_id' => (string) ($credentials['id'] ?? ''),
            'slug' => $credentials['slug'] ?? '',
            'name' => $credentials['name'] ?? '',
            'client_id' => $credentials['client_id'] ?? '',
            'client_secret' => $credentials['client_secret'] ?? '',
            'webhook_secret' => $credentials['webhook_secret'] ?? '',
            'pem' => $credentials['pem'] ?? '',
        ];

        $this->log()->notice('Provisioning GHES instance...');
        $result = $this->getVcsClient()->provision($provisionPayload);

        $this->stopServer();
        @unlink($serverScript);

        $slug = $provisionPayload['slug'];

        $this->log()->notice('');
        $this->log()->notice('========================================');
        $this->log()->notice(' GHES Instance Registered Successfully!');
        $this->log()->notice('========================================');
        $this->log()->notice(' App Name:     {name}', ['name' => $provisionPayload['name']]);
        $this->log()->notice(' App ID:       {id}', ['id' => $provisionPayload['app_id']]);
        $this->log()->notice(' Slug:         {slug}', ['slug' => $slug]);
        $this->log()->notice(' Settings URL: {url}', ['url' => "{$ghesBaseUrl}/settings/apps/{$slug}"]);
        $this->log()->notice('========================================');
    }

    private function sanitizeHostname(string $hostname): string
    {
        $hostname = preg_replace('#^https?://#', '', $hostname);
        $hostname = rtrim($hostname, '/');

        if (empty($hostname) || !preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-\.]*[a-zA-Z0-9])?$/', $hostname)) {
            throw new TerminusException(
                'Invalid hostname: {hostname}. Provide a valid GHES hostname (e.g. ghes.example.com).',
                ['hostname' => $hostname]
            );
        }

        return $hostname;
    }

    private function findAvailablePort(): int
    {
        $socket = stream_socket_server("tcp://127.0.0.1:0");
        if ($socket === false) {
            throw new TerminusException('Cannot find an available port on localhost.');
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        return (int) substr(strrchr($address, ':'), 1);
    }

    private function createServerScript(string $ghesBaseUrl, string $manifestJson, string $flagFile): string
    {
        $escapedManifest = htmlspecialchars($manifestJson, ENT_QUOTES, 'UTF-8');
        $escapedGhesUrl = htmlspecialchars($ghesBaseUrl, ENT_QUOTES, 'UTF-8');

        $scriptPath = sys_get_temp_dir() . '/terminus_ghes_server_' . bin2hex(random_bytes(8)) . '.php';

        $formAction = "{$ghesBaseUrl}/settings/apps/new";

        $php = <<<'SERVERSCRIPT'
<?php
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);

if ($path === '/callback') {
    parse_str($_SERVER['QUERY_STRING'] ?? '', $query);
    $code = $query['code'] ?? '';
    if (empty($code)) {
        http_response_code(400);
        echo 'Missing code parameter.';
        exit;
    }
    file_put_contents('FLAG_FILE', 'done');

    $convUrl = htmlspecialchars('GHES_BASE_URL' . '/api/v3/app-manifests/' . $code . '/conversions', ENT_QUOTES, 'UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html><head><title>GHES Registration</title></head><body>
<h2>Exchanging code for app credentials...</h2>
<p>This page will submit to your GHES instance. After it responds, <strong>copy the entire JSON</strong> and paste it into your terminal.</p>
<form id="conv" method="post" action="{$convUrl}">
  <input type="submit" value="Exchange Code Now" style="font-size:1.2em;padding:10px 20px;">
</form>
<script>setTimeout(function(){document.getElementById("conv").submit();},2000);</script>
</body></html>
HTML;
    exit;
}

echo <<<'HTML'
<!DOCTYPE html>
<html><head><title>GHES Registration</title></head><body>
<h2>Registering GitHub App</h2>
<p>Redirecting to your GHES instance...</p>
<form id="manifest-form" method="post" action="FORM_ACTION">
  <input type="hidden" name="manifest" value="ESCAPED_MANIFEST">
  <input type="submit" value="Create GitHub App" style="font-size:1.2em;padding:10px 20px;">
</form>
<script>document.getElementById("manifest-form").submit();</script>
</body></html>
HTML;
SERVERSCRIPT;

        $php = str_replace('FLAG_FILE', addcslashes($flagFile, "'\\"), $php);
        $php = str_replace("'GHES_BASE_URL'", "'" . addcslashes($ghesBaseUrl, "'\\") . "'", $php);
        $php = str_replace('FORM_ACTION', htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8'), $php);
        $php = str_replace('ESCAPED_MANIFEST', $escapedManifest, $php);

        file_put_contents($scriptPath, $php);

        return $scriptPath;
    }

    private function startServer(int $port, string $scriptPath): void
    {
        $this->serverProcess = new Process(['php', '-S', "127.0.0.1:{$port}", $scriptPath]);
        $this->serverProcess->start();

        usleep(300000);

        if (!$this->serverProcess->isRunning()) {
            throw new TerminusException('Failed to start local HTTP server.');
        }

        $this->log()->debug('Local server started on port {port}', ['port' => $port]);
    }

    private function stopServer(): void
    {
        if (isset($this->serverProcess) && $this->serverProcess !== null && $this->serverProcess->isRunning()) {
            $this->serverProcess->stop(0);
        }
        $this->serverProcess = null;
    }

    private function waitForFlagFile(string $flagFile, int $timeout): bool
    {
        $start = time();
        while (true) {
            if (file_exists($flagFile)) {
                @unlink($flagFile);
                return true;
            }
            if ((time() - $start) > $timeout) {
                return false;
            }
            usleep(500000);
        }
    }

    private function waitForConfirmation(int $timeout): void
    {
        $handle = fopen('php://stdin', 'r');
        if ($handle === false) {
            return;
        }

        stream_set_blocking($handle, false);
        $start = time();

        while ((time() - $start) < $timeout) {
            $line = fgets($handle);
            if ($line !== false) {
                return;
            }
            usleep(500000);
        }
    }

    private function validateCredentials(array $credentials): void
    {
        $required = ['id', 'slug', 'client_id', 'client_secret', 'webhook_secret', 'pem'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                $missing[] = $field;
            }
        }
        if (!empty($missing)) {
            throw new TerminusException(
                'Missing required fields in credentials: {fields}',
                ['fields' => implode(', ', $missing)]
            );
        }
    }
}
