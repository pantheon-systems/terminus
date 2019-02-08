<?php

namespace Pantheon\Terminus\Commands\Remote;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

/**
 * Class SSHBaseCommand
 * Base class for Terminus commands that deal with sending SSH commands
 * @package Pantheon\Terminus\Commands\Remote
 */
abstract class SSHBaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * @var string Name of the command to be run as it will be used on server
     */
    protected $command = '';
    /**
     * @var Environment
     */
    private $environment;
    /**
     * @var Site
     */
    private $site;
    /**
     * @var bool
     */
    protected $progressAllowed;

    /**
     * Define the environment and site properties
     *
     * @param string $site_env_id The site/env to retrieve in <site>.<env> format
     */
    protected function prepareEnvironment($site_env_id)
    {
        list($this->site, $this->environment) = $this->getSiteEnv($site_env_id);
    }

    /**
     * progressAllowed sets the field that controls whether a progress bar
     * may be displayed when a program is executed. If allowed, a progress
     * bar will be used in tty mode.
     *
     * @param type|bool $allowed
     * @return $this
     */
    protected function setProgressAllowed($allowed = true)
    {
        $this->progressAllowed = $allowed;
        return $this;
    }

    /**
     * Execute the command remotely
     *
     * @param array $command_args
     * @return string
     * @throws TerminusProcessException
     */
    protected function executeCommand(array $command_args)
    {
        $this->validateEnvironment($this->environment);

        $command_summary = $this->getCommandSummary($command_args);
        $command_line = $this->getCommandLine($command_args);

        $ssh_data = $this->sendCommandViaSsh($command_line);

        $this->log()->notice('Command: {site}.{env} -- {command} [Exit: {exit}]', [
            'site'    => $this->site->get('name'),
            'env'     => $this->environment->id,
            'command' => $command_summary,
            'exit'    => $ssh_data['exit_code'],
        ]);

        if ($ssh_data['exit_code'] != 0) {
            throw new TerminusProcessException($ssh_data['output']);
        }
    }

    /**
     * Sends a command to an environment via SSH.
     *
     * @param string $command The command to be run on the platform
     */
    protected function sendCommandViaSsh($command)
    {
        $ssh_command = $this->getConnectionString() . ' ' . ProcessUtils::escapeArgument($command);
        if ($this->getConfig()->get('test_mode')) {
            return $this->divertForTestMode($ssh_command);
        }
        return $this->getContainer()->get(LocalMachineHelper::class)->execute(
            $ssh_command,
            $this->getOutputCallback(),
            $this->progressAllowed
        );
    }

    /**
     * Validates that the environment's connection mode is appropriately set
     *
     * @param Environment $environment
     */
    protected function validateEnvironment($environment)
    {
        // Only warn in dev / multidev
        if ($environment->isDevelopment()) {
            $this->validateConnectionMode($environment->get('connection_mode'));
        }
    }

    /**
     * Validates that the environment is using the correct connection mode
     *
     * @param string $mode
     */
    protected function validateConnectionMode($mode)
    {
        if ((!$this->getConfig()->get('hide_git_mode_warning')) && ($mode == 'git')) {
            $this->log()->warning(
                'This environment is in read-only Git mode. If you want to make changes to the codebase of this site '
                . '(e.g. updating modules or plugins), you will need to toggle into read/write SFTP mode first.'
            );
        }
    }

    /**
     * Outputs a message if Terminus is in test mode and uses it to mock the command's response
     *
     * @string $ssh_command
     * @return string[] $response Elements as follow:
     *         string output    The output from the command run
     *         string exit_code The status code returned by the command run
     */
    private function divertForTestMode($ssh_command)
    {
        $output = "Terminus is in test mode. SSH commands will not be sent over the wire. "
            . PHP_EOL . "SSH Command: ${ssh_command}";
        $container = $this->getContainer();
        if ($container->has('output')) {
            $container->get('output')->write($output);
        }
        return [
            'output' => $output,
            'exit_code' => 0
        ];
    }

    /**
     * Escape the command-line args
     *
     * @param string[] $args All of the arguments to escape
     * @param string[]
     */
    private function escapeArguments($args)
    {
        return array_map(
            function ($arg) {
                return $this->escapeArgument($arg);
            },
            $args
        );
    }

    /**
     * Escape one command-line arg
     *
     * @param string $arg The argument to escape
     * @return string
     */
    private function escapeArgument($arg)
    {
        // Omit escaping for simple args.
        if (preg_match('/^[a-zA-Z0-9_-]*$/', $arg)) {
            return $arg;
        }
        return ProcessUtils::escapeArgument($arg);
    }

    /**
     * Return the first item of the $command_args that is not an option.
     *
     * @param array $command_args
     * @return string
     */
    private function firstArguments($command_args)
    {
        $result = '';
        while (!empty($command_args)) {
            $first = array_shift($command_args);
            if (strlen($first) && $first[0] == '-') {
                return $result;
            }
            $result .= " $first";
        }
        return $result;
    }

    /**
     * @param boolean $usesTty
     * @return \Closure
     */
    private function getOutputCallback()
    {
        if ($this->getContainer()->get(LocalMachineHelper::class)->useTty() === false) {
            $output = $this->output();
            $stderr = $this->stderr();

            return function ($type, $buffer) use ($output, $stderr) {
                if (Process::ERR === $type) {
                    $stderr->write($buffer);
                } else {
                    $output->write($buffer);
                }
            };
        }
        return function ($type, $buffer) {
        };
    }

    /**
     * Gets the command-line args
     *
     * @param string[] $command_args
     * @return string
     */
    private function getCommandLine($command_args)
    {
        array_unshift($command_args, $this->command);
        return implode(" ", $this->escapeArguments($command_args));
    }

    /**
     * Return a summary of the command that does not include the
     * arguments. This avoids potential information disclosure in
     * CI scripts.
     *
     * @param array $command_args
     * @return string
     */
    private function getCommandSummary($command_args)
    {
        return $this->command . $this->firstArguments($command_args);
    }

    /**
     * @return string SSH connection string
     */
    private function getConnectionString()
    {
        $sftp = $this->environment->sftpConnectionInfo();
        return vsprintf(
            'ssh -T %s@%s -p %s -o "StrictHostKeyChecking=no" -o "AddressFamily inet"',
            [$sftp['username'], $sftp['host'], $sftp['port'],]
        );
    }
}
