<?php

namespace Pantheon\Terminus\Helpers;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusAlreadyExistsException;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\Traits\CommandExecutorTrait;
use Pantheon\Terminus\ProgressBars\ProcessProgressBar;
use Robo\Common\IO;
use Robo\Contract\ConfigAwareInterface;
use Robo\Contract\IOAwareInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Class ShellExecHelper.
 *
 * A helper for executing commands on the local client. A wrapper for 'exec'.
 *
 * @package Pantheon\Terminus\Helpers
 */
class LocalMachineHelper implements ConfigAwareInterface, ContainerAwareInterface, IOAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use IO;
    use CommandExecutorTrait {
        CommandExecutorTrait::execute as executeUnbuffered;
    }

    /**
     * Executes the given command on the local machine and return the exit code and output.
     *
     * @param string $cmd The command to execute
     * @return array The command output and exit_code
     */
    public function exec($cmd, $callback = null)
    {
        $process = $this->getProcess($cmd);
        $process->run($callback);
        return [
            'output' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'exit_code' => $process->getExitCode(),
        ];
    }

    /**
     * Executes a buffered command.
     *
     * @param string $cmd The command to execute
     * @param callable $callback A function to run while waiting for the process to complete
     * @param bool $progressIndicatorAllowed Allow the progress bar to be used (if in tty mode only)
     * @return array The command output and exit_code
     *
     * @throws TerminusException
     */
    public function execute($cmd, $callback, $progressIndicatorAllowed): array
    {
        $process = $this->getProcess($cmd);
        $useTty = $this->useTty();
        $process->setTty($useTty);
        if (false === $useTty && !stream_isatty(STDIN)) {
            $process->setInput(STDIN);
        }

        $process->start();
        if ($progressIndicatorAllowed && $useTty) {
            $this->getProgressBar($process)->cycle($callback);
        } else {
            false === $useTty ?
                $process->wait($callback) :
                $process->wait();
        }

        return [
            'output' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'exit_code' => $process->getExitCode(),
        ];
    }

    /**
     * Returns a set-up filesystem object.
     *
     * @return Filesystem
     */
    public function getFilesystem()
    {
        return new Filesystem();
    }

    /**
     * Returns a finder object
     *
     * @return Finder
     */
    public function getFinder()
    {
        return new Finder();
    }

    /**
     * Returns a ProcessProgressBar.
     *
     * @param Process $process
     *
     * @return ProcessProgressBar
     */
    public function getProgressBar(Process $process)
    {
        $nickname = \uniqid(__METHOD__ . "-");
        $this->getContainer()->add($nickname, ProcessProgressBar::class)
            ->addArguments([$this->output(), $process]);
        return $this->getContainer()->get($nickname);
    }

    /**
     * Reads to a file from the local system.
     *
     * @param string $filename Name of the file to read
     * @return string Content read from that file
     */
    public function readFile($filename)
    {
        return file_get_contents($this->fixFilename($filename));
    }

    /**
     * Determine whether the use of a tty is appropriate.
     *
     * @return bool
     */
    public function useTty(): bool
    {
        if (!$this->input()->isInteractive()) {
            // If we are not in interactive mode, then never use a tty.
            return false;
        }

        return stream_isatty(STDIN) && stream_isatty(STDOUT);
    }

    /**
     * Writes to a file on the local system.
     *
     * @param string $filename Name of the file to write to
     * @param string $content Content to write to the file
     */
    public function writeFile($filename, $content)
    {
        $this->getFilesystem()->dumpFile($this->fixFilename($filename), $content);
    }

    /**
     * Accepts a filename/full path and localizes it to the user's system.
     *
     * @param string $filename
     * @return string
     */
    protected function fixFilename($filename)
    {
        $config = $this->getConfig();
        return $config->fixDirectorySeparators(str_replace('~', $config->get('user_home') ?? '', $filename ?? ''));
    }

    /**
     * Returns a set-up process object.
     *
     * @param string $cmd The command to execute
     * @return Process
     */
    protected function getProcess(string $cmd)
    {
        $process = Process::fromShellCommandline($cmd);
        $config = $this->getConfig();
        $process->setTimeout($config->get('timeout'));

        // Forward environment variables to the subprocess.
        // This allows users to control Composer behavior (e.g., COMPOSER_AUDIT_BLOCK_INSECURE)
        // and other subprocess behavior via TERMINUS_FORWARD_ENV.
        $forwardedVars = $this->getForwardedEnvironment();
        if (!empty($forwardedVars)) {
            // Merge forwarded vars with the current process environment.
            // We need to get all environment variables and merge with forwarded ones.
            // Use getenv() to get actual environment variables (not all $_SERVER keys are env vars).
            $currentEnv = [];
            // Get all environment variables using getenv() for each known env var from $_SERVER
            // This ensures we only get actual environment variables, not other $_SERVER keys.
            foreach ($_SERVER as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    // Verify this is actually an environment variable by checking getenv()
                    $envValue = getenv($key);
                    if ($envValue !== false) {
                        $currentEnv[$key] = $envValue;
                    }
                }
            }
            // Also check $_ENV for any additional variables
            foreach ($_ENV as $key => $value) {
                if (is_string($key) && is_string($value) && !isset($currentEnv[$key])) {
                    $currentEnv[$key] = $value;
                }
            }
            // Merge: start with current env, then override with forwarded vars
            $env = array_merge($currentEnv, $forwardedVars);
            $process->setEnv($env);
        }

        return $process;
    }

    /**
     * Gets the environment variables that should be forwarded to subprocesses.
     *
     * This method:
     * - Always forwards Composer-related environment variables (e.g., COMPOSER_AUDIT_BLOCK_INSECURE)
     *   to allow users to control Composer's security audit behavior during plugin installation.
     * - Forwards any variables specified in TERMINUS_FORWARD_ENV (comma-separated list).
     *
     * Note: We do not globally disable Composer audits. We only respect explicit user
     * instructions via environment variables.
     *
     * @return array Environment variables to forward (key => value pairs), or empty array if none to forward
     */
    protected function getForwardedEnvironment(): array
    {
        $forwardedVars = [];

        // Always forward Composer-related environment variables if they are set.
        // This allows users to override Composer's security audit behavior.
        $composerEnvVars = [
            'COMPOSER_AUDIT_BLOCK_INSECURE',
            'COMPOSER_ALLOW_SUPERUSER',
            'COMPOSER_DISABLE_XDEBUG_WARN',
            'COMPOSER_MEMORY_LIMIT',
            'COMPOSER_MIRROR_PATH_REPOS',
            'COMPOSER_NO_INTERACTION',
            'COMPOSER_PROCESS_TIMEOUT',
        ];

        foreach ($composerEnvVars as $var) {
            $value = getenv($var);
            if ($value !== false) {
                $forwardedVars[$var] = $value;
            }
        }

        // Check for TERMINUS_FORWARD_ENV (comma-separated list of env var names to forward).
        $terminusForwardEnv = getenv('TERMINUS_FORWARD_ENV');
        if ($terminusForwardEnv !== false && !empty($terminusForwardEnv)) {
            $varsToForward = array_map('trim', explode(',', $terminusForwardEnv));
            foreach ($varsToForward as $varName) {
                if (!empty($varName)) {
                    $value = getenv($varName);
                    if ($value !== false) {
                        $forwardedVars[$varName] = $value;
                    }
                }
            }
        }

        return $forwardedVars;
    }

    /**
     * Clones the Git repository.
     *
     * @param string $gitUrl
     * @param string $path
     * @param bool $overrideIfExists
     * @param string $branch
     *   The branch to clone. Defaults to remote HEAD pointer.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusAlreadyExistsException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function cloneGitRepository(
        string $gitUrl,
        string $path,
        bool $overrideIfExists = false,
        string $branch = ''
    ) {
        if (is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
            if (!$overrideIfExists) {
                throw new TerminusAlreadyExistsException(sprintf('The repository already exists in %s', $path));
            }

            if ('' !== trim($path, DIRECTORY_SEPARATOR . ' ')) {
                $this->executeUnbuffered('rm -rf "%s"', [$path]);
            }
        }

        $additionalOptions = $branch ? sprintf('--branch %s', $branch) : '';

        $this->executeUnbuffered('git clone %s %s %s', [$gitUrl, $path, $additionalOptions]);
    }

    /**
     * Opens the given URL in a browser on the local machine.
     *
     * @param $url The URL to be opened
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function openUrl($url)
    {
        $cmd = '';
        switch (php_uname('s')) {
            case 'Linux':
                $cmd = 'xdg-open';
                break;
            case 'Darwin':
                $cmd = 'open';
                break;
            case 'Windows NT':
                $cmd = 'start';
                break;
        }
        if (!$cmd) {
            throw new TerminusException('Terminus is unable to open a browser on this OS.');
        }
        $command = sprintf('%s %s', $cmd, $url);

        $this->getProcess($command)->run();
    }
}
