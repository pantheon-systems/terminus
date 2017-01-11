<?php

namespace Pantheon\Terminus\Commands\Remote;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
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
     * @var array
     */
    protected $valid_frameworks = [];
    /**
     * @var Site
     */
    private $site;
    /**
     * @var Environment
     */
    private $environment;

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
     * Execute the command remotely
     *
     * @param array $command_args
     * @return string
     * @throws TerminusProcessException
     */
    protected function executeCommand(array $command_args)
    {
        $this->validateEnvironment($this->site, $this->environment);

        $command_line = $this->getCommandLine($command_args);

        $output = $this->output();
        $result = $this->environment->sendCommandViaSsh(
            $command_line,
            function ($buffer) use ($output) {
                $output->writeln($buffer);
            }
        );
        $output = $result['output'];
        $exit = $result['exit_code'];

        $this->log()->notice('Command: {site}.{env} -- {command} [Exit: {exit}]', [
            'site'    => $this->site->get('name'),
            'env'     => $this->environment->id,
            'command' => escapeshellarg($command_line),
            'exit'    => $exit,
        ]);

        if ($exit != 0) {
            throw new TerminusProcessException($output);
        }

        return rtrim($output);
    }

    /**
     * Validates that the environment's connection mode is appropriately set
     *
     * @param Site $site
     * @param Environment $environment
     */
    protected function validateEnvironment($site, $environment)
    {
        $this->validateConnectionMode($environment->get('connection_mode'));
        $this->validateFramework($site->get('framework'));
    }

    /**
     * Validates that the environment is using the correct connection mode
     *
     * @param string $mode
     */
    protected function validateConnectionMode($mode)
    {
        if ($mode == 'git') {
            $this->log()->warning(
                'This environment is in read-only Git mode. If you want to make changes to the codebase of this site '
                . '(e.g. updating modules or plugins), you will need to toggle into read/write SFTP mode first.'
            );
        }
    }

    /**
     * Validates the framework being used
     *
     * @param string $framework
     * @throws TerminusException
     */
    protected function validateFramework($framework)
    {
        if (!in_array($framework, $this->valid_frameworks)) {
            throw new TerminusException(
                'The {command} command is only available on sites running {frameworks}. The framework for this site is {framework}.',
                [
                    'command'    => $this->command,
                    'frameworks' => implode(', ', $this->valid_frameworks),
                    'framework'  => $framework,
                ]
            );
        }
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
}
